<?php

namespace Deployer;

require_once __DIR__ . '/../functions.php';

desc('Set up Wordfence firewall in bedrock / deployer installation');
task('wordfence:firewall_setup', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $sharedWebRootPath = cleanPath($deployPath . "/shared/{$webRoot}");
    $currentWebRootPath = cleanPath($deployPath . "/current/{$webRoot}");
    $userIniFilePath = $sharedWebRootPath . '/.user.ini';
    $wordfenceWafFilePath = $sharedWebRootPath . '/wordfence-waf.php';

    // Create .user.ini file
    run("mkdir -p {$sharedWebRootPath}/ && touch {$userIniFilePath}");
    ob_start();
    echo <<<EOL
        ; Wordfence WAF
        auto_prepend_file = '{$sharedWebRootPath}/wordfence-waf.php'
        ; END Wordfence WAF
        EOL;

    $content = ob_get_clean();

    run("echo \"{$content}\" > {$userIniFilePath}");

    // Create Wordfence WAF file
    run("touch {$wordfenceWafFilePath}");
    ob_start();
    echo <<<EOL
        <?php
        // Before removing this file, please verify the PHP ini setting `auto_prepend_file` does not point to this.

        if (file_exists('{$currentWebRootPath}/app/plugins/wordfence/waf/bootstrap.php')) {
            define('WFWAF_LOG_PATH', '{$currentWebRootPath}/app/wflogs/');
            include_once '{$currentWebRootPath}/app/plugins/wordfence/waf/bootstrap.php';
        }
        EOL;

    $content = ob_get_clean();

    run("echo \"{$content}\" > {$wordfenceWafFilePath}");
});

desc('Wordfence default configuration');
task('wordfence:default_configuration', function () {
    runWpDbQuery('wordfence/activity-report');
    runWpDbQuery('wordfence/banned-urls');
    runWpDbQuery('wordfence/display');
    runWpDbQuery('wordfence/email-alert-preferences');
    runWpDbQuery('wordfence/general');
    runWpDbQuery('wordfence/loginsec');
    runWpDbQuery('wordfence/notifications');
    runWpDbQuery('wordfence/scan');
});
