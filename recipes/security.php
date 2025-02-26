<?php

namespace Deployer;

use DateTime;

// Task to create security.txt
desc('Generates a security.txt file');
task('security:generate_security_txt', function () {
    $securityTxtContent = generateSecurityTxtContent();

    $fileDirectory = '{{release_path}}/.well-known';

    // Ensure the directory exists
    run("mkdir -p $fileDirectory");

    // Write the security.txt content to the file
    $filePath = "$fileDirectory/security.txt";
    run("echo '" . addslashes($securityTxtContent) . "' > $filePath");
});

// Hook the task to the deploy sequence
after('deploy:symlink', 'security:generate_security_txt');

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
        ? (new DateTime($expires))->format('Y-m-d\TH:i:s\Z')
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