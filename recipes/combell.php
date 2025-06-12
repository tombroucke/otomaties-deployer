<?php

namespace Deployer;

use Deployer\Utility\Httpie;

require_once __DIR__ . '/../functions.php';

option('skip-ssl-verify');

/** Symlink hosts */
desc('Symlink app to host');
task('combell:host_symlink', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');
    $fullWebRootPath = $webRoot ? "{$deployPath}/current/{$webRoot}" : "{$deployPath}/current";
    $directory = currentHost()->getAlias() === 'production' ? 'www' : 'subsites/' . parse_url(get('url'), PHP_URL_HOST);

    $parts = explode('/', trim($deployPath, '/'));
    $relativeDir = implode('/', array_slice($parts, -2));
    $assumedPath = str_replace($relativeDir, '', $deployPath) . $directory;

    // check if symlink exists
    if (test("[ -L {$assumedPath} ]")) {
        writeln('Symlink already exists. Aborting.');

        return;
    }

    if (! askConfirmation("<info>Warning</> Symlinking {$fullWebRootPath} to {$assumedPath}. Are you sure you want to continue?", false)) {
        writeln('Aborted');

        return;
    }

    // check if assumed path is a directory
    if (test("[ -d {$assumedPath} ]")) {
        $backupPath = $assumedPath . '.bak';
        writeln("Assumed path {$assumedPath} is a directory, moving to {$backupPath}");
        run("mv {$assumedPath} {$assumedPath}.bak");
    }

    // symlink app to host
    run("ln -s {$fullWebRootPath} {$assumedPath}");

    // reload PHP
    reloadPhp();
});

/** Reload PHP */
desc('Reload PHP');
task('combell:reloadPHP', function () {
    writeln('Waiting for reloadPHP.sh');
    $timestamp = date('YmdHis');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $reloadedFileCheckContent = "{$timestamp} {$releaseRevision}";
    $reloadedFileCheckPath = "{$releasePath}/{$webRoot}/combell-reloaded-check.txt";

    run("echo \"{$reloadedFileCheckContent}\" > {$reloadedFileCheckPath}");

    sleep(5);
    reloadPhp();

    // Try to fetch file in curl request
    $sleep = 5;
    $checkEvery = 30;
    $limit = 120;
    $iterations = 0;

    while (! revisionHasBeenUpdated($reloadedFileCheckContent)) {
        sleep($sleep);
        $secondsElapsed = $sleep * $iterations + $sleep;

        if ($secondsElapsed >= $limit) {
            writeln('Revision was not found after 120 seconds, continuing');
            break;
        }

        if ($secondsElapsed % $checkEvery == 0) {
            writeln("Revision was not found after {$secondsElapsed} seconds, reloading PHP");
            reloadPhp();
        }
        $iterations++;
    }

    writeln('reloadPHP.sh has finished');

    // Clean up
    run("rm {$reloadedFileCheckPath}");
});

/** Reset OPcode cache */
desc('Reset OPcode cache');
task('combell:reset_opcode_cache', function () {
    writeln('Clearing opcache');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $opCacheResetFilePath = "{$releasePath}/{$webRoot}/opcache_reset.{$releaseRevision}.php";

    run("echo \"<?php opcache_reset();\" > {$opCacheResetFilePath}");

    $iterations = 0;
    while (! opcodeCacheHasBeenReset()) {
        $seconds = 5 * ++$iterations;
        sleep($seconds); // 5, 10, 15, 20, 25
        if ($iterations == 5) {
            writeln('Could not clear opcache after 5 iterations.');
            break;
        }

        writeln('Could not clear opcache, retrying.');
    }

    // Clean up
    run("rm {$opCacheResetFilePath}");
});

function url($filePath)
{
    $url = rtrim(get('url'), '/');
    $filePath = ltrim($filePath, '/');

    return "{$url}/{$filePath}";
}

function reloadPhp()
{
    return run('reloadPHP.sh');
}

function revisionHasBeenUpdated($reloadedFileCheckContent)
{
    try {
        $request = Httpie::get(url('/combell-reloaded-check.txt'))
            ->setopt(CURLOPT_SSL_VERIFYPEER, ! input()->getOption('skip-ssl-verify'));

        foreach (requestHeaders() as $key => $value) {
            $request = $request->header($key, $value);
        }

        $reloadedFileContent = $request
            ->send();

        return strpos($reloadedFileContent, $reloadedFileCheckContent) === 0;
    } catch (\Throwable $th) {
        writeln($th->getMessage());

        return false;
    }
}
