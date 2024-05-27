<?php
namespace Deployer;

desc('Password protect stage');
task('auth:password_protect_stage', function () {
    $deployPath = get('deploy_path');

    createFileIfNotExists("{$deployPath}/shared/www/.htpasswd");

    $username = ask('username', get('basic_auth_user'));
    $password = ask('password', get('basic_auth_pass'));
    $encryptedPassword = crypt($password, base64_encode($password));

    if (!test("grep -q {$username}: {$deployPath}/shared/www/.htpasswd")) {
        ob_start();
        echo "{$username}:{$encryptedPassword}";
        $content = ob_get_clean();
    
        run("echo \"{$content}\" >> {$deployPath}/shared/www/.htpasswd");
    } else {
        writeln('<comment>Username already exists</comment>');
    }

    // Create htaccess file
    if (!test("grep -q AuthUserFile {$deployPath}/shared/www/.htaccess")) {
        createFileIfNotExists("{$deployPath}/shared/www/.htaccess");
        
        // Add htaccess rules
        ob_start();
        echo <<<EOL
        AuthType Basic
        AuthName "Restricted"
        AuthUserFile {$deployPath}/shared/www/.htpasswd
        Require valid-user
        EOL;
    
        $content = ob_get_clean();
    
        run("echo \"{$content}\" >> {$deployPath}/shared/www/.htaccess");
    } else {
        writeln('<comment>Basic auth already in effect</comment>');
    }
});
