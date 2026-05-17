<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'clear_all') {
    // Only allow admins to clear history? Or just proceed based on current logic
    $conn->query("DELETE FROM invoices");
    header("Location: sales.php");
    exit();
}

$sales = [];
$result = $conn->query("SELECT * FROM invoices ORDER BY sale_date DESC, sale_time DESC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sales[$row['id']] = [
            'id'       => $row['id'],
            'customer' => $row['customer'],
            'subtotal' => (float) $row['subtotal'],
            'discount' => (float) $row['discount'],
            'total'    => (float) $row['total'],
            'date'     => $row['sale_date'],
            'time'     => $row['sale_time'],
            'items'    => []
        ];
    }
}

// Fetch all items and map to their invoices
$itemResult = $conn->query("SELECT * FROM invoice_items ORDER BY invoice_id");
if ($itemResult) {
    while ($item = $itemResult->fetch_assoc()) {
        $iid = $item['invoice_id'];
        if (isset($sales[$iid])) {
            $sales[$iid]['items'][] = [
                'name'  => $item['product_name'],
                'qty'   => (int)   $item['quantity'],
                'price' => (float) $item['price'],
                'total' => (float) $item['total'],
            ];
        }
    }
}

// Re-index as plain array for JavaScript
$sales = array_values($sales);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.5rem;">Sales History</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Track and review all pharmacy transactions</p>
                    </div>
                    <button class="btn btn-outline" onclick="clearSalesHistory()" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);">
                        <i class="fa-solid fa-trash-can"></i> Clear All History
                    </button>
                </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; gap: 1rem;">
                        <div class="input-wrapper" style="flex: 1;">
                            <i class="fa-solid fa-search input-icon"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by Invoice # or Customer..." oninput="updateFilteredSales()">
                        </div>
                        <input type="date" id="dateFilter" class="form-control" style="width: 200px; padding-left: 1rem;" onchange="updateFilteredSales()">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date & Time</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Discount</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="salesTableBody">
                                <!-- Sales data injected here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="emptyState" style="padding: 4rem; text-align: center; color: var(--text-light); display: none;">
                        <i class="fa-solid fa-receipt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                        <p>No sales records found.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Sale Details Modal -->
    <div id="saleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 2rem;">
        <div class="card" style="width: 100%; max-width: 600px; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                <div>
                    <h3 id="modalInvoiceId">INV-000000</h3>
                    <p id="modalDate" style="font-size: 0.85rem; color: var(--text-muted);"></p>
                </div>
                <button onclick="closeSaleModal()" style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i class="fa-solid fa-xmark fa-xl"></i></button>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <p style="font-size: 0.9rem; margin-bottom: 0.25rem; color: var(--text-muted);">CUSTOMER</p>
                <p id="modalCustomer" style="font-weight: 600;"></p>
            </div>

            <div class="table-container" style="max-height: 300px; overflow-y: auto; margin-bottom: 1.5rem; border: 1px solid var(--border); border-radius: var(--radius-md);">
                <table style="font-size: 0.9rem;">
                    <thead style="background: var(--surface-alt);">
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsBody"></tbody>
                </table>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end; padding-top: 1rem; border-top: 1px solid var(--border);">
                <div style="display: flex; gap: 2rem; font-size: 0.9rem;">
                    <span style="color: var(--text-muted);">Subtotal</span>
                    <span id="modalSubtotal">Rs. 0</span>
                </div>
                <div style="display: flex; gap: 2rem; font-size: 0.9rem; color: var(--danger);">
                    <span>Discount</span>
                    <span id="modalDiscount">- Rs. 0</span>
                </div>
                <div style="display: flex; gap: 2rem; font-size: 1.25rem; font-weight: 800; color: var(--accent); margin-top: 0.5rem;">
                    <span>Grand Total</span>
                    <span id="modalTotal">Rs. 0</span>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <button class="btn btn-outline" style="width: 100%;" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        let allSales = <?php echo json_encode($sales); ?>;
        let currentPage = 1;
        const itemsPerPage = 10;
        let filteredSales = [];

        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('sales');
            Components.renderTopbar('Sales History');
            updateFilteredSales();
        });

        function updateFilteredSales() {
            const sales = allSales;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const date = document.getElementById('dateFilter').value;
            
            filteredSales = sales.filter(s => {
                const matchesSearch = s.id.toLowerCase().includes(search) || 
                                    s.customer.toLowerCase().includes(search);
                const matchesDate = !date || s.date === date;
                return matchesSearch && matchesDate;
            });

            currentPage = 1;
            renderSales();
        }

        function renderSales() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const pageItems = filteredSales.slice(startIndex, endIndex);

            const tbody = document.getElementById('salesTableBody');
            const empty = document.getElementById('emptyState');
            
            if (filteredSales.length === 0) {
                tbody.innerHTML = '';
                empty.style.display = 'block';
                return;
            }
            
            empty.style.display = 'none';
            tbody.innerHTML = pageItems.map(s => `
                <tr>
                    <td><strong>${s.id}</strong></td>
                    <td>
                        <div style="font-size: 0.95rem;">${s.date}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${s.time}</div>
                    </td>
                    <td>${s.customer}</td>
                    <td>${s.items.length} Items</td>
                    <td style="color: var(--danger);">Rs. ${s.discount.toLocaleString()}</td>
                    <td style="font-weight: 700; color: var(--accent);">Rs. ${s.total.toLocaleString()}</td>
                    <td>
                        <button class="action-btn" onclick="viewSale('${s.id}')" title="View Details">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            // Update Pagination Info
            document.getElementById('pageStart').innerText = filteredSales.length > 0 ? startIndex + 1 : 0;
            document.getElementById('pageEnd').innerText = Math.min(endIndex, filteredSales.length);
            document.getElementById('totalItems').innerText = filteredSales.length;

            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = endIndex >= filteredSales.length;
        }

        function changePage(dir) {
            currentPage += dir;
            renderSales();
        }

        function viewSale(id) {
            const sale = allSales.find(s => s.id === id);
            if (!sale) return;

            document.getElementById('modalInvoiceId').innerText = sale.id;
            document.getElementById('modalDate').innerText = `${sale.date} at ${sale.time}`;
            document.getElementById('modalCustomer').innerText = sale.customer;
            document.getElementById('modalSubtotal').innerText = `Rs. ${sale.subtotal.toLocaleString()}`;
            document.getElementById('modalDiscount').innerText = `- Rs. ${sale.discount.toLocaleString()}`;
            document.getElementById('modalTotal').innerText = `Rs. ${sale.total.toLocaleString()}`;

            const tbody = document.getElementById('modalItemsBody');
            tbody.innerHTML = sale.items.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.qty}</td>
                    <td>Rs. ${item.price}</td>
                    <td>Rs. ${item.total}</td>
                </tr>
            `).join('');

            document.getElementById('saleModal').style.display = 'flex';
        }

        function closeSaleModal() {
            document.getElementById('saleModal').style.display = 'none';
        }

        function clearSalesHistory() {
            if (confirm('CRITICAL: Are you sure you want to clear ALL sales history? This action cannot be undone.')) {
                window.location.href = 'sales.php?action=clear_all';
            }
        }
    </script>
</body>
</html>
