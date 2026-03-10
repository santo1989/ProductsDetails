<?php
session_start();
require_once '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to perform this action.';
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    redirect('dashboard.php');
}

$demoImages = [
    'premium-cotton-tshirt' => [
        'https://unsplash.com/photos/5E5N49RWtbA/download?force=true&w=1200',
        'https://unsplash.com/photos/mp0bgAAfoUs/download?force=true&w=1200',
        'https://unsplash.com/photos/RiDxDgHg7pw/download?force=true&w=1200',
        'https://unsplash.com/photos/yCdPU73kGSc/download?force=true&w=1200',
        'https://unsplash.com/photos/DgXIq5tTUqY/download?force=true&w=1200'
    ],
    'denim-slim-fit-jeans' => [
        'https://unsplash.com/photos/Lks7vei-eAg/download?force=true&w=1200',
        'https://unsplash.com/photos/SJvDxw0azqw/download?force=true&w=1200',
        'https://unsplash.com/photos/qqRGHREFJJc/download?force=true&w=1200',
        'https://unsplash.com/photos/R3LcfTvcGWY/download?force=true&w=1200',
        'https://unsplash.com/photos/90WdFgbf59w/download?force=true&w=1200'
    ],
    'formal-dress-shirt' => [
        'https://unsplash.com/photos/T7K4aEPoGGk/download?force=true&w=1200',
        'https://unsplash.com/photos/6anudmpILw4/download?force=true&w=1200',
        'https://unsplash.com/photos/Yc5sL5MCbEA/download?force=true&w=1200',
        'https://unsplash.com/photos/cYyqhdbJ9TI/download?force=true&w=1200',
        'https://unsplash.com/photos/OW5KP_Pj85Q/download?force=true&w=1200'
    ],
    'athletic-performance-polo' => [
        'https://unsplash.com/photos/jgWZM5TXnwE/download?force=true&w=1200',
        'https://unsplash.com/photos/FQgI8AD-BSg/download?force=true&w=1200',
        'https://unsplash.com/photos/VxrFNhSI9zk/download?force=true&w=1200',
        'https://unsplash.com/photos/LvSUaq_yhBY/download?force=true&w=1200',
        'https://unsplash.com/photos/Zb0hcV0nJFE/download?force=true&w=1200'
    ],
    'winter-wool-sweater' => [
        'https://unsplash.com/photos/xPJYL0l5Ii8/download?force=true&w=1200',
        'https://unsplash.com/photos/OvW_Eh0KF10/download?force=true&w=1200',
        'https://unsplash.com/photos/XnC5eO2WFh8/download?force=true&w=1200',
        'https://unsplash.com/photos/MVGxDHj3c7o/download?force=true&w=1200',
        'https://unsplash.com/photos/hR535Mow9_E/download?force=true&w=1200'
    ],
    'casual-hooded-sweatshirt' => [
        'https://unsplash.com/photos/WWesmHEgXDs/download?force=true&w=1200',
        'https://unsplash.com/photos/HRZUzoX1e6w/download?force=true&w=1200',
        'https://unsplash.com/photos/Y_aXcZ4VLqI/download?force=true&w=1200',
        'https://unsplash.com/photos/YWX9z_fcG0o/download?force=true&w=1200',
        'https://unsplash.com/photos/8lnbXtxFGZw/download?force=true&w=1200'
    ],
    'linen-summer-shorts' => [
        'https://unsplash.com/photos/EuDapbwpPmA/download?force=true&w=1200',
        'https://unsplash.com/photos/vCF5sB7QecM/download?force=true&w=1200',
        'https://unsplash.com/photos/iMdsjoiftZo/download?force=true&w=1200',
        'https://unsplash.com/photos/zWTGZOe3YBo/download?force=true&w=1200',
        'https://unsplash.com/photos/H7B-M3HQbgE/download?force=true&w=1200'
    ]
];

$updatedRows = 0;

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE products SET Main_Image = ?, Image1 = ?, Image2 = ?, Image3 = ?, Image4 = ? WHERE Product_URL = ?");

    foreach ($demoImages as $slug => $images) {
        $stmt->bind_param('ssssss', $images[0], $images[1], $images[2], $images[3], $images[4], $slug);
        $stmt->execute();
        $updatedRows += $stmt->affected_rows;
    }

    $stmt->close();
    $conn->commit();

    $_SESSION['success_message'] = 'Demo images updated successfully. Total rows affected: ' . $updatedRows;
} catch (Throwable $exception) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Failed to update demo images. Please try again.';
}

$conn->close();
redirect('dashboard.php');
