<?php
namespace Deployer;

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
    $sleep  = 5;
    $checkEvery = 30;
    $limit = 120;
    $iterations = 0;

    while (!revisionHasBeenUpdated($reloadedFileCheckContent)) {
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
    writeln('Writing opcache_reset file');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $opCacheResetFilePath = "{$releasePath}/{$webRoot}/opcache_reset.{$releaseRevision}.php";

    run("echo \"<?php opcache_reset();\" > {$opCacheResetFilePath}");

    sleep(5);

    fetch(
        url('/opcache_reset.' . get('release_revision') . '.php'),
        'get',
        requestHeaders(),
        info: $info
    );

    if ($info['http_code'] === 200) {
        writeln('OPcode cache has been reset');
    } else {
        writeln('Could not reset OPcode cache');
    }

    // Clean up
    writeln('Deleting opcache_reset file');
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
    run('reloadPHP.sh');
}

function revisionHasBeenUpdated($reloadedFileCheckContent)
{
    $reloadedFileContent = fetch(
        url('/combell-reloaded-check.txt'),
        'get',
        requestHeaders()
    );
    return strpos($reloadedFileContent, $reloadedFileCheckContent) === 0;
}

function requestHeaders()
{
    $headers = [];
    $basicAuthUser = get('basic_auth_user');
    $basicAuthPass = get('basic_auth_pass');

    if ($basicAuthUser && $basicAuthPass) {
        $base64EncodedString = base64_encode("{$basicAuthUser}:{$basicAuthPass}");
        $headers['Authorization'] = "Basic {$base64EncodedString}";
    }
    return $headers;
}
