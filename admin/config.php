<?php
function connection(){
    $server_name = "localhost";
    $username = "root";
    $password = ""; // For XAMPP users, password is ""
    $db_name = "web";

    // Create a connection
    $conn = new mysqli($server_name, $username, $password, $db_name);
    // $conn holds the connection
    // $conn - object
    // mysqli - class (contains different functions and variables inside)
    // mysql improved

    // Check the connection
    if($conn->connect_error){
        // There is an error.
        die("Connection failed: " . $conn->connect_error);
        // die() will terminate the current script
    } else {
        // No error in the connection
        return $conn;
    }
    // -> object operator (object is on the left)
    // connect_error contains a String value of the error


    function recalculateSalesMetrics($conn) {
        $salesReport = getSalesReport($conn);
        $totalSales = 0;
        $totalOrders = count($salesReport);
        $completedOrders = 0;
    
        foreach ($salesReport as $sale) {
            if (isset($sale['order_status']) && $sale['order_status'] === 'Completed') {
                $totalSales += $sale['total_amount'];
                $completedOrders++;
            }
        }
    
        $averageOrderValue = $completedOrders > 0 ? $totalSales / $completedOrders : 0;
    
        // Update the sales metrics in the session
        $_SESSION['salesMetrics'] = [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'average_order_value' => $averageOrderValue,
        ];
    }
}

