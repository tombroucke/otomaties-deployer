<?php

namespace Otomaties\Deployer\Recipes\Composer;

use function Deployer\ask;
use function Deployer\askHiddenResponse;
use function Deployer\desc;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\task;
use function Deployer\upload;

require_once __DIR__.'/../functions.php';

desc('Add repository authentication to remote server');
task('composer:add_remote_repository_authentication', function () {
    $repos = runLocally('composer config repositories');
    $ignoreRepos = ['wpackagist.org', 'repo.packagist.org'];
    foreach (json_decode($repos) as $repo) {
        if ($repo->type == 'composer') {
            $parsedUrl = parse_url($repo->url);
            $host = $parsedUrl['host'] ?? '';
            if (! in_array($host, $ignoreRepos)) {
                $username = ask('username for '.$host);
                $password = askHiddenResponse('password for '.$host);

                run("composer config -a -g http-basic.{$host} \
                {$username} {$password}");
            }
        }
    }
})->oncePerNode();

desc('Upload auth.json to remote');
task('composer:upload_auth_json', function () {
    $authJsonPath = 'auth.json';

    if (file_exists($authJsonPath)) {
        upload($authJsonPath, '{{release_path}}/auth.json');
    }
})->oncePerNode();

desc('Remove auth.json from remote');
task('composer:remove_auth_json', function () {
    run('rm {{release_path}}/auth.json');
})->oncePerNode();
