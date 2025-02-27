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

desc('Installs languages for plugins and themes (locally)');
task('wordpress:install_languages', function () {
    $wp = getWpCommand();

    // Get the list of installed languages
    $languages = runLocally("{$wp} core language list --status=installed --field=language");

    if (empty(trim($languages))) {
        writeln('<info>No languages installed.</info>');
        return;
    }

    $languages = explode("\n", trim($languages));

    foreach ($languages as $language) {
        writeln("<info>Installing plugins and themes for language: $language</info>");

        // Install plugins for the specified language
        $pluginsOutput = runLocally("{$wp} language plugin install --all --color $language", ['tty' => true]);
        writeln($pluginsOutput);

        // Install themes for the specified language
        $themesOutput = runLocally("{$wp} language theme install --all --color $language", ['tty' => true]);
        writeln($themesOutput);
    }

    writeln('<info>Installation of plugins and themes for all languages completed.</info>');
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

desc('Uninstalls language including plugins and themes translations (locally)');
task('wordpress:uninstall_language', function () {
    $language = ask('Language to uninstall');
    
    $wp = getWpCommand();
    
    $isActiveLanguage = runLocally("{$wp} option get WPLANG") === $language ? true : false;
    
    if($isActiveLanguage) {
        writeln("<error>Language {$language} is active. Please change the active language first.</error>");
        return;
    }

    $isLanguageInstalled = runLocally("{$wp} language core list --field=language --status=installed");
    $isLanguageInstalled = strpos($isLanguageInstalled, $language) !== false;
    
    if($isLanguageInstalled) {
        runLocally("{$wp} language core uninstall $language");
    }
    
    runLocally("{$wp} language plugin uninstall {$language} --all");
    runLocally("{$wp} language theme uninstall {$language} --all");
    
    writeln("<info>Language {$language} uninstalled.</info>");
});

desc('Checks for plugin updates');
task('wordpress:check_plugin_updates', function () {
    $wp = 'wp';
    
    if(currentHost()->has('lando')) {
        $wp = 'lando wp';
    }
    
    if(currentHost()->getAlias() === 'localhost') {
        var_dump('running locally');
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