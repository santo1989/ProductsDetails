<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    if (!is_logged_in() || !is_admin()) {
        $_SESSION['error_message'] = 'You do not have permission to perform this action.';
        redirect('dashboard.php');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error_message'] = 'Invalid request method.';
        redirect('dashboard.php');
    }
}

$imageFields = ['Main_Image', 'Image1', 'Image2', 'Image3', 'Image4'];
$processed = 0;
$generated = 0;
$skipped = 0;
$missing = 0;
$errors = 0;

try {
    $result = $conn->query("SELECT ID, Main_Image, Image1, Image2, Image3, Image4 FROM products");

    if ($result) {
        while ($product = $result->fetch_assoc()) {
            foreach ($imageFields as $field) {
                $relativePath = $product[$field] ?? '';

                if (empty($relativePath) || preg_match('/^https?:\/\//i', $relativePath)) {
                    $skipped++;
                    continue;
                }

                $processed++;
                $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                if (!file_exists($absolutePath)) {
                    $missing++;
                    continue;
                }

                $thumbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, get_thumbnail_path_from_relative_path($relativePath));
                $hadThumbBefore = file_exists($thumbPath);

                if (regenerate_thumbnail_for_relative_path($relativePath, true)) {
                    if ($hadThumbBefore) {
                        $skipped++;
                    } else {
                        $generated++;
                    }
                } else {
                    $errors++;
                }
            }
        }
    }

    $message = 'Thumbnail regeneration complete. Processed: ' . $processed . ', generated: ' . $generated . ', existing/remote skipped: ' . $skipped . ', missing: ' . $missing . ', errors: ' . $errors . '.';

    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        $_SESSION['success_message'] = $message;
        redirect('dashboard.php');
    }
} catch (Throwable $exception) {
    $message = 'Thumbnail regeneration failed.';

    if ($isCli) {
        fwrite(STDERR, $message . ' ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    $_SESSION['error_message'] = $message;
    redirect('dashboard.php');
} finally {
    $conn->close();
}
