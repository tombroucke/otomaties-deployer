# Otomaties Deployer

## Installation

```
composer require tombroucke/otomaties-deployer --dev
```

## Options

Use `dep deploy production --skip-ssl-verify` to deploy a website without a SSL certificate

## Example deploy.php file

```php
<?php

namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';
require_once 'contrib/cachetool.php';
require_once 'recipe/composer.php';

(\Dotenv\Dotenv::createImmutable(__DIR__))
    ->load();

collect([
    'functions.php',
    'recipes/auth.php',
    'recipes/bedrock.php',
    'recipes/combell.php',
    'recipes/composer.php',
    'recipes/database.php',
    'recipes/opcode.php',
    'recipes/otomaties.php',
    'recipes/sage.php',
    'recipes/wordfence.php',
    'recipes/wp.php',
])
    ->map(fn ($file) => __DIR__ . '/vendor/tombroucke/otomaties-deployer/' . $file)
    ->filter(fn ($file) => file_exists($file))
    ->each(fn ($file) => require_once $file);

/** Config */
set('application', 'example.com');
set('repository', 'git@github.com:username/example.com.git');

/** Sage */
set('sage/build_command', 'build'); // build:production for mix
set('sage/theme_path', 'app/themes/example');

/** Hosts */
host('production')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', 'https://example.com')
    ->set('remote_user', 'examplecom')
    ->set('branch', 'main')
    ->set('deploy_path', '/data/sites/web/examplecom/app/main');

host('staging')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', 'https://staging.example.com')
    ->set('basic_auth_user', env('BASIC_AUTH_USER'))
    ->set('basic_auth_pass', env('BASIC_AUTH_PASS'))
    ->set('remote_user', 'examplecom')
    ->set('branch', 'staging')
    ->set('deploy_path', '/data/sites/web/examplecom/app/staging');

/** Check if everything is set for sage */
before('deploy:prepare', 'sage:check');

/** Upload auth.json */
before('deploy:vendors', 'composer:upload_auth_json');

/** Remove auth.json */
after('deploy:vendors', 'composer:remove_auth_json');

/** Install theme dependencies */
after('deploy:vendors', 'sage:vendors');

/** Push theme assets */
after('deploy:update_code', 'sage:compile_and_upload_assets');

/** Write revision to file */
after('deploy:update_code', 'otomaties:write_revision_to_file');

/** Reload Combell */
after('deploy:symlink', 'combell:reloadPHP');

/** Clear OPcode cache */
after('deploy:symlink', 'combell:reset_opcode_cache');

/** Cache ACF fields */
after('deploy:symlink', 'wp:acorn:acf:cache');

/** Optimize acorn */
after('deploy:symlink', 'wp:acorn:optimize');

/** Reload cache & preload */
after('deploy:symlink', 'wp:rocket:clean');

/** Reload cache & preload */
after('deploy:symlink', 'wp:rocket:preload');

/** Remove unused themes */
after('deploy:cleanup', 'wp:remove_unused_themes');

/** Unlock deploy */
after('deploy:failed', 'deploy:unlock');

/** Aliases */
task('wp:acorn:acf:cache', function () {
    runWpQuery('wp acorn acf:cache');
});

task('wp:acorn:optimize', function () {
    runWpQuery('wp acorn optimize');
});

task('wp:rocket:clean', function () {
    runWpQuery('wp rocket regenerate --file=advanced-cache && wp rocket clean --confirm');
});

task('wp:rocket:preload', function () {
    runWpQuery('wp rocket preload');
});
```

## Runcloud Hub

```php
/** Update dropin */
after('deploy:symlink', 'wp:runcloud-hub:update-dropin');

task('wp:runcloud-hub:update-dropin', function () {
    runWpQuery('wp runcloud-hub update-dropin');
});
```

```php
/** Purge all caches */
after('deploy:symlink', 'wp:runcloud-hub:purgeall');

task('wp:runcloud-hub:purgeall', function () {
    runWpQuery('wp runcloud-hub purgeall');
});
```

## WordPress

```php
/** Flush object cache */
after('deploy:symlink', 'wp:cache:flush');

task('wp:cache:flush', function () {
    runWpQuery('wp cache flush');
});
```

## WooCommerce

```php
/** Update WooCommerce tables */
after('deploy:symlink', 'wp:wc:update');

task('wp:wc:update', function () {
    runWpQuery('wp wc update');
});
```

## Extra commands

### Initial setup

#### Symlink hosts on Combell

```bash
dep combell:host_symlink production
```

#### Create bedrock .env file

```bash
dep bedrock:create_env staging
```

#### Enable basic auth on host:

```bash
dep auth:password_protect_stage staging
```

#### Add repository authentication to remote server

```bash
dep composer:add_remote_repository_authentication
```

### Security

#### Setup Wordfence firewall for Bedrock / deployer

```bash
dep wordfence:firewall_setup
```

#### Set default Wordfence configuration

> [!WARNING]
> The website needs to be be fully installed before you can run this command

```bash
dep wordfence:default_configuration
```

#### Add .htaccess rules for security

```bash
dep htaccess:add_all_rules
```

### Database handling

#### Pull database from production

```bash
dep db:pull production
```

#### Download database

```bash
dep db:download production
```

#### Push database to staging

```bash
dep db:push staging
```

### WordPress

#### WP CLI

```bash
dep wp:cli
```

##### Install packages

```bash
dep wp:cli --cmd="wp package install {package_name}"
```

```bash
dep wp:cli --cmd="wp package install tombroucke/wp-rocket-cli" # Install WP Rocket CLI package
```

```bash
dep wp:cli --cmd="wp package install aaemnnosttv/wp-cli-login-command" # Install login package
```

##### Flush Object Cache

```bash
dep wp:cli --cmd="wp cache flush" # Flush cache
```

##### Capabilities

```bash
dep wp:cli --cmd="wp user add-cap {login} edit_theme_options" # Allow user to update theme options
```

```bash
dep wp:cli --cmd="wp cap add editor edit_theme_options" # Allow editor to update theme options
```

```bash
dep wp:cli --cmd="wp cap add editor manage_instagram_feed_options" # Allow editor to manage smash balloon instagram feed
```

```bash
dep wp:cli --cmd="wp cap add editor gform_full_access" # Allow editor access to Gravity Forms
```

##### Login

```bash
dep wp:cli --cmd="wp login create {login}" # Create login
```

#### Set admin email

```bash
dep wp:cli --cmd="wp option update admin_email {emailaddress} --autoload=yes" # Create login
```

## Upgrading

### From v1.x to v2.x

There is a script included to automatically update from v1.x to v2.x. Require this script in your old deploy.php script, and we will try to fix the file automatically. Run `dep` after adding this line.

```php
require 'vendor/tombroucke/otomaties-deployer/update/2.x.php';
```
