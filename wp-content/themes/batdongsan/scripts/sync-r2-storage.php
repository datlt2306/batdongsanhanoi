<?php
/**
 * Cloudflare R2 Full Sync & Migration Execution Script
 *
 * Usage:
 *   wp eval-file wp-content/themes/batdongsan/scripts/sync-r2-storage.php
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPFitter\Aws\S3\S3Client;
use WPFitter\Aws\Exception\AwsException;

WP_CLI::line("===============================================================");
WP_CLI::line(" Cloudflare R2 Full Storage Sync Tool");
WP_CLI::line("===============================================================");

// 1. Get credentials
$key = advmo_get_provider_credential('cloudflare_r2', 'key');
$secret = advmo_get_provider_credential('cloudflare_r2', 'secret');
$endpoint = advmo_get_provider_credential('cloudflare_r2', 'endpoint');
$bucket = advmo_get_provider_credential('cloudflare_r2', 'bucket') ?: 'batdongsanhanoi';
$domain = advmo_get_provider_credential('cloudflare_r2', 'domain') ?: 'https://pub-df1b5df747f143958eeaaea51f7b6282.r2.dev';

if (empty($endpoint)) {
    $endpoint = 'https://5197310ce4c03e985c93b25731dc8f0d.r2.cloudflarestorage.com';
}
$endpoint = advmo_sanitize_r2_endpoint($endpoint);

WP_CLI::line("Endpoint: {$endpoint}");
WP_CLI::line("Bucket:   {$bucket}");
WP_CLI::line("CDN:      {$domain}");
WP_CLI::line("Key ID:   " . (!empty($key) ? substr($key, 0, 6) . '...' . substr($key, -4) : '(empty)'));

if (empty($key) || empty($secret)) {
    WP_CLI::error("R2 Access Key ID and Secret Access Key must be set in wp-config.php or advmo_credentials.", false);
    WP_CLI::line("To configure in wp-config.php:");
    WP_CLI::line("  define('ADVMO_CLOUDFLARE_R2_KEY', 'your_32_char_access_key_id');");
    WP_CLI::line("  define('ADVMO_CLOUDFLARE_R2_SECRET', 'your_64_char_secret_access_key');");
    exit(1);
}

// 2. Instantiate S3Client
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'auto',
    'endpoint' => $endpoint,
    'use_aws_shared_config_files' => false,
    'credentials' => [
        'key' => $key,
        'secret' => $secret,
    ],
]);

// 3. Test R2 Connection
WP_CLI::line("\nTesting connection to R2 bucket '{$bucket}'...");
try {
    $s3->listObjectsV2([
        'Bucket' => $bucket,
        'MaxKeys' => 1,
    ]);
    WP_CLI::success("Connection to Cloudflare R2 bucket verified successfully!");
} catch (Throwable $e) {
    WP_CLI::error("Connection failed: " . $e->getMessage(), false);
    exit(1);
}

// 4. Sync Theme Static Images (wp-content/themes/batdongsan/assets/images/)
$theme_images_dir = get_template_directory() . '/assets/images';
WP_CLI::line("\nSyncing theme static images from {$theme_images_dir}...");

$files = glob($theme_images_dir . '/*');
$uploaded_count = 0;

$mime_map = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $filename = basename($file);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $content_type = $mime_map[$ext] ?? 'application/octet-stream';
    $r2_key = 'assets/images/' . $filename;

    WP_CLI::log("Uploading theme asset: {$filename} -> s3://{$bucket}/{$r2_key} ({$content_type})...");
    try {
        $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $r2_key,
            'SourceFile' => $file,
            'ContentType' => $content_type,
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);
        $uploaded_count++;
    } catch (Throwable $e) {
        WP_CLI::warning("Failed to upload {$filename}: " . $e->getMessage());
    }
}
WP_CLI::success("Uploaded {$uploaded_count} theme static images to Cloudflare R2!");

// 5. Run Media Library Offload via Advanced Media Offloader CLI
WP_CLI::line("\nTriggering media library offload for all attachments...");
WP_CLI::runcommand("advmo offload");

WP_CLI::line("\n===============================================================");
WP_CLI::success("Full Cloud Migration completed successfully!");
WP_CLI::line("===============================================================");
