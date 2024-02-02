<?php
namespace Deployer;

desc('Set up Wordfence firewall in bedrock / deployer installation');
task('wordfence:firewall_setup', function () {
    $deployPath = get('deploy_path');
    $sharedWwwPath = $deployPath . '/shared/www';
    $userIniFilePath = $sharedWwwPath . '/.user.ini';
    $wordfenceWafFilePath = $sharedWwwPath . '/wordfence-waf.php';

    // Create .user.ini file
    run("mkdir -p {$sharedWwwPath}/ && touch {$userIniFilePath}");
    ob_start();
    echo <<<EOL
        ; Wordfence WAF
        auto_prepend_file = '{$sharedWwwPath}/wordfence-waf.php'
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

        if (file_exists('{$deployPath}/current/www/app/plugins/wordfence/waf/bootstrap.php')) {
            define('WFWAF_LOG_PATH', '{$deployPath}/current/www/app/wflogs/');
            include_once '{$deployPath}/current/www/app/plugins/wordfence/waf/bootstrap.php';
        }
        EOL;
    
    $content = ob_get_clean();

    run("echo \"{$content}\" > {$wordfenceWafFilePath}");
});
