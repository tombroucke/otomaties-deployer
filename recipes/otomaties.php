<?php

namespace Deployer;

/* Add revision.txt */
desc('Write timestamp and git commit to file');
task('otomaties:write_revision_to_file', function () {
    $date = date('YmdHis');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $revisionFilePath = "{$releasePath}/{$webRoot}/revision.txt";

    run("echo \"{$date} {$releaseRevision}\" > {$revisionFilePath}");
});

desc('Disable access to sensitive files');
task('otomaties:disable_access_to_sensitive_files', function () {
    appendToHtaccess('snippets/htaccess/sensitive-files.txt');
});

desc('Disable access to blade files');
task('otomaties:disable_access_to_blade_files', function () {
    appendToHtaccess('snippets/htaccess/disable-access-to-blade-files.txt');
});

desc('Disable xmlrpc');
task('otomaties:disable_xmlrpc', function () {
    appendToHtaccess('snippets/htaccess/disable-xmlrpc.txt');
});

desc('7G firewall');
task('otomaties:7g_firewall', function () {
    appendToHtaccess('snippets/htaccess/7g-firewall.txt');
});

desc('Woff2 Expires headers');
task('otomaties:woff2_expires_headers', function () {
    appendToHtaccess('snippets/htaccess/woff2-expires-headers.txt');
});

desc('Security headers');
task('otomaties:security_headers', function () {
    appendToHtaccess('snippets/htaccess/security-headers.txt');
});

function appendToHtaccess($filepath)
{
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $htaccessPath = "{$deployPath}/shared/{$webRoot}/.htaccess";
    $content = PHP_EOL . '# Otomaties deployer: ' . $filepath . PHP_EOL;

    $content .= file_get_contents(dirname(__DIR__) . '/' . $filepath);

    if (!test("grep -q {$filepath} {$deployPath}/shared/{$webRoot}/.htaccess")) {
        createFileIfNotExists($htaccessPath);
        // Append content to .htaccess
        $slashedContent = addcslashes($content, '"`$\\');
        run("echo \"{$slashedContent}\" >> {$htaccessPath}");
    }
}

desc('Builds assets and uploads them to remote server');
task('otomaties:htaccess_rules', [
    'otomaties:disable_access_to_sensitive_files',
    'otomaties:disable_access_to_blade_files',
    'otomaties:disable_xmlrpc',
    'otomaties:7g_firewall',
    'otomaties:woff2_expires_headers',
    'otomaties:security_headers',
]);
