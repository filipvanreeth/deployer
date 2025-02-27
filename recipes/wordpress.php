<?php
namespace Deployer;

/** Clear cache */
desc('Clear WordPress cache');
task('wordpress:clear_cache', function () {
    within(
        '{{release_path}}',
        function () {
            run('wp cache flush');
        }
    );
});

desc('Set administration email address');
task('wordpress:set_admin_email', function () {
    runWpQuery('wordpress/admin-email');
});

desc('Updates WordPress core, plugins, and themes');
task('wordpress:update', function () {
    $wp = getWpCommand();

    // Update WordPress core
    $coreOutput = runLocally("{$wp} core update --color", ['tty' => true]);
    writeln($coreOutput);

    // Update plugins
    $pluginsOutput = runLocally("{$wp} plugin update --all --color", ['tty' => true]);
    writeln($pluginsOutput);

    // Update themes
    $themesOutput = runLocally("{$wp} theme update --all --color", ['tty' => true]);
    writeln($themesOutput);
});

desc('Checks for plugin updates');
task('wordpress:check_plugin_updates', function () {
    $wp = getWpCommand();
    
    if(currentHost()->getAlias() === 'localhost') {
        $plugins = runLocally("{$wp} plugin list --update=available --format=json --path={{web_root}}/wp");
    } else {
        $wpPath = "{{current_path}}/{{web_root}}/wp";
        $plugins = run("{$wp} plugin list --update=available --format=json --path={$wpPath}");
    }

    if (empty(trim($plugins))) {
        writeln('<info>All plugins are up to date.</info>');
        return;
    }

    $plugins = json_decode($plugins, true);
    $totalPluginUpdates = count($plugins);

    $output = "<info>{$totalPluginUpdates} plugins with available updates:</info>" . PHP_EOL;

    foreach ($plugins as $plugin) {
        $pluginName = $plugin['name'];
        $currentVersion = $plugin['version'];
        $newVersion = $plugin['update_version'];
        $output .= "{$pluginName} ({$currentVersion} -> {$newVersion})" . PHP_EOL;
    }

    writeln($output);
});