<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT id, product_name as name, category, quantity as qty, price FROM inventory WHERE quantity > 0 ORDER BY product_name ASC");
$allMeds = [];
while ($row = $result->fetch_assoc()) {
    $allMeds[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="app-container">
        <div class="main-wrapper">
            <main class="content-area">
                <div style="display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem;">
                    <!-- Billing Form -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card">
                            <h3 style="margin-bottom: 1.5rem;">Create New Bill</h3>
                            <div
                                style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Select Medicine</label>
                                    <select id="medSelect" class="form-control" style="padding-left: 1rem;"
                                        onchange="updatePrice()">
                                        <option value="">-- Search Medicine --</option>
                                        <!-- Options injected here -->
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Price (Rs.)</label>
                                    <input type="number" id="itemPrice" class="form-control" style="padding-left: 1rem;"
                                        readonly>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" id="itemQty" class="form-control" style="padding-left: 1rem;"
                                        value="1" min="1">
                                </div>
                                <button class="btn btn-primary" onclick="addItem()">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card" style="padding: 0; overflow: hidden; flex: 1;">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="billItemsBody">
                                        <!-- Items injected here -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="emptyState" style="padding: 3rem; text-align: center; color: var(--text-light);">
                                <i class="fa-solid fa-cart-shopping"
                                    style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                                <p>No items added to the bill yet.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Sidebar -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card">
                            <h3 style="margin-bottom: 1.5rem;">Bill Summary</h3>
                            <div class="form-group">
                                <label class="form-label">Customer Name</label>
                                <input type="text" id="custName" class="form-control" style="padding-left: 1rem;"
                                    placeholder="Walk-in Patient">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Discount (%)</label>
                                <input type="number" id="discount" class="form-control" style="padding-left: 1rem;"
                                    value="0" min="0" max="100" oninput="calculateTotal()">
                            </div>

                            <div
                                style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                                    <span style="color: var(--text-muted);">Subtotal</span>
                                    <span id="subtotal">Rs. 0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                                    <span style="color: var(--text-muted);">Discount</span>
                                    <span id="discountAmt" style="color: var(--danger);">- Rs. 0</span>
                                </div>
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; margin-top: 0.5rem; color: var(--accent);">
                                    <span>Total</span>
                                    <span id="totalAmount">Rs. 0</span>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 2rem;">
                                <button class="btn btn-primary" onclick="processBill()" id="btnProcess">
                                    <i class="fa-solid fa-receipt"></i> Process & Print
                                </button>
                                <button class="btn btn-outline" onclick="clearBill()">
                                    <i class="fa-solid fa-trash"></i> Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/components.js"></script>
    <script>
        let allMeds = <?php echo json_encode($allMeds); ?>;
        let billItems = [];

        document.addEventListener('DOMContentLoaded', () => {
            Components.renderSidebar('billing');
            Components.renderTopbar('Billing System');
            populateMeds();
        });

        function populateMeds() {
            const meds = allMeds;
            const select = document.getElementById('medSelect');
            select.innerHTML = '<option value="">-- Search Medicine --</option>' +
                meds.map(m => `<option value="${m.id}" data-price="${m.price}">${m.name} (${m.qty} available)</option>`).join('');
        }

        function updatePrice() {
            const select = document.getElementById('medSelect');
            const price = select.options[select.selectedIndex].dataset.price;
            document.getElementById('itemPrice').value = price || '';
        }

        function addItem() {
            const select = document.getElementById('medSelect');
            const id = select.value;
            if (!id) return;

            const meds = allMeds;
            const inventoryMed = meds.find(m => m.id == id);
            const qty = parseInt(document.getElementById('itemQty').value);

            // Check stock
            const currentInBill = billItems.find(item => item.id == id)?.qty || 0;
            const totalRequested = currentInBill + qty;

            if (inventoryMed.qty < totalRequested) {
                alert(`OUT OF STOCK: Only ${inventoryMed.qty} units available in inventory.`);
                return;
            }

            const name = select.options[select.selectedIndex].text.split(' (')[0];
            const price = parseFloat(document.getElementById('itemPrice').value);

            const existing = billItems.find(item => item.id == id);
            if (existing) {
                existing.qty += qty;
                existing.total = existing.qty * existing.price;
            } else {
                billItems.push({ id, name, price, qty, total: price * qty });
            }

            renderBillItems();
            calculateTotal();

            // Reset fields
            select.value = '';
            document.getElementById('itemPrice').value = '';
            document.getElementById('itemQty').value = 1;
        }

        function removeItem(index) {
            billItems.splice(index, 1);
            renderBillItems();
            calculateTotal();
        }

        function renderBillItems() {
            const tbody = document.getElementById('billItemsBody');
            const empty = document.getElementById('emptyState');

            if (billItems.length === 0) {
                tbody.innerHTML = '';
                empty.style.display = 'block';
                return;
            }

            empty.style.display = 'none';
            tbody.innerHTML = billItems.map((item, index) => `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>Rs. ${item.price}</td>
                    <td>${item.qty}</td>
                    <td style="font-weight: 700;">Rs. ${item.total}</td>
                    <td>
                        <button class="action-btn" onclick="removeItem(${index})" style="color: var(--danger);"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        function calculateTotal() {
            const subtotal = billItems.reduce((acc, item) => acc + item.total, 0);
            const discountPercent = parseFloat(document.getElementById('discount').value) || 0;
            const discountAmt = (subtotal * discountPercent) / 100;
            const total = subtotal - discountAmt;

            document.getElementById('subtotal').innerText = `Rs. ${subtotal.toLocaleString()}`;
            document.getElementById('discountAmt').innerText = `- Rs. ${discountAmt.toLocaleString()}`;
            document.getElementById('totalAmount').innerText = `Rs. ${total.toLocaleString()}`;
        }

        function clearBill() {
            if (confirm('Clear all items from the current bill?')) {
                billItems = [];
                document.getElementById('custName').value = '';
                document.getElementById('discount').value = 0;
                renderBillItems();
                calculateTotal();
            }
        }

        async function processBill() {
            if (billItems.length === 0) {
                alert('Please add at least one item to the bill.');
                return;
            }

            const btnProcess = document.getElementById('btnProcess');
            btnProcess.disabled = true;
            btnProcess.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

            const subtotal = billItems.reduce((acc, item) => acc + item.total, 0);
            const discountPercent = parseFloat(document.getElementById('discount').value) || 0;
            const discountAmt = (subtotal * discountPercent) / 100;
            const total = subtotal - discountAmt;

            const sale = {
                id: 'INV-' + Date.now().toString().slice(-6),
                customer: document.getElementById('custName').value || 'Walk-in Patient',
                subtotal,
                discount: discountAmt,
                total,
                date: new Date().toISOString().split('T')[0],
                time: new Date().toLocaleTimeString('en-US', { hour12: false })
            };

            try {
                const response = await fetch('api_billing.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'process_bill', sale, items: billItems })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Bill processed successfully!');
                    
                    // Update local inventory state to avoid reload
                    billItems.forEach(item => {
                        const med = allMeds.find(m => m.id == item.id);
                        if (med) med.qty -= item.qty;
                    });
                    
                    billItems = [];
                    renderBillItems();
                    calculateTotal();
                    document.getElementById('custName').value = '';
                    populateMeds();
                } else {
                    alert('Error processing bill: ' + result.message);
                }
            } catch (error) {
                alert('Connection error while processing bill.');
            } finally {
                btnProcess.disabled = false;
                btnProcess.innerHTML = '<i class="fa-solid fa-receipt"></i> Process & Print';
            }
        }
    </script>
</body>

</html>
