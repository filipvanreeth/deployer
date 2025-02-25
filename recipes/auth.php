<?php
namespace Deployer;

desc('Enables HTTP authentication');
task('auth:enable_http_authentication', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    createFileIfNotExists("{$deployPath}/shared/{$webRoot}/.htpasswd");

    $username = ask('username', get('basic_auth_user'));
    $password = ask('password', get('basic_auth_pass'));
    $encryptedPassword = crypt($password, base64_encode($password));

    if (!test("grep -q {$username}: {$deployPath}/shared/{$webRoot}/.htpasswd")) {
        ob_start();
        echo "# Username: {$username}\n";
        echo "# Password: {$password}\n";
        echo "{$username}:{$encryptedPassword}";
        $content = ob_get_clean();
    
        run("echo \"{$content}\" >> {$deployPath}/shared/{$webRoot}/.htpasswd");
    } else {
        writeln('<comment>Username already exists</comment>');
    }

    // Create htaccess file
    if (!test("grep -q AuthUserFile {$deployPath}/shared/{$webRoot}/.htaccess")) {
        createFileIfNotExists("{$deployPath}/shared/{$webRoot}/.htaccess");
        
        // Add htaccess rules
        ob_start();
        echo <<<EOL
        AuthType Basic
        AuthName "Restricted"
        AuthUserFile {$deployPath}/shared/{$webRoot}/.htpasswd
        Require valid-user
        EOL;
    
        $content = ob_get_clean();
    
        run("echo \"{$content}\" >> {$deployPath}/shared/{$webRoot}/.htaccess");
    } else {
        writeln('<comment>Basic auth already in effect</comment>');
    }
});

desc('Disables HTTP authentication');
task('auth:disable_http_authentication', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    // Remove .htpasswd file
    if (test("[ -f {$deployPath}/shared/{$webRoot}/.htpasswd ]")) {
        run("rm {$deployPath}/shared/{$webRoot}/.htpasswd");
        writeln('<info>.htpasswd file removed</info>');
    } else {
        writeln('<comment>.htpasswd file does not exist</comment>');
    }

    // Remove AuthUserFile directive from .htaccess
    if (test("[ -f {$deployPath}/shared/{$webRoot}/.htaccess ]")) {
        run("sed -i '/AuthType/d' {$deployPath}/shared/{$webRoot}/.htaccess");
        run("sed -i '/AuthName/d' {$deployPath}/shared/{$webRoot}/.htaccess");
        run("sed -i '/AuthUserFile/d' {$deployPath}/shared/{$webRoot}/.htaccess");
        run("sed -i '/Require valid-user/d' {$deployPath}/shared/{$webRoot}/.htaccess");
        writeln('<info>Basic auth directives removed from .htaccess</info>');
    } else {
        writeln('<comment>.htaccess file does not exist</comment>');
    }
});