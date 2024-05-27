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

desc('Wordfence default configuration');
task('wordfence:default_configuration', function () {
    runWpQuery('activity-report');
    runWpQuery('banned-urls');
    runWpQuery('display');
    runWpQuery('email-alert-preferences');
    runWpQuery('general');
    runWpQuery('loginsec');
    runWpQuery('notifications');
    runWpQuery('scan');
});

function runWpQuery($filename) {
    $deployPath = get('deploy_path');
    $query = file_get_contents(dirname(__DIR__) . '/snippets/wordfence/' . $filename . '.sql');

    // Extracting placeholders and default values
    preg_match_all('/{{\s(.*?)(?::(.*?))?\s}}/', $query, $matches, PREG_SET_ORDER);

    $url = parse_url(get('url'), PHP_URL_HOST);
    $defaults = [
        'domain' => preg_replace('/\.[^.]*$/', '', $url),
        'domain_extension' => $url,
    ];
    
    foreach($matches as $match) {
        $replace = $match[0];
        $key = $match[1];
        $defaultValue = $match[2] ?? $defaults[$key] ?? '';
        $value = ask("Enter a value for {$key} (default: {$defaultValue})", $defaultValue);
        $query = str_replace($replace, $value, $query);        
    }

    $query = trim(str_replace("'", "\"", $query));
    return run("wp db query '{$query}' --path={$deployPath}/current/www/wp");
}
