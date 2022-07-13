<?php
namespace Deployer;

require 'recipe/composer.php';
require 'contrib/slack.php';

/** Config */
set('keep_releases', 3);
set('slack_success_text', 'Deploy to *{{target}}* successful. Visit {{url}}/wp/wp-admin.');

/** Shared files */
add('shared_files', [
    '.env',
    'www/.htaccess',
    'www/.htpasswd',
    'www/.user.ini',
    'www/app/object-cache.php',
    'www/app/wp-cache-config.php',
    'www/wordfence-waf.php',
]);
add('shared_dirs', [
    'www/app/blogs.dir',
    'www/app/ewww',
    'www/app/fonts',
    'www/app/languages/wpml',
    'www/app/uploads',
    'www/app/wflogs',
    'www/app/wp-rocket-config',
]);
add('writable_dirs', []);
