# Deployer

## Installation

```
composer require filipvanreeth/deployer --dev
```

## Example deploy.php file

```php
<?php
namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';
require 'contrib/cachetool.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require 'vendor/filipvanreeth/deployer/deploy.php';

/** Config */
set('web_root', 'web');
set('application', '');
set('repository', '');

/** Hosts */
host('production')
    ->set('hostname', '')
    ->set('url', '')
    ->set('remote_user', '')
    ->set('branch', 'main')
    ->set('deploy_path', '/setPath/app/main')
    ->set('wp_admin_email', '');

host('staging')
    ->set('hostname', '')
    ->set('url', '')
    ->set('basic_auth_user', $_SERVER['BASIC_AUTH_USER'] ?? '')
    ->set('basic_auth_pass', $_SERVER['BASIC_AUTH_PASS'] ?? '')
    ->set('remote_user', '')
    ->set('branch', 'staging')
    ->set('deploy_path', '/path/app/staging');
    ->set('wp_admin_email', '');

/** Install theme dependencies */
after('deploy:vendors', 'composer:vendors');

/** Write revision to file */
after('deploy:update_code', 'automate:write_revision_to_file');

/** Reload Combell */
after('deploy:symlink', 'combell:reloadPHP');

/** Clear OPcode cache */
after('deploy:symlink', 'cachetool:clear:opcache');

/** Cache ACF fields */
after('deploy:symlink', 'acorn:acf_cache');

/** Remove unused themes */
after('deploy:cleanup', 'cleanup:unused_themes');

/** Unlock deploy */
after('deploy:failed', 'deploy:unlock');
```

## WooCommerce

```php
/** Update WooCommerce tables */
after('deploy:symlink', 'woocommerce:update_database');
```

## WordPress cache

```php
/** Update WooCommerce tables */
after('deploy:symlink', 'wordpress:clear_cache');
```

## Extra commands

### Initial setup

#### Symlink hosts on Combell

```bash
dep combell:host_symlink production
```

#### Create bedrock .env file

```bash
dep bedrock:create_env staging
```

#### Enable basic auth on host:

```bash
dep auth:password_protect_stage staging
```

#### Add repository authentication to remote server

```bash
dep composer:add_remote_repository_authentication
```

### Security

#### Add .htaccess rules for security

```bash
dep automate:htaccess_rules
```

### Database handling

#### Pull database from production

```bash
dep db:pull production
```

#### Download database

```bash
dep db:download production
```

#### Push database to staging

```bash
dep db:push staging
```
