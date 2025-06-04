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
require 'contrib/cachetool.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require 'vendor/tombroucke/otomaties-deployer/deploy.php';

/** Config */
set('web_root', 'web');
set('application', '');
set('repository', '');
set('sage/theme_path', get('web_root') . '/app/themes/themename');
set('sage/build_command', 'build --clean --flush'); // build --clean for bud, build:production for mix

/** Hosts */
host('production')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', '')
    ->set('remote_user', 'examplebe')
    ->set('branch', 'main')
    ->set('deploy_path', '/data/sites/web/examplebe/app/main');

host('staging')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', '')
    ->set('basic_auth_user', $_SERVER['BASIC_AUTH_USER'] ?? '')
    ->set('basic_auth_pass', $_SERVER['BASIC_AUTH_PASS'] ?? '')
    ->set('remote_user', 'examplebe')
    ->set('branch', 'staging')
    ->set('deploy_path', '/data/sites/web/examplebe/app/staging');

/** Install theme dependencies */
after('deploy:vendors', 'sage:vendors');

/** Push theme assets */
after('deploy:update_code', 'sage:compile_and_upload_assets');

/** Write revision to file */
after('deploy:update_code', 'otomaties:write_revision_to_file');

/** Reload Combell */
after('deploy:symlink', 'combell:reloadPHP');

/** Clear OPcode cache */
after('deploy:symlink', 'cachetool:clear:opcache');

/** Cache ACF fields */
after('deploy:symlink', 'acorn:acf_cache');

/** Optimize acorn */
after('deploy:symlink', 'acorn:optimize');

/** Reload cache & preload */
after('deploy:symlink', 'wp_rocket:clear_cache');

/** Reload cache & preload */
after('deploy:symlink', 'wp_rocket:preload_cache');

/** Remove unused themes */
after('deploy:cleanup', 'cleanup:unused_themes');

/** Unlock deploy */
after('deploy:failed', 'deploy:unlock');
```

## WordPress

```php
/** Flush object cache */
after('deploy:symlink', fn () => runWpQuery('cache flush'));
```

## WooCommerce

```php
/** Update WooCommerce tables */
after('deploy:symlink', 'woocommerce:update_database');
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
dep otomaties:htaccess_rules
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
