<?php
namespace Deployer;

/** Install sage composer dependencies */
desc('Runs composer install on remote server');
task('sage:vendors', function () {
    run('cd {{release_path}}/{{sage/theme_path}} && {{bin/composer}} install {{composer_options}}');
});

/** Build & copy sage assets */
desc('Compiles the theme locally for production');
task('sage:compile', function () {
    runLocally('cd {{sage/theme_path}} && yarn && yarn {{sage/build_command}}');
});

desc('Updates remote assets with local assets, but without deleting previous assets on destination');
task('sage:upload_assets', function () {
    upload('{{sage/theme_path}}/{{sage/public_dir}}', '{{release_path}}/{{sage/theme_path}}');
});

desc('Builds assets and uploads them to remote server');
task('sage:compile_and_upload_assets', [
    'sage:compile',
    'sage:upload_assets',
]);
