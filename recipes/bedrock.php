<?php
namespace Deployer;

desc('Makes sure, .env file for Bedrock is available');
task('bedrock:create_env', function () {

    // Check if url is set
    $url = rtrim(get('url'), '/');
    if (!$url || $url == '') {
        writeln('url is empty');
        exit;
    }

    $deployPath = get('deploy_path');
    if (!test("[ -f {$deployPath}/shared/.env ]")) {
        run("mkdir -p {$deployPath}/shared/ && touch {$deployPath}/shared/.env");

        // Keys that require a salt token
        $salt_keys = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
        ];
    
        writeln('<comment>Generating .env file</comment>');
    
        // Ask for credentials
        $dbName = ask(get('stage') . ' DB_NAME');
        $dbUser = ask(get('stage') . ' DB_USER');
        $dbPass = askHiddenResponse(get('stage') . ' DB_PASSWORD');
        $dbHost = ask(get('stage') . ' DB_HOST', $dbName . '.db.webhosting.be');
        $wpEnv  = askChoice(get('stage') . ' WP_ENV', [
            'development' => 'development',
            'staging' => 'staging',
            'production' => 'production',
        ], 'production');
    
        ob_start();
    
        echo <<<EOL
        DB_NAME='{$dbName}'
        DB_USER='{$dbUser}'
        DB_PASSWORD='{$dbPass}'

        # Optionally, you can use a data source name (DSN)
        # When using a DSN, you can remove the DB_NAME, DB_USER, DB_PASSWORD, and DB_HOST variables
        # DATABASE_URL='mysql://root:root@database_host:database_port/local'
        
        # Optional database variables
        DB_HOST='{$dbHost}'
        # DB_PREFIX='wp_'
        
        WP_ENV='{$wpEnv}'
        WP_HOME='{$url}'
        WP_SITEURL='{$url}/wp'

        # Specify optional debug.log path
        # WP_DEBUG_LOG='/path/to/debug.log'
        
        # Generate your keys here: https://roots.io/salts.html
        EOL;
    
        echo PHP_EOL;

        foreach ($salt_keys as $key) {
            echo $key . "='" . generate_salt() . "'" . PHP_EOL;
        }
    
        $content = ob_get_clean();
    
        run("echo \"{$content}\" > {$deployPath}/shared/.env");
    } else {
        writeln('<comment>.env file already exists</comment>');
    }
});

desc('Upload auth.json to remote');
task('bedrock:upload_auth_json', function () {
    $authJsonPath = 'auth.json';

    if (file_exists($authJsonPath)) {
        upload($authJsonPath, '{{release_path}}/auth.json');
    }
});

desc('Remove auth.json from remote');
task('bedrock:remove_auth_json', function () {
    run("rm {{release_path}}/auth.json");
});

function generate_salt()
{
    $chars              = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_[]{}<>~+=,.;:/?|';
    $char_option_length = strlen($chars) - 1;

    $password = '';
    for ($i = 0; $i < 64; $i ++) {
        $password .= substr($chars, random_int(0, $char_option_length), 1);
    }

    return $password;
}
