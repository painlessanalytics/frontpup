<?php
/**
 * Removes unused AWS services from the SDK directory
 */

$keep = [
    'CloudFront',
    'Credentials',
    'Signature',
    'Handler',
    'Api',
    'Exception',
];

$awsDir = __DIR__ . '/aws/Aws';

foreach (scandir($awsDir) as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }

    $path = $awsDir . '/' . $item;

    if (is_dir($path) && !in_array($item, $keep, true)) {
        exec('rm -rf ' . escapeshellarg($path));
        echo "Removed: $item\n";
    }
}