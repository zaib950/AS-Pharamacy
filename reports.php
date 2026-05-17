<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

$sales = [];
$result = $conn->query("
    SELECT i.*, 
           (SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT('name', product_name, 'qty', quantity, 'price', price, 'total', total)), ']') 
            FROM invoice_items 
            WHERE invoice_id = i.id) as items_json
    FROM invoices i 
    ORDER BY i.sale_date DESC, i.sale_time DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items = json_decode($row['items_json'] ?: '[]', true);
        $sales[] = [
            'id' => $row['id'],
            'customer' => $row['customer'],
            'subtotal' => (float)$row['subtotal'],
            'discount' => (float)$row['discount'],
            'total' => (float)$row['total'],
            'date' => $row['sale_date'],
            'time' => $row['sale_time'],
            'items' => $items
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.5rem;">Reports & Analytics</h2>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">Business performance and insights</p>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <select id="periodFilter" class="form-control" style="width: 180px; padding-left: 1rem;">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>This Month</option>
                        </select>
                        <button class="btn btn-outline" onclick="window.print()">
                            <i class="fa-solid fa-download"></i> Export Report
                        </button>
                    </div>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon emerald">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Revenue</span>
                            <span class="stat-value" id="totalRevenue">Rs. 0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-basket-shopping"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Orders</span>
                            <span class="stat-value" id="totalOrders">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Discounts</span>
                            <span class="stat-value" id="totalDiscount">Rs. 0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rose">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Avg. Order Value</span>
                            <span class="stat-value" id="avgOrder">Rs. 0</span>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem;">
                    <div class="card">
                        <h3 style="margin-bottom: 1.5rem;">Revenue by Category</h3>
                        <div style="height: 300px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <h3 style="margin-bottom: 1.5rem;">Top Selling Medicines</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="topSellingBody">
                                    <!-- Top selling items injected here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        let allSales = <?php echo json_encode($sales); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('reports');
            Components.renderTopbar('Analytics Reports');
            generateReportData();
        });

        function generateReportData() {
            const sales = allSales;

            const revenue = sales.reduce((acc, s) => acc + s.total, 0);
            const discount = sales.reduce((acc, s) => acc + s.discount, 0);
            const orders = sales.length;
            const avg = orders > 0 ? Math.round(revenue / orders) : 0;

            document.getElementById('totalRevenue').innerText = `Rs. ${revenue.toLocaleString()}`;
            document.getElementById('totalOrders').innerText = orders;
            document.getElementById('totalDiscount').innerText = `Rs. ${discount.toLocaleString()}`;
            document.getElementById('avgOrder').innerText = `Rs. ${avg.toLocaleString()}`;

            // Top Selling Logic
            const medStats = {};
            sales.forEach(sale => {
                sale.items.forEach(item => {
                    if (!medStats[item.name]) {
                        medStats[item.name] = { qty: 0, revenue: 0 };
                    }
                    medStats[item.name].qty += item.qty;
                    medStats[item.name].revenue += item.total;
                });
            });

            const topSelling = Object.entries(medStats)
                .sort((a, b) => b[1].qty - a[1].qty)
                .slice(0, 5);

            document.getElementById('topSellingBody').innerHTML = topSelling.map(([name, data]) => `
                <tr>
                    <td><strong>${name}</strong></td>
                    <td>${data.qty} Units</td>
                    <td style="font-weight: 700; color: var(--accent);">Rs. ${data.revenue.toLocaleString()}</td>
                </tr>
            `).join('');

            renderCategoryChart();
        }

        function renderCategoryChart() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Analgesic', 'Antibiotic', 'Antacid', 'Vitamin', 'Other'],
                    datasets: [{
                        data: [40, 25, 15, 10, 10],
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f43f5e', '#64748b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    cutout: '70%'
                }
            });
        }
    </script>
</body>

</html>
