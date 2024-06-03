<?php
namespace Deployer;

/** Clear cache */
desc('Clear WordPress cache');
task('wordpress:clear_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp cache flush');
        }
    );
});

desc('Set administration email address');
task('wordpress:set_admin_email', function () {
    runWpQuery('wordpress/admin-email');
});
