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
        $reloadedFileContent = fetch(
            url('/combell-reloaded-check.txt'),
            'get',
            requestHeaders()
        );
        return strpos($reloadedFileContent, $reloadedFileCheckContent) === 0;
    } catch (\Throwable $th) {
        writeln($th->getMessage());
        return false;
    }
}
