<?php

namespace Otomaties\Deployer\Recipes\Htaccess;

use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Otomaties\Deployer\cleanPath;
use function Otomaties\Deployer\createFileIfNotExists;

require_once __DIR__.'/../functions.php';

desc('Add .htaccess rule: disable access to sensitive files');
task('htaccess:disable_access_to_sensitive_files', function () {
    appendToHtaccess('snippets/htaccess/sensitive-files.txt');
});

desc('Add .htaccess rule: disable access to blade files');
task('htaccess:disable_access_to_blade_files', function () {
    appendToHtaccess('snippets/htaccess/disable-access-to-blade-files.txt');
});

desc('Add .htaccess rule: disable xmlrpc');
task('htaccess:disable_xmlrpc', function () {
    appendToHtaccess('snippets/htaccess/disable-xmlrpc.txt');
});

desc('Add .htaccess rule: 7G firewall');
task('htaccess:7g_firewall', function () {
    appendToHtaccess('snippets/htaccess/7g-firewall.txt');
});

desc('Add .htaccess rule: Woff2 Expires headers');
task('htaccess:woff2_expires_headers', function () {
    appendToHtaccess('snippets/htaccess/woff2-expires-headers.txt');
});

desc('Add .htaccess rule: text/javascript Expires headers');
task('htaccess:text_javascript_expires', function () {
    appendToHtaccess('snippets/htaccess/text-javascript-expires-headers.txt');
});

desc('Add .htaccess rule: Security headers');
task('htaccess:security_headers', function () {
    appendToHtaccess('snippets/htaccess/security-headers.txt');
});

function appendToHtaccess(string $filepath): void
{
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $htaccessPath = cleanPath("{$deployPath}/shared/{$webRoot}/.htaccess");
    $content = PHP_EOL.'# Otomaties deployer: '.$filepath.PHP_EOL;

    $content .= file_get_contents(dirname(__DIR__).'/'.$filepath);

    if (! test("grep -q {$filepath} {$htaccessPath}")) {
        createFileIfNotExists($htaccessPath);
        // Append content to .htaccess
        $slashedContent = addcslashes($content, '"`$\\');
        run("echo \"{$slashedContent}\" >> {$htaccessPath}");
    }
}

desc('Add all .htaccess rules');
task('htaccess:add_all_rules', [
    'htaccess:disable_access_to_sensitive_files',
    'htaccess:disable_access_to_blade_files',
    'htaccess:disable_xmlrpc',
    'htaccess:7g_firewall',
    'htaccess:woff2_expires_headers',
    'htaccess:text_javascript_expires',
    'htaccess:security_headers',
]);
