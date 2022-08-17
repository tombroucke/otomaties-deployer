<?php
namespace Deployer;

/** Clear cache & start preload */
desc('Clears cache & starts preload');
task('wp_rocket:init_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp rocket regenerate --file=advanced-cache');
            run('wp rocket clean --confirm');
            run('wp rocket preload');
        }
    );
});
