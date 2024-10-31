<?php
namespace Deployer;

require_once 'recipe/composer.php';
require_once 'contrib/slack.php';
require_once 'functions.php';

// require all files in recipes
foreach (glob(__DIR__ . '/recipes/*.php') as $filename) {
    require_once $filename;
}

/** Config */
set('keep_releases', 3);
set('slack_success_text', 'Deploy to *{{target}}* successful. Visit {{url}}/wp/wp-admin.');

if (!has('web_root')) {
    set('web_root', 'www');
}

if (!has('sage/public_dir')) {
    set('sage/public_dir', 'public');
}

if (!has('db_prefix')) {
    set('db_prefix', 'wp_');
}

/** Shared files */
add('shared_files', [
    '.env',
    get('web_root') . '/.htaccess',
    get('web_root') . '/.htpasswd',
    get('web_root') . '/.user.ini',
    get('web_root') . '/app/object-cache.php',
    get('web_root') . '/app/wp-cache-config.php',
]);

/** Shared directories */
add('shared_dirs', [
    get('web_root') . '/app/blogs.dir',
    get('web_root') . '/app/ewww',
    get('web_root') . '/app/fonts',
    get('web_root') . '/app/languages/wpml',
    get('web_root') . '/app/uploads',
    get('web_root') . '/app/wflogs',
    get('web_root') . '/app/wp-rocket-config',
]);

/** Writable directories */
add('writable_dirs', []);
