<?php

$deployFile = 'deploy.php';

if (! file_exists($deployFile)) {
    throw new \RuntimeException('`' . $deployFile . '` does not exist.');
}

$contents = file_get_contents($deployFile);

$contents = preg_replace('/set\(\s*\'web_root\'\s*,\s*\'(?:web|www)\'\s*\);\s*/', '', $contents);

$contents = str_replace(
    "set('sage/theme_path', get('web_root') . '",
    PHP_EOL . '/** Sage */' . PHP_EOL . "set('sage/theme_path', '",
    $contents,
);

if (strpos($contents, 'use function Otomaties\Deployer\runWpQuery') === false) {
    $contents = preg_replace(
        '/namespace Deployer;/',
        '$0 ' . PHP_EOL . 'use function Otomaties\Deployer\runWpQuery;',
        $contents,
    );
}

if (strpos($contents, 'use Illuminate\Support\Arr;') === false) {
    $contents = preg_replace(
        '/namespace Deployer;/',
        '$0 ' . PHP_EOL . PHP_EOL . 'use Illuminate\Support\Arr;',
        $contents,
    );
}

if (strpos($contents, 'sage:check') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Check if everything is set for sage */' . PHP_EOL . 'before(\'deploy:prepare\', \'sage:check\');' . PHP_EOL . PHP_EOL . '$0',
        $contents,
    );
}

if (strpos($contents, 'composer:upload_auth_json') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Upload auth.json */' . PHP_EOL . 'before(\'deploy:vendors\', \'composer:upload_auth_json\');' . PHP_EOL . PHP_EOL . '$0',
        $contents,
    );
}

if (strpos($contents, 'composer:remove_auth_json') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Remove auth.json */' . PHP_EOL . 'after(\'deploy:vendors\', \'composer:remove_auth_json\');' . PHP_EOL . PHP_EOL . '$0',
        $contents,
    );
}

$contents = str_replace('/** Cache ACF fields */' . PHP_EOL, '', $contents);
$contents = str_replace('after(\'deploy:symlink\', \'acorn:acf_cache\');' . PHP_EOL . PHP_EOL, '', $contents);
$contents = str_replace('/** Optimize acorn */' . PHP_EOL, '', $contents);
$contents = str_replace('after(\'deploy:symlink\', \'acorn:optimize\');' . PHP_EOL . PHP_EOL, '', $contents);
$contents = str_replace('/** Reload cache & preload */' . PHP_EOL, '', $contents);
$contents = str_replace('after(\'deploy:symlink\', \'wp_rocket:clear_cache\');' . PHP_EOL . PHP_EOL, '', $contents);
$contents = str_replace('/** Reload cache & preload */' . PHP_EOL, '', $contents);
$contents = str_replace('after(\'deploy:symlink\', \'wp_rocket:preload_cache\');' . PHP_EOL . PHP_EOL, '', $contents);

$simpleReplacements = [
    'cleanup:unused_themes' => 'wp:remove_unused_themes',
];

$contents = str_replace(array_keys($simpleReplacements), array_values($simpleReplacements), $contents);

$contents = str_replace(
    [
        'require \'vendor/tombroucke/otomaties-deployer/update/2.x.php\';' . PHP_EOL,
    ],
    [],
    $contents,
);

$contents = str_replace(
    [
        'require \'vendor/tombroucke/otomaties-deployer/deploy.php\';' . PHP_EOL,
        'require_once \'vendor/tombroucke/otomaties-deployer/deploy.php\';' . PHP_EOL,
    ],
    [
        'collect([
    \'functions.php\',
    \'recipes/auth.php\',
    \'recipes/bedrock.php\',
    \'recipes/combell.php\',
    \'recipes/composer.php\',
    \'recipes/database.php\',
    \'recipes/htaccess.php\',
    \'recipes/opcode.php\',
    \'recipes/otomaties.php\',
    \'recipes/sage.php\',
    \'recipes/wordfence.php\',
    \'recipes/wp.php\',
])
    ->map(fn ($file) => __DIR__ . \'/vendor/tombroucke/otomaties-deployer/\' . $file)
    ->filter(fn ($file) => file_exists($file))
    ->each(fn ($file) => require_once $file);' . PHP_EOL,
    ],
    $contents,
);

$contents = str_replace(
    '$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);' . PHP_EOL . '$dotenv->load();',
    '(\Dotenv\Dotenv::createImmutable(__DIR__))' . PHP_EOL . '    ->load();',
    $contents,
);

if (! str_contains($contents, 'recipe/composer.php')) {
    $contents = str_replace(
        'require \'contrib/cachetool.php\';',
        'require_once \'contrib/cachetool.php\';' . PHP_EOL . 'require_once \'recipe/composer.php\';',
        $contents,
    );
}

if (! str_contains($contents, 'task(\'otomaties:custom:optimize\'')) {
    $contents .= PHP_EOL . "/** Optimize the site */
after('deploy:symlink', 'otomaties:custom:optimize');

/** Optimize the site */
desc('Optimize the site');
task('otomaties:custom:optimize', function () {
    \$commands = [
        'wp acorn acf:cache',
        'wp acorn optimize',
        'wp rocket regenerate --file=advanced-cache',
        'wp rocket clean --confirm',
        'wp rocket preload',
    ];

    runWpQuery(Arr::join(\$commands, ' && '));
});";
}

// write the modified contents back to deploy.php
if (file_put_contents($deployFile, $contents) === false) {
    throw new \RuntimeException('Failed to write to `' . $deployFile . '`.');
}

throw new \RuntimeException('`' . $deployFile . '` has been updated to the new 2.x format. Please check the file for any additional changes that may be required. Re-run your command.');
