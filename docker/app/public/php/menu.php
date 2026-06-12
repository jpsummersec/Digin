<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$homeUrl = $isLoggedIn ? 'search-page.php' : 'landing.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Include Menu Stylesheet -->
<link rel="stylesheet" href="../css/menu.css">

<!-- DigIn Responsive Navigation Header -->
<header class="digin-header">
    <div class="digin-nav-inner">
        <a href="<?= htmlspecialchars($homeUrl) ?>" class="digin-logo">
            <img src="../images/digin_logo.svg" alt="DigIn Logo">
        </a>
        <button class="digin-hamburger" id="diginMenuBtn" aria-label="Toggle navigation menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <div class="digin-dropdown" id="diginDropdown" role="navigation">
       
        <a href="search-page.php" class="digin-dropdown-link <?= ($currentPage === 'search-page.php' && isset($_GET['query'])) ? 'active' : '' ?>">Search</a>
        <a href="profile-page.php" class="digin-dropdown-link <?= ($currentPage === 'profile-page.php') ? 'active' : '' ?>">Profile</a>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="digin-dropdown-link">Logout</a>
        <?php else: ?>
            <a href="signin.php" class="digin-dropdown-link <?= ($currentPage === 'signin.php' || $currentPage === 'create-account.php') ? 'active' : '' ?>">Log out</a>
        <?php endif; ?>
    </div>
</header>

<script src="../js/menu.js"></script>
