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
require 'vendor/tombroucke/otomaties-deployer/recipes/cleanup.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/combell.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/google-fonts.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/revision.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/sage.php';
require 'vendor/tombroucke/otomaties-deployer/recipes/wp-rocket.php';

/** Config */
set('application', '');
set('repository', '');
set('sage/theme_path', 'www/app/themes/themename');
set('sage/build_command', 'build --clean'); // build --clean for bud, build:production for mix

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
    ->set('remote_user', 'examplebe')
    ->set('branch', 'staging')
    ->set('deploy_path', '/data/sites/web/examplebe/app/staging');

host('acc')
    ->set('hostname', 'ssh###.webhosting.be')
    ->set('url', '')
    ->set('remote_user', 'examplebe')
    ->set('branch', 'acc')
    ->set('deploy_path', '/data/sites/web/examplebe/app/acc');

/** Notify deploy started */
before('deploy', 'slack:notify');

/** Install theme dependencies */
after('deploy:vendors', 'sage:vendors');

/** Push theme assets */
after('deploy:update_code', 'push:assets');

/** Write revision to file */
after('deploy:update_code', 'otomaties:write_revision_to_file');

/** Reload Combell */
after('deploy:symlink', 'combell:reloadPHP');

/** Clear OPcode cache */
after('deploy:symlink', 'combell:reset_opcode_cache');

/** Fetch Google fonts */
after('deploy:symlink', 'acorn:fetch_google_fonts');

/** Reload cache & preload */
after('deploy:symlink', 'wp_rocket:init_cache');

/** Remove unused themes */
after('deploy:cleanup', 'wordpress:cleanup');

/** Notify success */
after('deploy:success', 'slack:notify:success');

/** Unlock deploy */
after('deploy:failed', 'deploy:unlock');

/** Notify failure */
after('deploy:failed', 'slack:notify:failure');
```
