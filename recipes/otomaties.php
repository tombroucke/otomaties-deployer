<?php

namespace Otomaties\Deployer\Recipes\Otomaties;

use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Otomaties\Deployer\cleanPath;

require_once __DIR__ . '/../functions.php';

/* Add revision.txt */
desc('Write timestamp and git commit to file');
task('otomaties:write_revision_to_file', function () {
    $date = date('YmdHis');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $revisionFilePath = cleanPath("{$releasePath}/{$webRoot}/revision.txt");

    run("echo \"{$date} {$releaseRevision}\" > {$revisionFilePath}");
});
