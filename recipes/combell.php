<?php
namespace Deployer;

/** Reload PHP */
desc('Reload PHP');
task('combell:reloadPHP', function () {
    sleep(5);
    reloadPhp();

    writeln('Waiting for Combell to reload');
    $reloadedFileCheckContent = date('YmdHis') . ' ' . get('release_revision');
    run('echo "' . $reloadedFileCheckContent . '" > {{release_path}}/www/combell-reloaded-check.txt');

    $sleep = 5;
    $checkEvery = 30;
    $limit = 120;
    $iterations = 0;
    $start = microtime(true);

    while (!revisionHasBeenUpdated($reloadedFileCheckContent)) {
        sleep($sleep);
        $secondsElapsed = $checkEvery * $iterations + $checkEvery;

        if ($checkEvery % $sleep == 0) {
            writeln('Revision was not found after ' . $secondsElapsed . ' seconds, reloading PHP');
            reloadPhp();
        }

        if ($secondsElapsed == $limit) {
            writeln('Revision was not found after 120 seconds, continuing');
            break;
        }
        $iterations++;
    }
    $timeElapsed = microtime(true) - $start;
    writeln(sprintf('Combell reloaded after %ss.', $timeElapsed));
    run('rm {{release_path}}/www/combell-reloaded-check.txt');
});

/** Reset OPcode cache */
desc('Reset OPcode cache');
task('combell:reset_opcode_cache', function () {
    writeln('Writing opcache_reset file');
    run('echo "<?php opcache_reset();" > {{release_path}}/www/opcache_reset.{{release_revision}}.php');

    sleep(5);

    fetch(rtrim(get('url'), '/') . '/opcache_reset.' . get('release_revision') . '.php', info: $info);

    if ($info['http_code'] === 200) {
        writeln('OPcode cache has been reset');
    } else {
        writeln('Could not reset OPcode cache');
    }

    writeln('Deleting opcache_reset file');
    run('rm {{release_path}}/www/opcache_reset.{{release_revision}}.php');
});

function reloadPhp()
{
    run('reloadPHP.sh');
}

function revisionHasBeenUpdated($reloadedFileCheckContent)
{
    $reloadedFileContent = fetch(rtrim(get('url'), '/') . '/combell-reloaded-check.txt');
    return strpos($reloadedFileContent, $reloadedFileCheckContent) === 0;
}
