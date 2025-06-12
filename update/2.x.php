<?php

$deployFile = 'deploy.php';

if (! file_exists($deployFile)) {
    throw new \RuntimeException('`'.$deployFile.'` does not exist.');
}

$contents = file_get_contents($deployFile);

$contents = preg_replace('/set\(\s*\'web_root\'\s*,\s*\'(?:web|www)\'\s*\);\s*/', '', $contents);

// replace set('sage/theme_path', get('web_root') . ' with set('sage/theme_path', '
$contents = str_replace(
    "set('sage/theme_path', get('web_root') . '",
    PHP_EOL.'/** Sage */'.PHP_EOL."set('sage/theme_path', '",
    $contents
);

// check if sage:check is present, if not, add it right before /** Install theme dependencies */
if (strpos($contents, 'sage:check') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Check if everything is set for sage */'.PHP_EOL.'after(\'deploy:vendors\', \'sage:check\');'.PHP_EOL.PHP_EOL.'$0',
        $contents
    );
}

if (strpos($contents, 'composer:upload_auth_json') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Upload auth.json */'.PHP_EOL.'before(\'deploy:vendors\', \'composer:upload_auth_json\');'.PHP_EOL.PHP_EOL.'$0',
        $contents
    );
}

if (strpos($contents, 'composer:remove_auth_json') === false) {
    $contents = preg_replace(
        '/\/\*\* Install theme dependencies \*\//',
        '/** Remove auth.json */'.PHP_EOL.'before(\'deploy:vendors\', \'composer:remove_auth_json\');'.PHP_EOL.PHP_EOL.'$0',
        $contents
    );
}

$simpleReplacements = [
    '\'acorn:acf_cache\'' => 'fn () => runWpQuery(\'wp acorn acf:cache\')',
    '\'acorn:optimize\'' => 'fn () => runWpQuery(\'wp acorn optimize\')',
    '\'wp_rocket:clear_cache\'' => 'fn () => runWpQuery(\'wp rocket regenerate --file=advanced-cache && wp rocket clean --confirm\')',
    '\'wp_rocket:preload_cache\'' => 'fn () => runWpQuery(\'wp rocket preload\')',
    'cleanup:unused_themes' => 'wp:remove_unused_themes',
    '\'runcloud-hub:update-dropin\'' => 'fn () => runWpQuery(\'wp runcloud-hub update-dropin\')',
    '\'runcloud-hub:purgeall\'' => 'fn () => runWpQuery(\'wp runcloud-hub purgeall\')',
];

$contents = str_replace(array_keys($simpleReplacements), array_values($simpleReplacements), $contents);

$contents = str_replace(
    [
        'require \'vendor/tombroucke/otomaties-deployer/update.php\';'.PHP_EOL,
        'require_once \'vendor/tombroucke/otomaties-deployer/update.php\';'.PHP_EOL,
    ],
    [],
    $contents
);

$contents = str_replace(
    [
        'require \'vendor/tombroucke/otomaties-deployer/deploy.php\';'.PHP_EOL,
        'require_once \'vendor/tombroucke/otomaties-deployer/deploy.php\';'.PHP_EOL,
    ],
    [
        'collect([
    \'functions.php\',
    \'recipes/auth.php\',
    \'recipes/bedrock.php\',
    \'recipes/combell.php\',
    \'recipes/composer.php\',
    \'recipes/database.php\',
    \'recipes/opcode.php\',
    \'recipes/otomaties.php\',
    \'recipes/sage.php\',
    \'recipes/wordfence.php\',
    \'recipes/wp.php\',
])
    ->map(fn ($file) => __DIR__ . \'/vendor/tombroucke/otomaties-deployer/\' . $file)
    ->filter(fn ($file) => file_exists($file))
    ->each(fn ($file) => require_once $file);'.PHP_EOL,
    ],
    $contents
);

$contents = str_replace(
    '$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);'.PHP_EOL.'$dotenv->load();',
    '(\Dotenv\Dotenv::createImmutable(__DIR__))'.PHP_EOL.'    ->load();',
    $contents
);

$contents = str_replace(
    'require \'contrib/cachetool.php\';',
    'require_once \'contrib/cachetool.php\';'.PHP_EOL.'require_once \'recipe/composer.php\';',
    $contents
);

// write the modified contents back to deploy.php
if (file_put_contents($deployFile, $contents) === false) {
    throw new \RuntimeException('Failed to write to `'.$deployFile.'`.');
}
