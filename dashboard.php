<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$inventory_result = $conn->query("SELECT id, product_name as name, category, company, quantity as qty, price, expiry FROM inventory");
$allMeds = [];
while ($row = $inventory_result->fetch_assoc()) {
    $allMeds[] = $row;
}

$sales_result = $conn->query("SELECT id, customer, subtotal, discount, total, sale_date as date, sale_time as time FROM invoices ORDER BY sale_date DESC, sale_time DESC");
$allSales = [];
while ($row = $sales_result->fetch_assoc()) {
    $row['total'] = (float)$row['total'];
    $allSales[] = $row;
}

$currentUser = [
    'name' => $_SESSION['user_name'],
    'role' => $_SESSION['user_role']
];

if (isset($_GET['verify']) && !isset($_SESSION['verify_code'])) {
    $_SESSION['verify_code'] = rand(100000, 999999);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar injected here -->
        
        <div class="main-wrapper">
            <!-- Topbar injected here -->
            
            <main class="content-area">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon emerald">
                            <i class="fa-solid fa-capsules"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Medicines</span>
                            <span class="stat-value" id="statMeds">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Sales</span>
                            <span class="stat-value" id="statSales">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Low Stock Items</span>
                            <span class="stat-value" id="statLowStock">0</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rose">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Today's Revenue</span>
                            <span class="stat-value" id="statRevenue">Rs. 0</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Column 1 & 2: Main Performance & Transactions -->
                    <div class="grid-chart card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <div>
                                <h3 style="font-size: 1.15rem;">Sales Performance</h3>
                                <p style="font-size: 0.85rem; color: var(--text-muted);">Weekly analysis of pharmacy revenue</p>
                            </div>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <select id="chartPeriod" class="form-control" style="width: auto; padding: 0.4rem 0.8rem; padding-left: 0.8rem; font-size: 0.75rem;" onchange="updateChart()">
                                    <option value="7">Last 7 Days</option>
                                    <option value="30">Last 30 Days</option>
                                </select>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Column 3: Quick Actions & Alerts (Sidebar) -->
                    <div class="grid-alerts" style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; border: none;">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem; color: white;">Quick Actions</h3>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="billing.php" class="btn btn-primary" style="background: var(--accent); border: none;">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> New Bill
                                </a>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                    <a href="inventory.php" class="btn btn-outline" style="border-color: rgba(255,255,255,0.1); color: white; background: rgba(255,255,255,0.05); font-size: 0.8rem; padding: 0.6rem;">
                                        <i class="fa-solid fa-plus"></i> Stock
                                    </a>
                                    <a href="reports.php" class="btn btn-outline" style="border-color: rgba(255,255,255,0.1); color: white; background: rgba(255,255,255,0.05); font-size: 0.8rem; padding: 0.6rem;">
                                        <i class="fa-solid fa-chart-pie"></i> Stats
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                <div style="width: 8px; height: 8px; background: var(--danger); border-radius: 50%;"></div>
                                <h3 style="font-size: 1.1rem;">Critical Alerts</h3>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div id="lowStockList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <!-- Low stock items injected here -->
                                </div>
                                <div id="expiryAlertList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <!-- Expiry alerts injected here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales (Spans bottom of main columns) -->
                    <div class="grid-recent card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 1.1rem;">Recent Transactions</h3>
                            <a href="sales.php" class="auth-link" style="font-size: 0.85rem;">View All Sales</a>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recentSalesBody">
                                    <!-- Data injected here -->
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
        window.currentUser = <?php echo json_encode($currentUser); ?>;
        let allMeds = <?php echo json_encode($allMeds); ?>;
        let allSales = <?php echo json_encode($allSales); ?>;

        function renderDashboardStats() {
            const meds = allMeds;
            const sales = allSales;
            
            document.getElementById('statMeds').innerText = meds.length;
            document.getElementById('statSales').innerText = sales.length;
            
            const lowStock = meds.filter(m => m.qty < 10);
            document.getElementById('statLowStock').innerText = lowStock.length;
            
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const threeMonthsFromNow = new Date();
            threeMonthsFromNow.setMonth(today.getMonth() + 3);

            const expiringSoon = meds.filter(m => {
                const expiry = new Date(m.expiry);
                return expiry <= threeMonthsFromNow && expiry >= today;
            });
            
            const todaySales = sales.filter(s => s.date === todayStr);
            const revenue = todaySales.reduce((acc, sale) => acc + sale.total, 0);
            document.getElementById('statRevenue').innerText = `Rs. ${revenue.toLocaleString()}`;

            // Render Recent Sales
            const recentSales = sales.slice(-6).reverse();
            const salesBody = document.getElementById('recentSalesBody');
            if (recentSales.length === 0) {
                salesBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-light); padding: 2rem;">No recent sales</td></tr>';
            } else {
                salesBody.innerHTML = recentSales.map(s => `
                    <tr>
                        <td style="font-weight: 600;">${s.id}</td>
                        <td>${s.customer}</td>
                        <td style="font-weight: 700; color: var(--accent);">Rs. ${s.total.toLocaleString()}</td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">${s.time}</td>
                        <td><span class="badge badge-success">Paid</span></td>
                    </tr>
                `).join('');
            }

            // Render Low Stock List
            const list = document.getElementById('lowStockList');
            if (lowStock.length === 0) {
                list.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-muted);">All items in stock.</p>';
            } else {
                list.innerHTML = lowStock.slice(0, 3).map(m => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: var(--surface-alt); border-radius: var(--radius-md);">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.85rem; font-weight: 600;">${m.name}</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">${m.company}</span>
                        </div>
                        <span class="badge badge-danger" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">${m.qty} left</span>
                    </div>
                `).join('');
            }

            // Render Expiry List
            const expiryList = document.getElementById('expiryAlertList');
            if (expiringSoon.length === 0) {
                expiryList.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-muted);">No items expiring soon.</p>';
            } else {
                expiryList.innerHTML = expiringSoon.slice(0, 3).map(m => {
                    const expiry = new Date(m.expiry);
                    const diffTime = Math.abs(expiry - today);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: var(--surface-alt); border-radius: var(--radius-md);">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.85rem; font-weight: 600;">${m.name}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Exp: ${m.expiry}</span>
                            </div>
                            <span class="badge badge-warning" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">${diffDays} days</span>
                        </div>
                    `;
                }).join('');
            }
        }

        let salesChart = null;

        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('dashboard');
            Components.renderTopbar('Dashboard Overview');
            renderDashboardStats();
            updateChart();
        });

        function updateChart() {
            const period = parseInt(document.getElementById('chartPeriod').value);
            renderSalesChart(period);
        }

        function renderSalesChart(days) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const sales = allSales;
            
            // Prepare labels (last X days)
            const labels = [];
            const data = [];
            
            for (let i = days - 1; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                const daySales = sales.filter(s => s.date === dateStr);
                const total = daySales.reduce((sum, s) => sum + s.total, 0);
                data.push(total);
            }

            if (salesChart) {
                salesChart.destroy();
            }

            salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (Rs.)',
                        data: data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Outfit', size: 13 },
                            bodyFont: { family: 'Outfit', size: 13 },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: Rs. ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                            ticks: {
                                font: { family: 'Outfit' },
                                callback: function(value) {
                                    return 'Rs. ' + value.toLocaleString();
                                }
                            }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { family: 'Outfit' } }
                        }
                    }
                }
            });
        }

        // Mock Email Verification Logic
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('verify')) {
            const correctCode = "<?php echo $_SESSION['verify_code'] ?? ''; ?>";
            const verificationModalHtml = `
                <div id="verifyModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 2rem;">
                    <div class="card" style="width: 100%; max-width: 450px; text-align: center; padding: 3rem;">
                        <div style="width: 70px; height: 70px; background: var(--primary-glow); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 2rem;">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                        </div>
                        <h2 style="margin-bottom: 1rem;">Verify Your Email</h2>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">We've sent a 6-digit verification code to <strong><?php echo $_SESSION['user_name']; ?></strong>'s email. <br> <span style="background: #fef08a; padding: 2px 8px; border-radius: 4px; color: #854d0e; font-weight: 700; margin-top: 10px; display: inline-block;">Mock Code: ${correctCode}</span></p>
                        <div style="display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 2rem;">
                            <input type="text" maxlength="6" id="vCodeInput" class="form-control" style="width: 200px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: 700; letter-spacing: 10px;">
                        </div>
                        <button class="btn btn-primary" style="width: 100%;" onclick="verifyEmailCode()">Verify & Continue</button>
                        <p style="margin-top: 1.5rem; font-size: 0.9rem;">Didn't receive the code? <a href="#" class="auth-link">Resend Code</a></p>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', verificationModalHtml);
        }

        function verifyEmailCode() {
            const input = document.getElementById('vCodeInput').value;
            const correct = "<?php echo $_SESSION['verify_code'] ?? ''; ?>";
            if (input === correct) {
                alert('Email verified successfully!');
                document.getElementById('verifyModal').remove();
                // Remove the verify param from URL without reload
                const url = new URL(window.location);
                url.searchParams.delete('verify');
                window.history.pushState({}, '', url);
            } else {
                alert('Invalid verification code. Please try again.');
            }
        }
    </script>
</body>
</html>
