<?php
//  DigIn  –  index.php

$page_title = "DigIn – Cook Smarter. Eat Better. Every Day.";

$nav_links = [
  "Recipes"    => "recipe.php",
  "Search"     => "search-page.php",
  "Community"  => "#",
  "Sign In"    => "signin.php",
];

$hero_buttons = [
  ["label" => "Get Started",    "class" => "btn-primary", "href" => "signin.php"],
  ["label" => "Browse Recipes", "class" => "btn-outline", "href" => "recipe.php"],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../css/landing.css" />
  <link rel="stylesheet" href="../css/root.css">
</head>

<body>

  <nav>
    <div class="container">
      <div class="nav-inner">

        <a href="#" class="logo">
          <img src="../images/logoDigIn.svg" alt="DigIn" />
        </a>

        <ul class="nav-links">
          <?php foreach ($nav_links as $label => $href): ?>
            <li>
              <a href="<?= htmlspecialchars($href) ?>"
                <?= $label === 'Sign In' ? 'class="nav-cta"' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>

        <button class="hamburger" id="menuBtn" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>

      </div>
    </div>

    <div class="mobile-nav" id="mobileNav" role="navigation">
      <?php foreach ($nav_links as $label => $href): ?>
        <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>

  <main>

    <section class="hero">
      <div class="container">
        <div class="hero-grid">


          <div class="hero-content">

            <div class="badge">
              <img src="../images/badge.png" alt="Cook. Share. Enjoy" />
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
              <?php foreach ($hero_buttons as $btn): ?>
                <a href="<?= htmlspecialchars($btn['href']) ?>"
                  class="btn <?= htmlspecialchars($btn['class']) ?>">
                  <?php if ($btn['class'] === 'btn-primary'): ?>
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
                  <?= htmlspecialchars($btn['label']) ?>
                </a>
              <?php endforeach; ?>
            </div>

          </div>

          <!-- Right: photo -->
          <div class="hero-visual">
            <div class="blob blob-green" aria-hidden="true"></div>
            <div class="blob blob-peach" aria-hidden="true"></div>

            <div class="hero-photo">
              <img src="../images/hero-food.png" alt="Various delicious meals prepared with DigIn" />
            </div>

            <div class="photo-label">
              <img src="../images/logoDigIn.svg" alt="DigIn" />
            </div>
          </div>

        </div>
      </div>
    </section>


    <section class="features-section">
      <div class="container">
        <div class="features-strip">

          <div class="feat">
            <img src="../images/image 18.svg" alt="Easy Recipes" />
            <div class="feat-text">
              <strong>Easy Recipes</strong>
              <span>Simple steps, delicious result</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/image 22.svg" alt="Meal Plans" />
            <div class="feat-text">
              <strong>Meal Plans</strong>
              <span>Personalized plans for your lifestyle</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/Chef.svg" alt="Community" />
            <div class="feat-text">
              <strong>Community</strong>
              <span>Share, ask and grow together</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/chif.svg" alt="Eat Better" />
            <div class="feat-text">
              <strong>Eat Better</strong>
              <span>Healthier choices for a better you</span>
            </div>
          </div>

        </div>
      </div>
    </section>

  </main>


  <!--FOOTER -->
  <footer>
    <div class="container">
      <p>&copy; <?= date('Y') ?> DigIn. All rights reserved.</p>
    </div>
  </footer>

  <script>
    const menuBtn = document.getElementById('menuBtn');
    const mobileNav = document.getElementById('mobileNav');

    menuBtn.addEventListener('click', () => {
      const isOpen = mobileNav.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', isOpen);
    });

    mobileNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileNav.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
      });
    });
  </script>

</body>

</html>
