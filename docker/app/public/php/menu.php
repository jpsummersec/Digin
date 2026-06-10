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
        <a href="<?= htmlspecialchars($homeUrl) ?>" class="digin-dropdown-link <?= ($currentPage === 'landing.php' || ($isLoggedIn && $currentPage === 'search-page.php' && !isset($_GET['query']))) ? 'active' : '' ?>">Home</a>
        <a href="search-page.php" class="digin-dropdown-link <?= ($currentPage === 'search-page.php' && isset($_GET['query'])) ? 'active' : '' ?>">Search</a>
        <a href="profile-page.php" class="digin-dropdown-link <?= ($currentPage === 'profile-page.php') ? 'active' : '' ?>">Profile</a>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="digin-dropdown-link">Logout</a>
        <?php else: ?>
            <a href="signin.php" class="digin-dropdown-link <?= ($currentPage === 'signin.php' || $currentPage === 'create-account.php') ? 'active' : '' ?>">Sign In</a>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('diginMenuBtn');
    const dropdown = document.getElementById('diginDropdown');
    
    if (menuBtn && dropdown) {
        menuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = dropdown.classList.toggle('active');
            menuBtn.classList.toggle('open');
            menuBtn.setAttribute('aria-expanded', isOpen);
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && !menuBtn.contains(event.target)) {
                dropdown.classList.remove('active');
                menuBtn.classList.remove('open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close menu on pressing Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                dropdown.classList.remove('active');
                menuBtn.classList.remove('open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
