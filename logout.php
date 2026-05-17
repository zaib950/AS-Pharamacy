<?php
session_start();
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['user_login'])) {
    setcookie("user_login", "", time() - 3600, "/");
}

header("Location: login.php");
exit();
?>
