<?php
namespace Deployer;

/** Reset OPcode cache */
desc('Reset OPcode cache');
task('opcode:reset_cache', function () {
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $opCacheResetFilePath = "{$releasePath}/{$webRoot}/opcache_reset.{$releaseRevision}.php";

    run("echo \"<?php opcache_reset();\" > {$opCacheResetFilePath}");

    $iterations = 0;
    while (!opcodeCacheHasBeenReset()) {
        $seconds = 5*++$iterations;
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

function opcodeCacheHasBeenReset()
{
    try {
        fetch(
            url('/opcache_reset.' . get('release_revision') . '.php'),
            'get',
            requestHeaders(),
            info: $info
        );
        return $info['http_code'] === 200;
    } catch (\Throwable $th) {
        writeln($th->getMessage());
        return false;
    }
}
