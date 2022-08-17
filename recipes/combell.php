<?php
namespace Deployer;

/** Reload PHP */
desc('Reload PHP');
task('combell:reloadPHP', function () {
    sleep(5);
    reloadPhp();

    writeln('Waiting for Combell to reload');
    $revision = get('release_revision');
    writeln('searching for revision ' . $revision);

    $sleep = 10;
    $iterations = 0;
    $start = microtime(true);
    while (!revisionHasBeenUpdated($revision)) {
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
});

/** Reset OPcode cache */
desc('Reset OPcode cache');
task('combell:reset_opcode_cache', function () {
    writeln('Writing opcache_reset file');
    run('echo "<?php opcache_reset();" > {{release_path}}/www/opcache_reset.{{release_revision}}.php');
    fetch(rtrim(get('url'), '/') . '/opcache_reset.' . get('release_revision') . '.php', info: $info);

    if ($info['http_code'] === 200) {
        writeln('OPcode cache has been reset');
        writeln('Deleting opcache_reset file');
        run('rm {{release_path}}/www/opcache_reset.{{release_revision}}.php');
    } else {
        writeln('Could not reset OPcode cache');
    }
});

function reloadPhp()
{
    run('reloadPHP.sh');
}

function revisionHasBeenUpdated($revision)
{
    $revisionFileContent = fetch(rtrim(get('url'), '/') . '/revision.txt');
    return strpos($revisionFileContent, $revision) === 15;
}
