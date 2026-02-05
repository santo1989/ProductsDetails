<?php
// download.php
if (isset($_GET['type']) && isset($_GET['data'])) {
    if ($_GET['type'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="order_sheet.csv"');

        $data = json_decode($_GET['data'], true);

        $output = fopen('php://output', 'w');

        // Add headers
        fputcsv($output, array_merge(['Size/Color'], $data['colors'], ['Total']));

        // Add rows
        foreach ($data['rows'] as $row) {
            fputcsv($output, $row);
        }

        // Add totals row
        fputcsv($output, array_merge(['Total'], $data['colTotals'], [$data['grandTotal']]));

        fclose($output);
    }
}
