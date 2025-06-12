<?php

namespace Deployer;

require_once __DIR__ . '/../functions.php';

if (! file_exists('config/application.php')) {
    throw new \RuntimeException('`config/application.php` is missing. Please ensure you are in a Bedrock project directory.');
}

$webRoot = \collect([
    'web',
    'www',
])
    ->filter(fn ($dir) => is_dir($dir) && file_exists("{$dir}/wp-config.php"))
    ->first();

if (! $webRoot) {
    throw new \RuntimeException('No web root found. Please ensure you are in a Bedrock project directory.');
}

set('web_root', $webRoot);

/** Shared files */
add('shared_files', [
    '.env',
    '{{ web_root }}/.htaccess',
    '{{ web_root }}/.htpasswd',
    '{{ web_root }}/.user.ini',
    '{{ web_root }}/app/object-cache.php',
    '{{ web_root }}/app/wp-cache-config.php',
]);

/** Shared directories */
add('shared_dirs', [
    '{{ web_root }}/app/blogs.dir',
    '{{ web_root }}/app/ewww',
    '{{ web_root }}/app/fonts',
    '{{ web_root }}/app/languages/wpml',
    '{{ web_root }}/app/uploads',
    '{{ web_root }}/app/wflogs',
    '{{ web_root }}/app/wp-rocket-config',
]);

desc('Create the .env file on the server');
task('bedrock:create_env', function () {
    // Check if url is set
    $url = rtrim(get('url'), '/');
    if (! $url || $url == '') {
        throw new \RuntimeException('Please set the url in your deploy config.');
    }

    $deployPath = get('deploy_path');
    $envPath = "{$deployPath}/shared/.env";
    if (! test("[ -f {$envPath} ]")) {
        createFileIfNotExists($envPath);

        // Keys that require a salt token
        $saltKeys = [
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
        $wpEnv = askChoice(get('stage') . ' WP_ENV', [
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

        foreach ($saltKeys as $key) {
            echo $key . "='" . generateSalt() . "'" . PHP_EOL;
        }

        $content = ob_get_clean();

        run("echo \"{$content}\" > {$deployPath}/shared/.env");
    } else {
        writeln('<comment>.env file already exists</comment>');
    }
})->oncePerNode();
