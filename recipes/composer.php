<?php
namespace Deployer;

desc('Add repository authentication to remote server');
task('composer:add_remote_repository_authentication', function () {
    $repos = runLocally('composer config repositories');
    $ignoreRepos = ['wpackagist.org', 'repo.packagist.org'];
    foreach (json_decode($repos) as $repo) {
        if ($repo->type == 'composer') {
            $parsedUrl = parse_url($repo->url);
            $host = $parsedUrl['host'];
            if (!in_array($host, $ignoreRepos)) {
                $username = ask('username for ' . $host);
                $password = askHiddenResponse('password for ' . $host);

                run("composer config -a -g http-basic.${host} \
                ${username} ${password}");
            }
        }
    }
})->oncePerNode();
