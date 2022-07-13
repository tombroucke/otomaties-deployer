<?php
namespace Deployer;

/** Reload PHP */
desc('Reload PHP');
task('combell:reloadPHP', function () {
    run('reloadPHP.sh');

    writeln('Waiting for opcache to reload');
    $find = '20220712195907';
    $start = microtime(true);
    while (!revisionHasBeenUpdated($find)) {
        sleep(1);
        info('.');
    }
    $timeElapsed = microtime(true) - $start;
    writeln(sprintf('OPcache reloaded after %ss.', $timeElapsed));

});

function revisionHasBeenUpdated($revision) {
    $revisionFileContent = fetch(rtrim(get('url'), '/') . '/revision.txt');
    return strpos($revisionFileContent, $revision) === 0;
}
