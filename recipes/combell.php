<?php

namespace Deployer;

/** Symlink hosts */
desc('Creates a symlink to the current release');
task('combell:create_symlink', function () {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');
    $symlinkDirectory = get('symlink_directory');
    
    // Check if symlink exists
    if (test("[ -L {$symlinkDirectory} ]")) {
        writeln('Symlink already exists. Aborting.');
        return;
    }

    // Create symlink
    $webRootPath = "{$deployPath}/current/{$webRoot}";
    writeln("Symlinking {$webRootPath} to {$symlinkDirectory}");
    run("ln -s {$webRootPath} {$symlinkDirectory}");

    // reload PHP
    reloadPhp();
});

/** Reload PHP */
desc('Reload PHP');
task('combell:reloadPHP', function () {
    writeln('Waiting for reloadPHP.sh');
    $timestamp = date('YmdHis');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $reloadedFileCheckContent = "{$timestamp} {$releaseRevision}";
    $reloadedFileCheckPath = "{$releasePath}/{$webRoot}/combell-reloaded-check.txt";

    run("echo \"{$reloadedFileCheckContent}\" > {$reloadedFileCheckPath}");

    sleep(5);
    reloadPhp();

    // Try to fetch file in curl request
    $sleep = 5;
    $checkEvery = 30;
    $limit = 120;
    $iterations = 0;

    while (! revisionHasBeenUpdated($reloadedFileCheckContent)) {
        sleep($sleep);
        $secondsElapsed = $sleep * $iterations + $sleep;

        if ($secondsElapsed >= $limit) {
            writeln('Revision was not found after 120 seconds, continuing');
            break;
        }

        if ($secondsElapsed % $checkEvery == 0) {
            writeln("Revision was not found after {$secondsElapsed} seconds, reloading PHP");
            reloadPhp();
        }
        $iterations++;
    }

    writeln('reloadPHP.sh has finished');

    // Clean up
    run("rm {$reloadedFileCheckPath}");
});

/** Reset OPcode cache */
desc('Reset OPcode cache');
task('combell:reset_opcode_cache', function () {
    writeln('Clearing opcache');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $opCacheResetFilePath = "{$releasePath}/{$webRoot}/opcache_reset.{$releaseRevision}.php";

    run("echo \"<?php opcache_reset();\" > {$opCacheResetFilePath}");

    $iterations = 0;
    while (! opcodeCacheHasBeenReset()) {
        $seconds = 5 * ++$iterations;
        sleep($seconds); // 5, 10, 15, 20, 25
        if ($iterations == 5) {
            writeln('Could not clear opcache after 5 iterations.');
            break;
        }

        writeln('Could not clear opcache, retrying.');
    }

    // Clean up
    run("rm {$opCacheResetFilePath}");
});

function url($filePath)
{
    $url = rtrim(get('url'), '/');
    $filePath = ltrim($filePath, '/');

    return "{$url}/{$filePath}";
}

function reloadPhp()
{
    return run('reloadPHP.sh');
}

function revisionHasBeenUpdated($reloadedFileCheckContent)
{
    try {
        $reloadedFileContent = fetch(
            url('/combell-reloaded-check.txt'),
            'get',
            requestHeaders()
        );

        return strpos($reloadedFileContent, $reloadedFileCheckContent) === 0;
    } catch (\Throwable $th) {
        writeln($th->getMessage());

        return false;
    }
}
