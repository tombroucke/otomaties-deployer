<?php
namespace Deployer;

require 'recipe/composer.php';
require 'contrib/slack.php';

/** Config */
set('keep_releases', 3);
set('slack_success_text', 'Deploy to *{{target}}* successful. Visit {{url}}/wp/wp-admin.');
set('web_root', 'www');
set('sage/public_dir', 'public');

/** Shared files */
add('shared_files', [
    '.env',
    get('web_root') . '/.htaccess',
    get('web_root') . '/.htpasswd',
    get('web_root') . '/.user.ini',
    get('web_root') . '/app/object-cache.php',
    get('web_root') . '/app/wp-cache-config.php',
    get('web_root') . '/wordfence-waf.php',
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
