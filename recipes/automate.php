<?php
namespace Deployer;

use DateTime;

/* Add revision.txt */
desc('Write timestamp and git commit to file');
task('automate:write_revision_to_file', function () {
    $date = date('YmdHis');
    $webRoot = get('web_root');
    $releasePath = get('release_path');
    $releaseRevision = get('release_revision');
    $revisionFilePath = "{$releasePath}/{$webRoot}/revision.txt";

    run("echo \"{$date} {$releaseRevision}\" > {$revisionFilePath}");
});

desc('Disable access to sensitive files');
task('automate:disable_access_to_sensitive_files', function () {
    appendToHtaccess('snippets/htaccess/sensitive-files.txt');
});

desc('Disable access to blade files');
task('automate:disable_access_to_blade_files', function () {
    appendToHtaccess('snippets/htaccess/disable-access-to-blade-files.txt');
});

desc('Disable xmlrpc');
task('automate:disable_xmlrpc', function () {
    appendToHtaccess('snippets/htaccess/disable-xmlrpc.txt');
});

desc('7G firewall');
task('automate:7g_firewall', function () {
    appendToHtaccess('snippets/htaccess/7g-firewall.txt');
});

desc('Woff2 Expires headers');
task('automate:woff2_expires_headers', function () {
    appendToHtaccess('snippets/htaccess/woff2-expires-headers.txt');
});

desc('Security headers');
task('automate:security_headers', function () {
    appendToHtaccess('snippets/htaccess/security-headers.txt');
});

function appendToHtaccess($filepath) {
    $deployPath = get('deploy_path');
    $webRoot = get('web_root');

    $htaccessPath = "{$deployPath}/shared/{$webRoot}/.htaccess";
    $content = PHP_EOL . '# automate deployer: ' . $filepath . PHP_EOL;

    $content .= file_get_contents(dirname(__DIR__) . '/' . $filepath);
    
    if (!test("grep -q {$filepath} {$deployPath}/shared/{$webRoot}/.htaccess")) {
        createFileIfNotExists($htaccessPath);    
        // Append content to .htaccess
        $slashedContent = addcslashes($content, '"`$\\');
        run("echo \"{$slashedContent}\" >> {$htaccessPath}");
    }
}

desc('Builds assets and uploads them to remote server');
task('automate:htaccess_rules', [
    'automate:disable_access_to_sensitive_files',
    'automate:disable_access_to_blade_files',
    'automate:disable_xmlrpc',
    'automate:7g_firewall',
    'automate:woff2_expires_headers',
    'automate:security_headers',
]);


// Create security.txt file
desc('Creates a security.txt file');
task('deploy:security_txt', function () {
    $securityTxtContent = generateSecurityTxtContent();

    $fileDirectory = '{{release_path}}/.well-known';

    // Ensure the directory exists
    run("mkdir -p $fileDirectory");

    // Write the security.txt content to the file
    $filePath = "$fileDirectory/security.txt";
    run("echo '" . addslashes($securityTxtContent) . "' > $filePath");
});

function generateSecurityTxtContent()
{
    $envPrefix = 'SECURITY_TXT_';
    
    $companyName = getenv("{$envPrefix}COMPANY_NAME");
    $contact = getenv("{$envPrefix}CONTACT");
    $encryption = getenv("{$envPrefix}ENCRYPTION");
    $acknowledgments = getenv("{$envPrefix}ACKNOWLEDGEMENTS");
    $policy = getenv("{$envPrefix}POLICY");
    $hiring = getenv("{$envPrefix}HIRING");
    $preferredLanguages = getenv("{$envPrefix}PREFERRED_LANGUAGES");
    $canonical = getenv("{$envPrefix}CANONICAL");
    
    $expires = getenv("{$envPrefix}EXPIRES");
    $expirationDate = $expires 
        ? new DateTime($expires)->format('Y-m-d\TH:i:s\Z')
        : (new DateTime())->modify('+1 year')->format('Y-m-d\TH:i:s\Z');

    $variables = [
        '[[company_name]]' => $companyName,
        '[[contact]]' => $contact,
        '[[encryption]]' => $encryption,
        '[[acknowledgments]]' => $acknowledgments,
        '[[policy]]' => $policy,
        '[[hiring]]' => $hiring,
        '[[preferred_languages]]' => $preferredLanguages,
        '[[canonical]]' => $canonical,
        '[[expires]]' => $expirationDate,
    ];

    $securityTxtContent = <<<EOT
    # Security.txt for [[company_name]]
    # More information: https://securitytxt.org/

    Contact: [[contact]]
    Encryption: [[encryption]]
    Acknowledgments: [[acknowledgments]]
    Policy: [[policy]]
    Hiring: [[hiring]]
    Preferred-Languages: [[preferred_languages]]
    Canonical: [[canonical]]
    Expires: [[expires]]
    EOT;

    $securityTxtContent = preg_replace_callback(
        '/\[\[(.*?)\]\]/',
        function ($matches) use ($variables) {
            return $variables[$matches[0]] ?? $matches[0];
        },
        $securityTxtContent
    );

    return $securityTxtContent;
}