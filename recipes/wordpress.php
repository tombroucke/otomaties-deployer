<?php

namespace Deployer;

/** Clear cache */
desc('Clear WordPress cache');
task('wordpress:clear_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp cache flush');
        },
    );
});

desc('Set administration email address');
task('wordpress:set_admin_email', function () {
    runWpDbQuery('wordpress/admin-email');
});

desc('Allow editors to edit theme options');
task('wordpress:editor_edit_theme_options', function () {
    runWpQuery('wp cap add editor edit_theme_options');
});

desc('Run WP CLI command');
task('wp:cli', function () {
    $command = input()->getOption('cmd');
    if (empty($command)) {
        throw new \RuntimeException('You must provide a WP CLI command to run using the --cmd option.');
    }

    runWpQuery($command);
})->once();
