<?php
namespace Deployer;

desc('Installs plugins and themes languages (locally)');
task('languages:install', function () {
    $wp = getWpCommand();
    
    $activeLanguages = runLocally("{$wp} language core list --field=language --status=active");
    
    if (!empty(trim($activeLanguages))) {
        $activeLanguages = explode("\n", trim($activeLanguages));
    } else {
        $activeLanguages = [];
    }

    $installedLanguages = runLocally("{$wp} core language list --status=installed --field=language");

    if (empty(trim($installedLanguages))) {
        writeln('<info>No languages installed.</info>');
        return;
    }

    $installedLanguages = explode("\n", trim($installedLanguages));
    
    // Merge the active and installed languages
    $languages = array_merge($activeLanguages, $installedLanguages);
    $languages = array_unique($languages);
    
    // Remove default en_US language
    unset($languages[array_search('en_US', $languages)]);

    foreach ($languages as $language) {
        writeln("<info>Installing plugins and themes for language: $language</info>");

        $pluginsOutput = runLocally("{$wp} language plugin install --all --color $language", ['tty' => true]);
        writeln($pluginsOutput);

        $themesOutput = runLocally("{$wp} language theme install --all --color $language", ['tty' => true]);
        writeln($themesOutput);
    }

    writeln('<info>Installation of plugins and themes for all languages completed.</info>');
});

desc('Uninstall language (locally)');
task('language:uninstall', function () {
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