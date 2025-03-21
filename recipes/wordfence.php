<?php

namespace Deployer;

desc('Set up Wordfence firewall in bedrock / deployer installation');
task('wordfence:firewall_setup', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $sharedWebrootPath = $deployPath . "/shared/{$webRoot}";
    $userIniFilePath = $sharedWebrootPath . '/.user.ini';
    $wordfenceWafFilePath = $sharedWebrootPath . '/wordfence-waf.php';

    // Create .user.ini file
    run("mkdir -p {$sharedWebrootPath}/ && touch {$userIniFilePath}");
    ob_start();
    echo <<<EOL
        ; Wordfence WAF
        auto_prepend_file = '{$sharedWebrootPath}/wordfence-waf.php'
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

        if (file_exists('{$deployPath}/current/{$webRoot}/app/plugins/wordfence/waf/bootstrap.php')) {
            define('WFWAF_LOG_PATH', '{$deployPath}/current/{$webRoot}/app/wflogs/');
            include_once '{$deployPath}/current/{$webRoot}/app/plugins/wordfence/waf/bootstrap.php';
        }
        EOL;

    $content = ob_get_clean();

    run("echo \"{$content}\" > {$wordfenceWafFilePath}");
});

desc('Wordfence default configuration');
task('wordfence:default_configuration', function () {
    runWpQuery('wordfence/activity-report');
    runWpQuery('wordfence/banned-urls');
    runWpQuery('wordfence/display');
    runWpQuery('wordfence/email-alert-preferences');
    runWpQuery('wordfence/general');
    runWpQuery('wordfence/loginsec');
    runWpQuery('wordfence/notifications');
    runWpQuery('wordfence/scan');
});
