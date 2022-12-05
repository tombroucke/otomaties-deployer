# Otomaties Deployer

## Installation

```
composer require tombroucke/otomaties-deployer --dev
```

## Example deploy.php file

```php
<?php
namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require 'vendor/tombroucke/otomaties-deployer/deploy.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/acorn.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/auth.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/bedrock.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/cleanup.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/combell.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/composer.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/otomaties.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/sage.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/woocommerce.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/wordfence.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/wp-rocket.php';


/** Config */
set('application', '');
set('repository', '');
set('sage/theme_path', get('web_root') . '/app/themes/themename');
set('sage/build_command', 'build --clean'); // build --clean for bud, build:production for webpack mix
set('sage/public_dir', 'public'); // public for bud, dist for webpack mix

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
    ->set('basic_auth_user', $_SERVER['BASIC_AUTH_USER'])
    ->set('basic_auth_pass', $_SERVER['BASIC_AUTH_PASS'])
    ->set('remote_user', 'examplebe')
    ->set('branch', 'staging')
    ->set('deploy_path', '/data/sites/web/examplebe/app/staging');

host('acc')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', '')
    ->set('basic_auth_user', $_SERVER['BASIC_AUTH_USER'])
    ->set('basic_auth_pass', $_SERVER['BASIC_AUTH_PASS'])
    ->set('remote_user', 'examplebe')
    ->set('branch', 'acc')
    ->set('deploy_path', '/data/sites/web/examplebe/app/acc');

/** Notify deploy started */
before('deploy', 'slack:notify');

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

/** Fetch Google fonts */
after('deploy:symlink', 'acorn:fetch_google_fonts');

/** Reload cache & preload */
after('deploy:symlink', 'wp_rocket:clear_cache');

/** Reload cache & preload */
after('deploy:symlink', 'wp_rocket:preload_cache');

/** Remove unused themes */
after('deploy:cleanup', 'cleanup:unused_themes');

/** Notify success */
after('deploy:success', 'slack:notify:success');

/** Unlock deploy */
after('deploy:failed', 'deploy:unlock');

/** Notify failure */
after('deploy:failed', 'slack:notify:failure');
```
## WooCommerce
```php
/** Update WooCommerce tables */
after('deploy:symlink', 'woocommerce:update_database');
```

## WordPress cache
```php
/** Update WooCommerce tables */
after('deploy:symlink', 'wordpress:clear_cache');
```

## Extra commands

### Enable basic auth on host:

```bash
dep auth:password_protect_stage staging
```

### Create bedrock .env file

```bash
dep bedrock:create_env staging
```

### Add repository authentication to remote server


```bash
dep composer:add_remote_repository_authentication
```

### Setup Wordfence firewall for Bedrock / deployer


```bash
dep wordfence:firewall_setup
```
