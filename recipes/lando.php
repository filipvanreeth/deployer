<?php
namespace Deployer;

set('lando', false);

desc('Cleanup Lando environment');
task('cleanup:lando_environment', function () {
    if (get('lando') === true) {
        writeln('<comment>Cleanup files in a Lando environment is not allowed!</comment>');
        return;
    }

    $filesToDelete = [
        '.lando.yml',
        '.lando.local.yml',
        '.lando.php',
    ];

    $directoryToDelete = [
        '.lando',
    ];

    foreach ($filesToDelete as $file) {
        run("rm -f {{release_path}}/{$file}");
    }

    foreach ($directoryToDelete as $directory) {
        run("rm -rf {{release_path}}/{$directory}");
    }

    writeln('<info>Lando files removed</info>');
});