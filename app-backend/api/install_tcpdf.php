<?php
/**
 * TCPDF Installation Helper
 * 
 * This script helps download and install the TCPDF library
 */

// Check if running from command line
$isCLI = (php_sapi_name() === 'cli');

// Function to output messages
function output($message) {
    global $isCLI;
    if ($isCLI) {
        echo $message . PHP_EOL;
    } else {
        echo $message . '<br>';
    }
}

// Set headers for browser output
if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>TCPDF Installation</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: green; }
            .error { color: red; }
            pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
    <h1>TCPDF Installation Helper</h1>';
}

// Define paths
$libDir = __DIR__ . '/../lib';
$tcpdfDir = $libDir . '/tcpdf';
$tempFile = sys_get_temp_dir() . '/tcpdf.zip';

// Check if TCPDF is already installed
if (file_exists($tcpdfDir . '/tcpdf.php')) {
    output('<span class="success">TCPDF is already installed!</span>');
    output('If you want to reinstall, please delete the ' . $tcpdfDir . ' directory first.');
    
    if (!$isCLI) {
        echo '</body></html>';
    }
    exit;
}

// Create lib directory if it doesn't exist
if (!file_exists($libDir)) {
    if (!mkdir($libDir, 0755, true)) {
        output('<span class="error">Error: Could not create lib directory.</span>');
        if (!$isCLI) {
            echo '</body></html>';
        }
        exit;
    }
}

// Download TCPDF
output('Downloading TCPDF...');
$tcpdfUrl = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.2.zip';

if (function_exists('curl_init')) {
    $ch = curl_init($tcpdfUrl);
    $fp = fopen($tempFile, 'w');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    if (!$success) {
        output('<span class="error">Error: Failed to download TCPDF using cURL.</span>');
        if (!$isCLI) {
            echo '</body></html>';
        }
        exit;
    }
} else if (ini_get('allow_url_fopen')) {
    if (!copy($tcpdfUrl, $tempFile)) {
        output('<span class="error">Error: Failed to download TCPDF using file_get_contents.</span>');
        if (!$isCLI) {
            echo '</body></html>';
        }
        exit;
    }
} else {
    output('<span class="error">Error: Neither cURL nor allow_url_fopen is enabled. Cannot download TCPDF.</span>');
    output('Please download TCPDF manually from ' . $tcpdfUrl . ' and extract it to ' . $tcpdfDir);
    if (!$isCLI) {
        echo '</body></html>';
    }
    exit;
}

output('Download complete. Extracting...');

// Extract the ZIP file
$zip = new ZipArchive;
if ($zip->open($tempFile) === TRUE) {
    // Create a temporary extraction directory
    $extractDir = $libDir . '/tcpdf_temp_' . time();
    $zip->extractTo($extractDir);
    $zip->close();
    
    // Get the extracted directory name (should be TCPDF-6.6.2)
    $extractedDir = glob($extractDir . '/*', GLOB_ONLYDIR);
    if (empty($extractedDir)) {
        output('<span class="error">Error: Could not find extracted directory.</span>');
        if (!$isCLI) {
            echo '</body></html>';
        }
        exit;
    }
    
    $sourceDir = $extractedDir[0];
    
    // If target directory already exists, remove it first
    if (file_exists($tcpdfDir)) {
        output('Target directory already exists. Removing...');
        
        // Function to recursively delete a directory
        function deleteDir($dirPath) {
            if (!is_dir($dirPath)) {
                return;
            }
            
            $files = array_diff(scandir($dirPath), array('.', '..'));
            foreach ($files as $file) {
                $path = $dirPath . '/' . $file;
                is_dir($path) ? deleteDir($path) : unlink($path);
            }
            
            return rmdir($dirPath);
        }
        
        if (!deleteDir($tcpdfDir)) {
            output('<span class="error">Warning: Could not completely remove existing directory. Installation may be incomplete.</span>');
        }
    }
    
    // Create the target directory
    if (!file_exists($tcpdfDir)) {
        mkdir($tcpdfDir, 0755, true);
    }
    
    // Copy files from source to target
    function copyDir($src, $dst) {
        $dir = opendir($src);
        if (!file_exists($dst)) {
            mkdir($dst, 0755, true);
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    copyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        
        closedir($dir);
    }
    
    copyDir($sourceDir, $tcpdfDir);
    
    // Clean up
    deleteDir($extractDir);
    unlink($tempFile);
    
    output('<span class="success">TCPDF has been successfully installed!</span>');
} else {
    output('<span class="error">Error: Failed to extract the ZIP file.</span>');
}

if (!$isCLI) {
    echo '</body></html>';
}
