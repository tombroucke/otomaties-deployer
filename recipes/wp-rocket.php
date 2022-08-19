<?php
namespace Deployer;

/** Clear cache */
desc('Clears cache & starts preload');
task('wp_rocket:clear_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp rocket regenerate --file=advanced-cache');
            run('wp rocket clean --confirm');
        }
    );
});

/** Start preload */
desc('Clears cache & starts preload');
task('wp_rocket:preload_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp rocket preload');
        }
    );
});
