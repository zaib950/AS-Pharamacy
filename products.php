<?php
session_start();
require_once 'config.php';

// Search and Category filtering
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['cat']) ? $_GET['cat'] : 'all';

$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $query .= " AND (product_name LIKE ? OR company LIKE ? OR category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($category !== 'all' && $category !== '') {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY product_name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines Catalog - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .catalog-header { background: var(--primary); color: white; padding: 60px 20px; text-align: center; }
        .filter-bar { background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow); margin-top: -40px; max-width: 1000px; margin-left: auto; margin-right: auto; display: flex; gap: 1rem; position: relative; z-index: 10; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; padding: 60px 20px; max-width: 1200px; margin: 0 auto; }
        .product-card { background: white; padding: 25px; border-radius: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); transition: 0.3s; position: relative; overflow: hidden; }
        .product-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
        .product-card .cat { font-size: 0.75rem; color: var(--primary); background: var(--primary-glow); padding: 4px 10px; border-radius: 20px; display: inline-block; margin-bottom: 10px; }
        .product-card h3 { font-size: 1.25rem; margin-bottom: 5px; }
        .product-card .company { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px; }
        .product-card .price { font-size: 1.5rem; font-weight: 800; color: var(--accent); margin-bottom: 20px; }
        .product-card .btn { width: 100%; }
        
        nav { display: flex; justify-content: space-between; align-items: center; padding: 20px 5%; background: white; position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow-sm); }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.5rem; color: var(--primary); }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="logo" style="text-decoration: none;">
            <i class="fa-solid fa-briefcase-medical"></i>
            <span>AS Pharmacy</span>
        </a>
        <div style="display: flex; gap: 2rem; align-items: center;">
            <a href="index.php" class="auth-link">Home</a>
            <a href="products.php" class="auth-link active">Medicines</a>
            <a href="index.php#about" class="auth-link">About Us</a>
            <a href="index.php#feedback" class="auth-link">Feedback</a>
            <a href="index.php#contact" class="auth-link">Contact</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="auth-link">Login</a>
                <a href="signup.php" class="btn btn-primary">Join Us</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="catalog-header">
        <h1>Available Medicines</h1>
        <p>Browse our wide range of authentic healthcare products</p>
    </header>

    <div class="filter-bar">
        <form action="products.php" method="GET" style="display: flex; flex: 1; gap: 1rem;">
            <div class="input-wrapper" style="flex: 2;">
                <i class="fa-solid fa-search input-icon"></i>
                <input type="text" name="q" class="form-control" placeholder="Search medicines..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select name="cat" class="form-control" style="flex: 1; padding-left: 1rem;">
                <option value="all">All Categories</option>
                <?php
                $catResult = $conn->query("SELECT name FROM categories ORDER BY name ASC");
                while($c = $catResult->fetch_assoc()) {
                    $sel = ($category == $c['name']) ? 'selected' : '';
                    echo "<option value='{$c['name']}' $sel>{$c['name']}</option>";
                }
                ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div style="max-width: 1200px; margin: 2rem auto 0; padding: 0 20px;">
        <?php if ($search !== '' || $category !== 'all'): ?>
            <p style="color: var(--text-muted);">
                Found <?php echo count($products); ?> results 
                <?php echo $search !== '' ? "for '<strong>".htmlspecialchars($search)."</strong>'" : ""; ?>
                <?php echo $category !== 'all' ? " in <strong>".htmlspecialchars($category)."</strong>" : ""; ?>
            </p>
        <?php endif; ?>
    </div>

    <section class="product-grid">
        <?php if (empty($products)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px;">
                <i class="fa-solid fa-box-open" style="font-size: 4rem; color: var(--text-light); margin-bottom: 2rem;"></i>
                <h3>No medicines found matching your criteria.</h3>
                <a href="products.php" class="auth-link">Clear filters</a>
            </div>
        <?php else: ?>
            <?php foreach($products as $p): ?>
                <div class="product-card">
                    <span class="cat"><?php echo $p['category']; ?></span>
                    <h3><?php echo $p['product_name']; ?></h3>
                    <p class="company"><?php echo $p['company']; ?></p>
                    <div class="price">Rs. <?php echo number_format($p['price'], 2); ?></div>
                    <a href="login.php" class="btn btn-primary">Order Now</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer style="padding: 40px 20px; text-align: center; border-top: 1px solid var(--border); color: var(--text-muted); margin-top: 4rem;">
        <p>&copy; 2026 AS Pharmacy. All Rights Reserved.</p>
    </footer>

    <script src="assets/js/components.js"></script>
</body>
</html>
