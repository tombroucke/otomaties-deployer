<?php

namespace Deployer;

require_once __DIR__ . '/../functions.php';

set('wordfence/config', collect([
    // activity-report
    'email_summary_dashboard_widget_enabled' => '1',
    'email_summary_enabled' => null,
    // banned-urls
    'bannedURLs' => collect([
        '/*.sql',
        '/.env',
        '/*.env',
        '/wp-config.php*',
        '/wp-config.txt',
        '/*.old',
        '/*.ini',
        '/.git',
        '/vendor*',
        '/*.log',
        '/wp-vcd.php',
        '/xmlrpc.php',
        '/admin*',
        '/wanted/adm.php',
        '/pvp*',
        '/pvp.php',
        '/admiinn',
        '/adm.php',
        '/*.temp',
        '/*.bak',
        '/adminer.php',
        '/adminer*',
        '/phpMyAdmin*',
        '/pma',
        '/*wp-config.php*',
        '/*wlwmanifest.xml',
        '/installer.php',
        '/.env.local',
        '/.aws/*',
        '/_cat/indices',
        '/api/php.php',
        '/.env.bak',
        '/.env.php',
        '/.env.backup',
        '/.env.prod',
        '/.env.test',
        '/.env.dev',
        '/.env.production',
        '/.env.staging',
        '/telescope/requests',
        '/.vscode',
        '/pma',
        '/PMA',
        '/swagger',
        '/recentservers.xml',
        '/sftp-config.json',
        '/aws.json',
        '/aws.yml',
        '/config/default.json',
        '/filezilla.xml',
        '/FileZilla.xml',
        '/sitemanager.xml',
        '/app.ini',
        '/db.zip',
        '/production.ini',
        '/config.ini',
        '/database.tar.gz',
        '/database.sql.gz',
        '/.docker',
        '/db.sql.gz',
        '/Dockerfile*',
    ])->implode(','),
    // display
    'displayAutomaticBlocks' => '1',
    'displayTopLevelBlocking' => '1',
    'displayTopLevelLiveTraffic' => '0',
    'displayTopLevelOptions' => '1',
    'liveTraf_displayExpandedRecords' => '0',
    // email-alert-preferences
    'alertEmails' => '{{ wordfence_alert_email }}',
    'alertOn_adminLogin' => '0',
    'alertOn_block' => '0',
    'alertOn_breachLogin' => '0',
    'alertOn_firstAdminLoginOnly' => '0',
    'alertOn_firstNonAdminLoginOnly' => '0',
    'alertOn_loginLockout' => '0',
    'alertOn_lostPasswdForm' => '0',
    'alertOn_nonAdminLogin' => '0',
    'alertOn_scanIssues' => '1',
    'alertOn_severityLevel' => '75',
    'alertOn_throttle' => '0',
    'alertOn_update' => '0',
    'alertOn_wafDeactivated' => '1',
    'alertOn_wordfenceDeactivated' => '1',
    'alert_maxHourly' => '0',
    'notification_securityAlerts' => '1',
    'wafAlertInterval' => '600',
    'wafAlertOnAttacks' => '0',
    'wafAlertThreshold' => '100',
    'wafAlertWhitelist' => null,
    // general
    'other_hideWPVersion' => '1',
    'disableCodeExecutionUploads' => '1',
    'disableCodeExecutionUploadsPHP7Migrated' => '1',
    'autoUpdate' => '1',
    'liveActivityPauseEnabled' => '1',
    'other_WFNet' => '1',
    // loginsec
    'loginSec_maxFailures' => '{{ wordfence_max_login_failures:3 }}',
    'loginSec_maxForgotPasswd' => '{{ wordfence_max_forgot_password:3 }}',
    'loginSec_userBlacklist' => collect([
        'admin',
        'administrator',
        'webmaster',
        'editor',
        'wpadmin',
        'wwwadmin',
        'wpenginesupport',
        'itsme',
        'hostingadmin',
        'info-bold-themes-com',
        '{{ wordfence_domain_no_extension }}',
        '{{ wordfence_domain_extension }}',
    ])->implode('\n'),
    // notifications
    'notification_blogHighlights' => '0',
    'notification_productUpdates' => '0',
    'notification_promotions' => '0',
    'notification_scanStatus' => '0',
    'notification_securityAlerts' => '1',
    'notification_updatesNeeded' => '0',
    // scan
    'scansEnabled_checkGSB' => '1',
    'scansEnabled_checkHowGetIPs' => '1',
    'scansEnabled_checkReadableConfig' => '1',
    'scansEnabled_comments' => '1',
    'scansEnabled_core' => '1',
    'scansEnabled_coreUnknown' => '1',
    'scansEnabled_diskSpace' => '1',
    'scansEnabled_fileContents' => '1',
    'scansEnabled_fileContentsGSB' => '1',
    'scansEnabled_geoipSupport' => '1',
    'scansEnabled_highSense' => '1',
    'scansEnabled_malware' => '1',
    'scansEnabled_oldVersions' => '1',
    'scansEnabled_options' => '1',
    'scansEnabled_passwds' => '1',
    'scansEnabled_plugins' => '1',
    'scansEnabled_posts' => '1',
    'scansEnabled_scanImages' => '1',
    'scansEnabled_suspectedFiles' => '1',
    'scansEnabled_suspiciousAdminUsers' => '1',
    'scansEnabled_suspiciousOptions' => '1',
    'scansEnabled_themes' => '1',
    'scansEnabled_wafStatus' => '1',
    'scansEnabled_wpscan_directoryListingEnabled' => '1',
    'scansEnabled_wpscan_fullPathDisclosure' => '1',
    'scanType' => 'highsensitivity',
    'scan_exclude' => '',
    'scan_force_ipv4_start' => '0',
    'scan_include_extra' => '',
    'scan_maxDuration' => '',
    'scan_maxIssues' => '1000',
    'scan_max_resume_attempts' => '2',
]));

