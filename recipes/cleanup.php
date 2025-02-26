<?php
namespace Deployer;

/** Clean up */
desc('Clean up unused themes');
task('cleanup:unused_themes', function () {
    $webRoot = get('web_root');
    run("rm -rf {$webRoot}/wp/wp-content/themes/twenty*");
});

desc('Cleanup WordPress files');
task('cleanup:wordpress_files', function () {
    $releasePath = get('release_path');
    $webRoot = get('web_root');
    
    $filesToDelete = [
        'wp-config-sample.php',
        'license.txt',
        'readme.html',
    ];
    
    foreach ($filesToDelete as $file) {
        run("rm -f {$releasePath}/{$webRoot}/wp/{$file}");
    }
});