<?php

namespace Deployer;

desc('Set administration email address');
task('wp:set:admin_email', function () {
    runWpDbQuery('wordpress/admin-email');
});

desc('Run WP CLI command');
task('wp:cli', function () {
    $command = input()->getOption('cmd');
    $webRoot = get('web_root');
    $deployPath = get('deploy_path');
    $path = "{$deployPath}/current/{$webRoot}/wp";

    $autocomplete = [
        'wp cache flush',
        'wp cap add {role:editor} {capability:edit_theme_options}',
        'wp cap list {role:editor} | sort',
        'wp cap remove {role:editor} {capability:edit_theme_options}',
        'wp cli check-update',
        'wp cli info',
        'wp cli update',
        'wp cli version',
        'wp core check-update',
        'wp core is-installed',
        'wp core update-db',
        'wp core version',
        'wp cron test',
        'wp db check',
        'wp db optimize',
        'wp db prefix',
        'wp db repair',
        'wp db search',
        'wp db size --human-readable',
        'wp db tables',
        'wp login create',
        'wp maintenance-mode activate',
        'wp maintenance-mode deactivate',
        'wp maintenance-mode status',
        'wp media image-size --format={format:table}',
        'wp media regenerate --yes',
        'wp menu list --format={format:table}',
        'wp menu location list --format={format:table}',
        'wp option add {option_name} {option_value} --autoload={autoload:no}',
        'wp option delete {option_name}',
        'wp option get {option_name}',
        'wp option list --format={format:table}',
        'wp option update {option_name} {option_value} --autoload={autoload:no}',
        'wp package install {package_name:tombroucke/wp-rocket-cli}',
        'wp package uninstall {package_name:tombroucke/wp-rocket-cli}',
        'wp plugin activate {plugin_name}',
        'wp plugin deactivate {plugin_name}',
        'wp plugin get {plugin_name}',
        'wp plugin is-active {plugin_name}',
        'wp plugin is-installed {plugin_name}',
        'wp plugin list --format={format:table}',
        'wp plugin status',
        'wp post-type get {post_type:page}',
        'wp post-type list --format={format:table}',
        'wp rewrite flush',
        'wp rewrite list --format={format:table}',
        'wp rewrite structure',
        'wp role list --format={format:table}',
        'wp user add-cap {login} {capability:edit_theme_options}',
        'wp user add-role {login} {role:editor}',
        'wp user create {login} {email} --role={role:editor}',
    ];

    $replacements = [
        'role' => [
            'administrator',
            'editor',
            'author',
            'contributor',
            'subscriber',
        ],
        'capability' => [
            'edit_theme_options',
            'manage_instagram_feed_options',
            'gform_full_access',
        ],
        'package_name' => [
            'aaemnnosttv/wp-cli-login-command',
            'tombroucke/wp-rocket-cli',
        ],
        'autoload' => [
            'yes',
            'no',
        ],
        'plugin_name' => fn () => array_map(
            fn ($plugin) => basename($plugin, '.php'),
            glob($webRoot . '/app/plugins/*')
        ),
        'post_type' => [
            'page',
            'post',
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'oembed_cache',
        ],
    ];

    if (empty($command)) {
        $command = ask('What WP CLI command do you want to run?', 'wp core version', $autocomplete);
    }

    preg_match_all('/\{(\w+)(?::([^}]+))?\}/', $command, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $variable = $match[1];
        $default = $match[2] ?? null;

        $options = $replacements[$variable] ?? [];

        if (is_callable($options)) {
            $options = $options();
        }

        $command = str_replace($match[0], ask("Please provide a value for {$variable}:", $default, $options), $command);
    }

    if (empty($command)) {
        throw new \RuntimeException('You must provide a WP CLI command to run using the --cmd option.');
    }

    runWpQuery($command, $path);
})->once();
