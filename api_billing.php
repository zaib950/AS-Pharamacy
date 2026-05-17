<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Enable mysqli exceptions for proper error catching
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['action']) && $data['action'] == 'process_bill') {
    $sale      = $data['sale'];
    $billItems = $data['items'];

    // Sanitize values
    $invId     = (string) $sale['id'];
    $customer  = !empty($sale['customer']) ? (string) $sale['customer'] : 'Walk-in Patient';
    $subtotal  = (float)  $sale['subtotal'];
    $discount  = (float)  $sale['discount'];
    $total     = (float)  $sale['total'];
    $saleDate  = (string) $sale['date'];   // YYYY-MM-DD
    $saleTime  = (string) $sale['time'];   // HH:MM:SS

    $conn->begin_transaction();
    try {
        // Insert invoice
        $stmt = $conn->prepare(
            "INSERT INTO invoices (id, customer, subtotal, discount, total, sale_date, sale_time)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdddss", $invId, $customer, $subtotal, $discount, $total, $saleDate, $saleTime);
        $stmt->execute();

        // Insert each item & deduct stock
        $stmtItem = $conn->prepare(
            "INSERT INTO invoice_items (invoice_id, product_name, quantity, price, total)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmtStock = $conn->prepare(
            "UPDATE inventory SET quantity = quantity - ? WHERE id = ?"
        );
        
        // Also insert into the legacy sales table for the user
        $stmtLegacySale = $conn->prepare(
            "INSERT INTO sales (product_id, quantity_sold, total_price) VALUES (?, ?, ?)"
        );

        foreach ($billItems as $item) {
            $pname = (string) $item['name'];
            $qty   = (int)    $item['qty'];
            $price = (float)  $item['price'];
            $itot  = (float)  $item['total'];
            $pid   = (int)    $item['id'];

            $stmtItem->bind_param("ssidd", $invId, $pname, $qty, $price, $itot);
            $stmtItem->execute();

            $stmtStock->bind_param("ii", $qty, $pid);
            $stmtStock->execute();
            
            $stmtLegacySale->bind_param("iid", $pid, $qty, $itot);
            $stmtLegacySale->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