desc('Set up Wordfence firewall in Bedrock installation');
task('wordfence:firewall_setup', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $sharedWebRootPath = cleanPath($deployPath . "/shared/{$webRoot}");
    $currentWebRootPath = cleanPath($deployPath . "/current/{$webRoot}");
    $userIniFilePath = $sharedWebRootPath . '/.user.ini';
    $wordfenceWafFilePath = $sharedWebRootPath . '/wordfence-waf.php';

    // Create .user.ini file
    createFileIfNotExists($userIniFilePath);

    ob_start();
    echo <<<EOL
        ; Wordfence WAF
        auto_prepend_file = '{$sharedWebRootPath}/wordfence-waf.php'
        ; END Wordfence WAF
        EOL;

    $content = ob_get_clean();

    run("echo \"{$content}\" > {$userIniFilePath}");

    // Create Wordfence WAF file
    createFileIfNotExists($wordfenceWafFilePath);
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
    $webRoot = get('web_root');
    $deployPath = get('deploy_path');

    $query = get('wordfence/config')
        ->map(fn ($value, $key) => '\wfConfig::set(\"' . $key . '\", \"' . replacePlaceholders($value) . '\");');

    $notInstalledMsg = 'Wordfence not activated';

    $result = runWpQuery(
        cmd: 'wp eval "if (class_exists(\'wfConfig\')) {' . $query->implode('') . '} else { echo \"' . $notInstalledMsg . '\"; }"',
        path: cleanPath("{$deployPath}/current/{$webRoot}/wp")
    );

    if (str_contains($result, $notInstalledMsg)) {
        writeln('<error>Wordfence is not installed or activated. Please install and activate Wordfence first.</error>');
    } else {
        writeln('<info>✓</info> Wordfence configuration set successfully.');
    }
});
