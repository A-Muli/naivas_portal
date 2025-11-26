<?php
require '../vendor/autoload.php';
include "db_connect.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$sql = "SELECT supplierEmail, productName, productCategory, storeName, activationDate, no_of_BAs 
        FROM bookings WHERE status='approved' ORDER BY activationDate ASC";

$result = $conn->query($sql);

// Check for SQL errors
if (!$result) {
    die("Database query failed: " . $conn->error);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->fromArray(
    ["Supplier", "Product", "Category", "Store", "Date", "No. of BAs"],
    NULL,
    "A1"
);

$row = 2;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row", $r["supplierEmail"]);
    $sheet->setCellValue("B$row", $r["productName"]);
    $sheet->setCellValue("C$row", $r["productCategory"]);
    $sheet->setCellValue("D$row", $r["storeName"]);
    $sheet->setCellValue("E$row", $r["activationDate"]);
    $sheet->setCellValue("F$row", $r["no_of_BAs"]);
    $row++;
}

$filename = "approved_activations_" . date("Y-m-d") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
