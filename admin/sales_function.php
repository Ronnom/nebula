<?php
// File: admin/sales_functions.php

/**
 * Get sales report data based on filters
 *
 * @param mysqli $conn Database connection
 * @param string $filter Status filter
 * @param string $date_range Date range filter
 * @return array Sales data
 */
if (!function_exists('getSalesReport')) {
    function getSalesReport($conn, $filter = 'all', $date_range = '30days') {
        // Start with the base query
        $sql = "SELECT
                    o.id AS order_id,
                    o.total_amount,
                    o.order_status,
                    o.created_at AS order_date,
                    u.first_name,
                    u.last_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE 1=1"; // This allows us to conditionally add filters

        // Apply status filter
        if ($filter !== 'all') {
            $sql .= " AND o.order_status = ?";
        }

        // Apply date range filter
        $date_condition = '';
        switch ($date_range) {
            case '7days':
                $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90days':
                $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case 'year':
                $date_condition = " AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";
                break;
            case 'all':
                // No date filter
                break;
        }

        $sql .= $date_condition;
        $sql .= " ORDER BY o.created_at DESC";

        try {
            $stmt = $conn->prepare($sql);

            // Bind parameters if we have filters
            if ($filter !== 'all') {
                $stmt->bind_param("s", $filter);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $sales = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sales[] = $row;
                }
            }
            return $sales;
        } catch (Exception $e) {
            // Log the error and return an empty array
            error_log("Error fetching sales report: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Update order status and recalculate metrics if needed
 *
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID to update
 * @param string $new_status New status for the order
 * @return bool Success or failure
 */
if (!function_exists('updateOrderStatus')) {
    function updateOrderStatus($conn, $order_id, $new_status) {
        $sql = "UPDATE orders SET order_status = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $order_id);

        return $stmt->execute();
    }
}
?>

