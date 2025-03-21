<?php

namespace Deployer;

/** Clean up */
desc('Clean up unused themes');
task('cleanup:unused_themes', function () {
    $webRoot = get('web_root');
    run("rm -rf {$webRoot}/wp/wp-content/themes/twenty*");
});
