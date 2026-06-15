<?php

require_once __DIR__ . '/include-cannot-access-when-loggedin.php';

$pageTitle = 'DigIn - Cook Smarter. Eat Better. Every Day.';

$heroButtons = [
    ['label' => 'Sign Up', 'class' => 'btn-primary', 'href' => 'create-account.php'],
    ['label' => 'Sign In', 'class' => 'btn-outline', 'href' => 'signin.php'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/landing.css" />
    <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
</head>

<body>

    <?php include __DIR__ . '/menu.php'; ?>

    <main>

        <section class="hero">

            <div class="floating-icons" aria-hidden="true">
                <span class="float-icon ficon-leaf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20C4 11 9 4 20 4 19 13 13 20 4 20Z" />
                        <path d="M5.5 18.5C9 14 13 10 18 6" />
                    </svg>
                </span>
                <span class="float-icon ficon-citrus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" />
                        <circle cx="12" cy="12" r="3.5" />
                        <path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4" />
                    </svg>
                </span>
                <span class="float-icon ficon-cherry">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="7.5" cy="17.5" r="3.2" />
                        <circle cx="15.5" cy="18.5" r="3.2" />
                        <path d="M7.8 14.5C8.5 9 11 5 14 3.5" />
                        <path d="M15.8 15.5C16.2 11.5 16.8 8.5 19 6.5" />
                    </svg>
                </span>
                <span class="float-icon ficon-chili">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 3.5c2-.8 4-.5 5 .8" />
                        <path d="M9.5 4C5.5 7 4 12.5 6 17.5c1.6 4 6 5.3 9.2 2.6 4.2-3.5 4.6-10 1.3-14.6C14.7 3.2 11.7 2.6 9.5 4Z" />
                    </svg>
                </span>
                <span class="float-icon ficon-whisk">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v5" />
                        <path d="M8.5 7c-2.5 3-2.8 9 0 13 1.9 2.7 5.1 2.7 7 0 2.8-4 2.5-10 0-13" />
                        <path d="M12 7c-2.5 2.5-2.5 8 0 11" />
                        <path d="M12 7c2.5 2.5 2.5 8 0 11" />
                    </svg>
                </span>
                <span class="float-icon ficon-avocado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.5c4.5 2 7.5 7.3 7.5 12A7.5 7.5 0 1 1 4.5 14.5c0-4.7 3-10 7.5-12Z" />
                        <circle cx="12" cy="15" r="3" />
                    </svg>
                </span>
            </div>

            <div class="container">
                <div class="hero-grid">

                    <div class="hero-content">

                        <div class="badge">
                            <img src="../images/badge.svg" alt="Cook. Share. Enjoy" />
                        </div>

                        <h1 class="headline">
                            Cook Smarter<br>
                            <span class="accent">Eat Better</span><br>
                            Every Day
                        </h1>

                        <div class="divider"></div>

                        <p class="hero-sub">
                            Discover delicious recipes, meal plans,<br>
                            and cooking inspiration tailored to you
                        </p>

                        <div class="btn-group">
                            <?php foreach ($heroButtons as $button): ?>
                                <a href="<?php echo htmlspecialchars($button['href']); ?>"
                                    class="btn <?php echo htmlspecialchars($button['class']); ?>">
                                    <?php if ($button['class'] === 'btn-primary'): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z" />
                                            <line x1="6" y1="17" x2="18" y2="17" />
                                            <line x1="6" y1="13" x2="18" y2="13" />
                                        </svg>
                                    <?php else: ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8" />
                                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                        </svg>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($button['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- Hero image and decorative background -->
                    <div class="hero-visual">
                        <div class="blob blob-green" aria-hidden="true"></div>
                        <div class="blob blob-peach" aria-hidden="true"></div>

                        <div class="hero-photo">
                            <img src="../images/hero-food2.jpeg" alt="Various delicious meals prepared with DigIn" />
                        </div>

                        <div class="photo-label">
                            <img src="../images/digin_logo.svg" alt="DigIn" />
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> DigIn. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>
