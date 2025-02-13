<?php

namespace Deployer;

desc('Pull Database');
task('db:pull', function () {
    $localUrl = ask('Local URL', $_SERVER['WP_HOME']);
    $date = date('Y-m-d_H-i-s');
    $uniqueId = uniqid();
    $dbFilename = "db-{$date}-{$uniqueId}.sql";
    $remoteDbFilePath = get('deploy_path') . '/' . $dbFilename;
    $tmpDir = sys_get_temp_dir();
    $tmpFile = "{$tmpDir}/{$dbFilename}";

    try {
        // Export database on server
        $exportCommand = "wp db export {$remoteDbFilePath} --path={{deploy_path}}/current/{{web_root}}/wp";
        writeln("Exporting database to {$remoteDbFilePath} using command: {$exportCommand}");
        run($exportCommand);

        // Download database
        writeln("Downloading database to {$tmpFile}");
        download(
            $remoteDbFilePath,
            $tmpFile,
            [
                'progress_bar' => true,
                'display_stats' => true,
            ]
        );

        // import database locally
        $importCommand = "wp db import {$tmpFile} --allow-root --path={{web_root}}/wp";
        writeln('Importing database locally using command: ' . $importCommand);
        runLocally($importCommand);

        // Replace url
        $searchReplaceCommand = "wp search-replace {{url}} {$localUrl} --allow-root --path={{web_root}}/wp";
        writeln('Replacing urls locally using command: ' . $searchReplaceCommand);
        runLocally($searchReplaceCommand);
    } catch (\Throwable $th) {
        $message = $th->getMessage();
        writeln("<fg=red;options=bold>error</> <error>$message</error>");
    }

    // Remove export file
    writeln("Removing export file {$remoteDbFilePath}");
    if (test("[ -f {$remoteDbFilePath} ]")) {
        run("rm {$remoteDbFilePath}");
    } else {
        writeln("<fg=yellow;options=bold>warning</> Export file <comment>{$remoteDbFilePath}</comment> not found, not removing");
    }

    // Remove tmp file
    writeln("Removing tmp file {$tmpFile}");
    if (file_exists($tmpFile)) {
        runLocally("rm {$tmpFile}");
    } else {
        writeln("<fg=yellow;options=bold>warning</> Tmp file <comment>{$tmpFile}</comment> not found, not removing");
    }
});

desc('Download Database');
task('db:download', function () {
    $date = date('Y-m-d_H-i-s');
    $uniqueId = uniqid();
    $dbFilename = createSlug(get('application')) . "-{$date}-{$uniqueId}.sql";
    $remoteDbFilePath = get('deploy_path') . '/' . $dbFilename;
    $downloadDir = ask('Download directory', getenv('HOME') . '/Downloads');
    $tmpFile = "{$downloadDir}/{$dbFilename}";

    try {
        // Export database on server
        $exportCommand = "wp db export {$remoteDbFilePath} --path={{deploy_path}}/current/{{web_root}}/wp";
        writeln("Exporting database to {$remoteDbFilePath} using command: {$exportCommand}");
        run($exportCommand);

        // Download database
        writeln("Downloading database to {$tmpFile}");
        download(
            $remoteDbFilePath,
            $tmpFile,
            [
                'progress_bar' => true,
                'display_stats' => true,
            ]
        );
    } catch (\Throwable $th) {
        $message = $th->getMessage();
        writeln("<fg=red;options=bold>error</> <error>$message</error>");
    }

    // Remove export file
    writeln("Removing export file {$remoteDbFilePath}");
    if (test("[ -f {$remoteDbFilePath} ]")) {
        run("rm {$remoteDbFilePath}");
    } else {
        writeln("<fg=yellow;options=bold>warning</> Export file <comment>{$remoteDbFilePath}</comment> not found, not removing");
    }

    runLocally("open {$downloadDir}");
});

desc('Push Database');
task('db:push', function () {
    if (! askConfirmation('<bg=red;fg=white;options=bold>Warning</><bg=red;fg=white>, this will overwrite the database on the remote server. Are you sure you want to continue?</>', false)) {
        writeln('Aborted');

        return;
    }

    $localUrl = ask('Local URL', $_SERVER['WP_HOME']);
    $date = date('Y-m-d_H-i-s');
    $uniqueId = uniqid();
    $dbFilename = "db-{$date}-{$uniqueId}.sql";
    $remoteDbFilePath = get('deploy_path') . '/' . $dbFilename;
    $tmpDir = sys_get_temp_dir();
    $tmpFile = "{$tmpDir}/{$dbFilename}";

    try {
        // Export database locally
        writeln("Exporting database to {$tmpFile}");
        runLocally("wp db export {$tmpFile} --allow-root --path={{web_root}}/wp");

        // Upload database
        writeln("Uploading database to {$remoteDbFilePath}");
        upload(
            $tmpFile,
            $remoteDbFilePath,
            [
                'progress_bar' => true,
                'display_stats' => true,
            ]
        );

        run(replaceCollation($remoteDbFilePath));

        // Import database on server
        writeln('Importing database');
        run("wp db import {$remoteDbFilePath} --path={{deploy_path}}/current/{{web_root}}/wp");

        // Replace url
        writeln('Replacing urls');
        run("wp search-replace {$localUrl} {{url}} --path={{deploy_path}}/current/{{web_root}}/wp");
    } catch (\Throwable $th) {
        $message = $th->getMessage();
        writeln("<fg=red;options=bold>error</> <error>$message</error>");
    }

    // Remove export file
    writeln("Removing export file {$tmpFile}");
    if (file_exists($tmpFile)) {
        runLocally("rm {$tmpFile}");
    } else {
        writeln("<fg=yellow;options=bold>warning</> Export file <comment>{$tmpFile}</comment> not found, not removing");
    }

    // Remove tmp file
    writeln("Removing tmp file {$remoteDbFilePath}");
    if (test("[ -f {$remoteDbFilePath} ]")) {
        run("rm {$remoteDbFilePath}");
    } else {
        writeln("<fg=yellow;options=bold>warning</> Tmp file <comment>{$remoteDbFilePath}</comment> not found, not removing");
    }
});

function replaceCollation($filePath)
{
    return "sed -i 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' {$filePath}";
}
