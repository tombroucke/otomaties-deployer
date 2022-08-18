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

    $sleep = 10;
    $iterations = 0;
    $start = microtime(true);
    writeln('searching for ' . $reloadedFileCheckContent);

    while (!revisionHasBeenUpdated($reloadedFileCheckContent)) {
        sleep($sleep);
        switch ($iterations) {
            case 3:
                writeln('Revision was not found after 30 seconds, reloading PHP');
                reloadPhp();
                break;
            case 6:
                writeln('Revision was not found after 60 seconds, reloading PHP');
                reloadPhp();
                break;
            case 9:
                writeln('Revision was not found after 90 seconds, reloading PHP');
                reloadPhp();
                break;
        }
        if ($iterations == 12) {
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
