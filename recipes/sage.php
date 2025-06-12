<?php

namespace Deployer;

use Illuminate\Support\Str;

require_once __DIR__ . '/../functions.php';

set('sage/public_dir', 'public');

/** Check if sage is set up correctly */
desc('Check if sage is set up correctly');
task('sage:check', function () {
    $sageThemePath = prependedSageThemePath();

    if (! has('sage/theme_path')) {
        throw new \RuntimeException('The "sage/theme_path" variable is not set. Please set it in your deployer configuration.');
    } else {
        writeln('<info>✓</info> The "sage/theme_path" variable is set to: `' . $sageThemePath . '`');
    }

    if (! testLocally("[ -d $sageThemePath ]")) {
        throw new \RuntimeException('The theme path `' . $sageThemePath . '` does not exist.');
    } else {
        writeln('<info>✓</info> The theme path `' . $sageThemePath . '` exists.');
    }

    if (testLocally('[ -f {{sage/theme_path}}/{{sage/public_dir}}/hot ]')) {
        throw new \RuntimeException('Vite is running hot. Please stop the dev server before deploying.');
    } else {
        writeln('<info>✓</info> Vite is not running hot. You can deploy safely.');
    }
});

/** Install sage composer dependencies */
desc('Runs composer install on remote server');
task('sage:vendors', function () {
    $sageThemePath = prependedSageThemePath();

    run("cd {{release_path}}/$sageThemePath && {{bin/composer}} install {{composer_options}}");
});

/** Build & copy sage assets */
desc('Compiles the theme locally for production');
task('sage:compile', function () {
    $sageThemePath = prependedSageThemePath();

    runLocally("cd $sageThemePath && npm install && npm run {{sage/build_command}}");
});

desc('Updates remote assets with local assets, but without deleting previous assets on destination');
task('sage:upload_assets', function () {
    $sageThemePath = prependedSageThemePath();

    upload("$sageThemePath/{{sage/public_dir}}", "{{release_path}}/$sageThemePath");
});

desc('Builds assets and uploads them to remote server');
task('sage:compile_and_upload_assets', [
    'sage:compile',
    'sage:upload_assets',
]);

function prependedSageThemePath(): ?string
{
    $webRoot = get('web_root');
    $themePath = get('sage/theme_path');

    if (blank($themePath)) {
        return null;
    }

    return Str::of($themePath)
        ->trim('/')
        ->replaceFirst($webRoot . '/', '')
        ->prepend($webRoot . '/')
        ->trim('/')
        ->toString();
}
