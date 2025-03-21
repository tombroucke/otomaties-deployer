<?php

namespace Deployer;

desc('Purge all caches');
task('runcloud-hub:purgeall', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp runcloud-hub purgeall');
        },
    );
});

desc('Update dropin');
task('runcloud-hub:update-dropin', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp runcloud-hub update-dropin');
        },
    );
});
