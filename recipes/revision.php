<?php
namespace Deployer;

/* Add revision.txt */
desc('Write timestamp and git commit to file');
task('otomaties:write_revision_to_file', function () {
    run('echo $(date \'+%Y%m%d%H%M%S\') {{release_revision}} > {{release_path}}/www/revision.txt');
});

