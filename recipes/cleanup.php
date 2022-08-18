<?php
namespace Deployer;

/** Clean up */
desc('Clean up');
task('wordpress:cleanup', function () {
    $webRoot = get('web_root');
    run("rm -rf {$webRoot}/wp/wp-content/themes/twenty*");
});
