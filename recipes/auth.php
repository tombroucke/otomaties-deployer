<?php

namespace Otomaties\Deployer\Recipes\Auth;

use function Deployer\ask;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;
use function Otomaties\Deployer\cleanPath;
use function Otomaties\Deployer\createFileIfNotExists;

require_once __DIR__ . '/../functions.php';

desc('Add basic authentication to a certain host');
task('auth:password_protect', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $fullWebRootSharedPath = cleanPath("{$deployPath}/shared/{$webRoot}");

    createFileIfNotExists("{$fullWebRootSharedPath}/.htpasswd");

    $username = ask('username', get('basic_auth_user'));
    $password = ask('password', get('basic_auth_pass'));
    $encryptedPassword = crypt($password, base64_encode($password));

    if (! test("grep -q {$username}: {$fullWebRootSharedPath}/.htpasswd")) {
        ob_start();
        echo "{$username}:{$encryptedPassword}";
        $content = ob_get_clean();

        run("echo \"{$content}\" >> {$fullWebRootSharedPath}/.htpasswd");
    } else {
        writeln('<comment>Username already exists</comment>');
    }

    // Create htaccess file
    if (! test("grep -q AuthUserFile {$fullWebRootSharedPath}/.htaccess")) {
        createFileIfNotExists("{$fullWebRootSharedPath}/.htaccess");

        // Add htaccess rules
        ob_start();
        echo <<<EOL
        AuthType Basic
        AuthName "Restricted"
        AuthUserFile {$fullWebRootSharedPath}/.htpasswd
        Require valid-user
        EOL;

        $content = ob_get_clean();

        run("echo \"{$content}\" >> {$fullWebRootSharedPath}/.htaccess");
    } else {
        writeln('<comment>Basic auth already in effect</comment>');
    }
});
