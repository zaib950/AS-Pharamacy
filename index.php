<?php
session_start();
require_once 'config.php';

// Handle contact form submission
$msg_status = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    
    // 250 word limit check
    $word_count = str_word_count($message);
    if ($word_count > 250) {
        $msg_status = "Error: Message exceeds 250 words limit!";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);
        if ($stmt->execute()) {
            $msg_status = "Success: Your message has been sent!";
        } else {
            $msg_status = "Error: Could not send message.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS Pharmacy - Your Health, Our Priority</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/822/822143.png">
    <style>
        .hero { background: linear-gradient(rgba(16, 185, 129, 0.9), rgba(6, 95, 70, 0.9)), url('https://images.unsplash.com/photo-1586015555751-63bb77f4322a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'); background-size: cover; background-position: center; padding: 100px 20px; color: white; text-align: center; }
        .search-box-large { max-width: 800px; margin: 40px auto; background: white; padding: 10px; border-radius: 50px; display: flex; box-shadow: var(--shadow-lg); }
        .search-box-large select { border: none; padding: 0 20px; border-right: 1px solid var(--border); background: transparent; outline: none; font-family: inherit; color: var(--text); }
        .search-box-large input { flex: 1; border: none; padding: 15px 25px; outline: none; font-size: 1rem; }
        .search-box-large button { background: var(--primary); color: white; border: none; padding: 0 30px; border-radius: 30px; cursor: pointer; transition: 0.3s; }
        .search-box-large button:hover { background: var(--primary-dark); }
        
        .section { padding: 80px 20px; max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px; }
        .contact-container { display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px; background: white; padding: 40px; border-radius: 20px; box-shadow: var(--shadow); }
        .map-frame { width: 100%; height: 400px; border-radius: 12px; border: none; }
        
        nav { display: flex; justify-content: space-between; align-items: center; padding: 20px 5%; background: white; position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow-sm); }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.5rem; color: var(--primary); }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <i class="fa-solid fa-briefcase-medical"></i>
            <span>AS Pharmacy</span>
        </div>
        <div style="display: flex; gap: 2rem; align-items: center;">
        <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
            <a href="index.php" class="auth-link">Home</a>
            <a href="products.php" class="auth-link">Medicines</a>
            <a href="#about" class="auth-link">About Us</a>
            <a href="#feedback" class="auth-link">Feedback</a>
            <a href="#contact" class="auth-link">Contact</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="auth-link">Login</a>
                <a href="signup.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Join Us</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="hero">
        <h1 style="font-size: 4rem; margin-bottom: 1rem; animation: slideDown 0.8s ease-out;">Your Trusted Health Partner</h1>
        <p style="font-size: 1.3rem; opacity: 0.9; margin-bottom: 2rem; animation: fadeIn 1.2s ease-in;">Quality Medicines, Expert Advice, and Reliable Care. <br> <strong>24 Hours Delivery</strong> at your doorstep!</p>
        
        <form class="search-box-large" action="products.php" method="GET">
            <select name="cat" id="searchCategory">
                <option value="all">All Categories</option>
                <?php
                require_once 'config.php';
                $catResult = $conn->query("SELECT name FROM categories ORDER BY name ASC");
                while($c = $catResult->fetch_assoc()) {
                    echo "<option value='{$c['name']}'>{$c['name']}</option>";
                }
                ?>
            </select>
            <input type="text" name="q" placeholder="Search for medicines...">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </header>

    <section id="about" class="section">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem;">Why Choose AS Pharmacy?</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto;">
            <div class="card" style="text-align: center; padding: 3rem; animation: slideUp 0.6s ease-out;">
                <div style="width: 70px; height: 70px; background: var(--primary-glow); color: var(--primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h3>24 Hours Delivery</h3>
                <p style="color: var(--text-muted);">Get your medicines delivered at your doorstep within 24 hours.</p>
            </div>
            <div class="card" style="text-align: center; padding: 3rem; animation: slideUp 0.8s ease-out;">
                <div style="width: 70px; height: 70px; background: #dcfce7; color: #16a34a; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-certificate"></i>
                </div>
                <h3>Certified Medicines</h3>
                <p style="color: var(--text-muted);">100% authentic and certified pharmaceutical products.</p>
            </div>
            <div class="card" style="text-align: center; padding: 3rem; animation: slideUp 1s ease-out;">
                <div style="width: 70px; height: 70px; background: #fef2f2; color: #dc2626; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <h3>Expert Consultation</h3>
                <p style="color: var(--text-muted);">Talk to our expert pharmacists for any health guidance.</p>
            </div>
        </div>
    </section>

    <?php if($msg_status): ?>
        <div style="max-width: 1200px; margin: 0 auto 20px; padding: 15px; border-radius: 8px; text-align: center; <?php echo strpos($msg_status, 'Success') !== false ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;'; ?>">
            <?php echo $msg_status; ?>
        </div>
    <?php endif; ?>

    <section id="feedback" class="section" style="background: var(--surface-alt);">
        <div class="card" style="max-width: 800px; margin: 0 auto; padding: 3rem; animation: slideUp 1s ease-out;">
            <h2 style="text-align: center; margin-bottom: 1rem;">Customer Feedback</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">We value your experience. Let us know how we can improve.</p>
            <form action="index.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required style="padding-left: 1rem;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required style="padding-left: 1rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Message (Max 250 words)</label>
                    <textarea name="message" class="form-control" id="msgArea" style="height: 120px; padding: 1rem;" placeholder="Write your feedback..." required></textarea>
                    <div id="wordCount" style="text-align: right; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">0 / 250 words</div>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary" style="height: 50px;">Submit Feedback</button>
            </form>
        </div>
    </section>

    <section id="contact" class="section" style="text-align: center;">
        <h2 style="margin-bottom: 3rem;">Contact Information</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; max-width: 1200px; margin: 0 auto;">
            <div class="card" style="padding: 2rem;">
                <i class="fa-solid fa-location-dot" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 1rem;"></i>
                <h3>Our Location</h3>
                <p style="color: var(--text-muted);">University of Sargodha, Sargodha, Pakistan</p>
            </div>
            <div class="card" style="padding: 2rem;">
                <i class="fa-solid fa-phone" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 1rem;"></i>
                <h3>Phone Number</h3>
                <p style="color: var(--text-muted);">+92 300 1234567</p>
            </div>
            <div class="card" style="padding: 2rem;">
                <i class="fa-solid fa-envelope" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 1rem;"></i>
                <h3>Email Support</h3>
                <p style="color: var(--text-muted);">support@aspharmacy.com</p>
            </div>
        </div>
    </section>

    <section id="map" class="section" style="background: var(--surface-alt);">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h2 style="text-align: center; margin-bottom: 3rem;">Our Location</h2>
            <div class="card" style="padding: 0; overflow: hidden; height: 400px; animation: fadeIn 1.5s ease;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3401.354117366666!2d72.6666!3d32.0833!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3921764619934375%3A0xf6399c065f4625b2!2sUniversity%20of%20Sargodha!5e0!3m2!1sen!2s!4v1715800000000!5m2!1sen!2s" 
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <footer style="padding: 60px 20px; text-align: center; border-top: 1px solid var(--border); color: var(--text-muted); background: white;">
        <div style="margin-bottom: 2rem;">
            <i class="fa-solid fa-briefcase-medical" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--primary);">AS Pharmacy</h3>
            <p>Your Trusted Health Partner in Sargodha</p>
        </div>
        <p>&copy; 2026 AS Pharmacy. All Rights Reserved.</p>
    </footer>

    <script src="assets/js/components.js"></script>
    <script>
        const msgArea = document.getElementById('msgArea');
        const wordCountDisplay = document.getElementById('wordCount');
        
        msgArea.addEventListener('input', () => {
            const words = msgArea.value.trim().split(/\s+/).filter(w => w.length > 0);
            const count = words.length;
            wordCountDisplay.innerText = `${count} / 250 words`;
            
            if (count > 250) {
                wordCountDisplay.style.color = 'var(--danger)';
                msgArea.style.borderColor = 'var(--danger)';
            } else {
                wordCountDisplay.style.color = 'var(--text-muted)';
                msgArea.style.borderColor = 'var(--border)';
            }
        });
    </script>
</body>
</html>
