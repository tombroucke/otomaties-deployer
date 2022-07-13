<?php
namespace Deployer;

/** Clean up */
desc('Clean up');
task('wordpress:cleanup', function () {
    run('rm -rf www/wp/wp-content/themes/twenty*');
});

