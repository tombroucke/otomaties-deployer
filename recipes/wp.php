<?php

namespace Deployer;

// /** Clear cache */
// desc('Clear WordPress cache');
// task('wp:cache:flush', function () {
//     within(
//         '{{release_path}}',
//         function () {
//             run('wp cache flush');
//         },
//     );
// });

desc('Set administration email address');
task('wp:set:admin_email', function () {
    runWpDbQuery('wordpress/admin-email');
});

desc('Run WP CLI command');
task('wp:cli', function () {
    $command = input()->getOption('cmd');
    if (empty($command)) {
        throw new \RuntimeException('You must provide a WP CLI command to run using the --cmd option.');
    }

    runWpQuery($command);
})->once();
