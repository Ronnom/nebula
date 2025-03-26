<?php
session_start();
require "config.php";
require_once "sales_function.php";

$conn = connection();
$salesReport = getSalesReport($conn);
$salesMetrics = recalculateSalesMetrics($conn);

echo json_encode($salesMetrics);
?>
