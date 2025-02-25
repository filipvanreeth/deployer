<?php
namespace Deployer;

require_once 'recipe/composer.php';
// require_once 'contrib/slack.php';
require_once 'functions.php';

// require all files in recipes
foreach (glob(__DIR__ . '/recipes/*.php') as $filename) {
    require_once $filename;
}

/** Config */
set('keep_releases', 3);
// set('slack_success_text', 'Deploy to *{{target}}* successful. Visit {{url}}/wp/wp-admin.');
set('basic_auth_user', '');
set('basic_auth_pass', '');

set('web_root', 'www');
set('db_prefix', 'wp_');

// set('cachetool_args', '--web=SymfonyHttpClient --web-path=./{{web_root}} --web-url={{url}} --web-basic-auth="{{basic_auth_user}}:{{basic_auth_pass}}"');

/** Shared files */
add('shared_files', [
    '.env',
    '{{ web_root }}/.htaccess',
    '{{ web_root }}/.htpasswd',
    '{{ web_root }}/.user.ini',
    '{{ web_root }}/app/object-cache.php',
    '{{ web_root }}/app/wp-cache-config.php',
]);

/** Shared directories */
add('shared_dirs', [
    '{{ web_root }}/app/blogs.dir',
    '{{ web_root }}/app/fonts',
    '{{ web_root }}/app/uploads',
]);

/** Writable directories */
add('writable_dirs', []);

/** Copy auth.json */
before('deploy:vendors', 'composer:upload_auth_json');

/** Remove auth.json */
after('deploy:vendors', 'composer:remove_auth_json');
