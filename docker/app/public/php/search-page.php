<?php
require_once __DIR__ . '/include-loginrequired.php';
include __DIR__ . '/include-dbhandler.php';

$userId = (int) $_SESSION['user_id'];
$recipeDetailsUrl = '../php/recipe.php';
$cookedRecipes = [];
$savedRecipeIds = [];

// get cooked recipes based on user ID
try {
    $statement = $dbHandler->prepare('
        SELECT r.`recipe_id`, r.`recipe_json`
        FROM `user_cooked_recipe` uc
        INNER JOIN `recipe` r
            ON r.`recipe_id` = uc.`recipe_id`
        WHERE uc.`user_id` = :userId
        ORDER BY uc.`cooked_date` DESC, uc.`cooked_recipe_id` DESC
    ');
    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
    $statement->execute();
    $cookedRecipes = $statement->fetchAll(PDO::FETCH_ASSOC);
    $statement->closeCursor();
} catch (PDOException $exception) {
    die('Select error: ' . $exception->getMessage());
}

// get saved recipe IDs based on user ID
try {
    $statement = $dbHandler->prepare('
        SELECT `recipe_id`
        FROM `user_saved_recipe`
        WHERE `user_id` = :userId
    ');
    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
    $statement->execute();
    $savedRecipeIds = $statement->fetchAll(PDO::FETCH_COLUMN);
    $statement->closeCursor();
} catch (PDOException $exception) {
    die('Select error: ' . $exception->getMessage());
}

// echo "User ID: " . $_SESSION["user_id"] . "<br>";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recipe Finder</title>
    <link rel="stylesheet" href="../css/root.css" />
    <link rel="stylesheet" href="../css/search-page.css" />
    <link rel="stylesheet" href="../css/landing.css" />
    <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
</head>

<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <main class="search-page">
        <div class="topbar">
            <div class="search-wrap">
                <button type="button" class="search-icon" id="searchButton" aria-label="Search recipes">
                    <img src="../images/search-page/search.svg" alt="search-button">
                </button>

                <input type="text" id="searchInput" placeholder="Search for recipes, cuisines..." />
            </div>

            <button type="button" class="filter-btn" id="filterBtn" aria-label="Open filters">
                <img src="../images/search-page/filter.svg" alt="filter-button">
            </button>
        </div>

        <h1 class="results-title" id="resultsTitle" hidden>Results</h1>

        <div id="cookingHistory">
            <?php if (empty($cookedRecipes)) { ?>
                <h1 class="nothing-to-show-yet">Nothing to show yet</h1>
            <?php } else { ?>
                <h1 class="cooking-history-text">Cooking History</h1>
                <div class="recipe-list">
                    <?php foreach ($cookedRecipes as $cookedRecipe) { ?>
                        <?php
                        $recipe = json_decode($cookedRecipe['recipe_json'], true);

                        if (!is_array($recipe)) {
                            continue;
                        }

                        $recipeId = $cookedRecipe['recipe_id'];
                        $title = 'Untitled recipe';
                        $image = '../images/hero-image-fallback.svg';
                        $time = '- minutes';
                        $calories = '- kcal';
                        $spoonacularScore = 0;

                        if (isset($recipe['title'])) {
                            $title = $recipe['title'];
                        }

                        if (!empty($recipe['image'])) {
                            $image = $recipe['image'];
                        }

                        if (isset($recipe['readyInMinutes'])) {
                            $time = $recipe['readyInMinutes'] . ' minutes';
                        }

                        if (isset($recipe['spoonacularScore'])) {
                            $spoonacularScore = $recipe['spoonacularScore'];
                        }

                        $starScore = min(max((float) $spoonacularScore / 20, 0), 5);
                        $fullStars = round($starScore);

                        if (isset($recipe['nutrition']['nutrients']) && is_array($recipe['nutrition']['nutrients'])) {
                            foreach ($recipe['nutrition']['nutrients'] as $nutrient) {
                                if (
                                    isset($nutrient['name']) &&
                                    isset($nutrient['amount']) &&
                                    isset($nutrient['unit']) &&
                                    $nutrient['name'] === 'Calories'
                                ) {
                                    $calories = round($nutrient['amount']) . ' ' . $nutrient['unit'];
                                    break;
                                }
                            }
                        }

                        $isFavorite = in_array($recipeId, $savedRecipeIds);
                        $favoriteAction = 'Add';
                        $favoriteDirection = 'to';
                        $favoritePressed = 'false';
                        $heartImage = 'heart-empty.svg';

                        if ($isFavorite) {
                            $favoriteAction = 'Remove';
                            $favoriteDirection = 'from';
                            $favoritePressed = 'true';
                            $heartImage = 'heart-full.svg';
                        }
                        ?>
                        <article class="recipe">
                            <a class="recipe-link" href="<?php echo htmlspecialchars($recipeDetailsUrl . '?id=' . $recipeId); ?>">
                                <img class="recipe-image" src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
                                <div class="recipe-content">
                                    <h2><?php echo htmlspecialchars($title); ?></h2>
                                    <div class="recipe-meta">
                                        <span><span class="meta-bolt" aria-hidden="true"><img src="../images/search-page/calories.svg"></span><?php echo htmlspecialchars($calories); ?></span>
                                        <span><span class="meta-clock" aria-hidden="true"><img src="../images/search-page/time.svg"></span><?php echo htmlspecialchars($time); ?></span>
                                    </div>
                                    <span class="meta-rating" aria-hidden="true">
                                        <?php for ($star = 0; $star < 5; $star++) { ?>
                                            <?php
                                            $starClass = 'is-empty';

                                            if ($star < $fullStars) {
                                                $starClass = 'is-filled';
                                            }
                                            ?>
                                            <span class="rating-star <?php echo $starClass; ?>" aria-hidden="true">⭐</span>
                                        <?php } ?>
                                    </span>
                                </div>
                            </a>
                            <button type="button" class="favorite-btn" data-recipe-id="<?php echo htmlspecialchars($recipeId); ?>" aria-label="<?php echo $favoriteAction . ' ' . htmlspecialchars($title) . ' ' . $favoriteDirection; ?> favorites" aria-pressed="<?php echo $favoritePressed; ?>">
                                <img src="../images/search-page/<?php echo $heartImage; ?>" alt="" aria-hidden="true">
                            </button>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <div class="filter-overlay" id="filterOverlay"></div>

        <aside class="filter-panel" id="filterPanel" aria-hidden="true">
            <div class="filter-header">
                <h2 class="filter-title">Filters</h2>
                <button class="close-btn" id="closeFilter" aria-label="Close filters"></button>
            </div>

            <div class="filter-body">
                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Dietary Preferences</h3>
                        <button type="button" class="see-all-btn" data-target="diet">See all</button>
                    </div>

                    <div class="chip-group preference-grid" id="diet" data-single-select="true">
                        <button type="button" class="preference-chip" data-value="vegan">
                            Vegan
                            <img src="../images/search-page/vegan.svg" alt="vegan icon">
                        </button>
                        <button type="button" class="preference-chip" data-value="vegetarian">
                            Vegetarian
                            <img src="../images/search-page/vegeterian.svg" alt="vegeterian icon">
                        </button>
                        <button type="button" class="preference-chip" data-value="paleo">
                            Paleo
                            <img src="../images/search-page/paleo.svg" alt="paleo icon">
                        </button>
                        <button type="button" class="preference-chip" data-value="ketogenic">
                            Keto
                            <img src="../images/search-page/keto.svg" alt="keto icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="gluten free">
                            Gluten Free
                            <img src="../images/search-page/gluten-free.svg" alt="gluten-free icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="lacto-vegetarian">
                            Lacto-Veg
                            <img src="../images/search-page/lacto-veg.svg" alt="ovo-veg icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="ovo-vegetarian">
                            Ovo-Veg
                            <img src="../images/search-page/ovo-veg.svg" alt="ovo-veg icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="pescetarian">
                            Pescetarian
                            <img src="../images/search-page/pescetarian.svg" alt="pescetarian icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="primal">
                            Primal
                            <img src="../images/search-page/primal.svg" alt="primal icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="low fodmap">
                            Low FODMAP
                            <img src="../images/search-page/lowfodmap.svg" alt="Low FODMAP icon">
                        </button>
                        <button type="button" class="preference-chip is-extra" data-value="whole30">
                            Whole30
                            <img src="../images/search-page/whole30.svg" alt="Whole30 icon">
                        </button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Allergies</h3>
                        <button type="button" class="see-all-btn" data-target="allergies">See all</button>
                    </div>

                    <div class="chip-group text-chip-group" id="allergies">
                        <button type="button" class="allergy" data-value="Dairy">Dairy</button>
                        <button type="button" class="allergy" data-value="Egg">Eggs</button>
                        <button type="button" class="allergy" data-value="Gluten">Gluten</button>
                        <button type="button" class="allergy" data-value="Grain">Grain</button>
                        <button type="button" class="allergy" data-value="Peanut">Peanuts</button>
                        <button type="button" class="allergy" data-value="Seafood">Seafood</button>
                        <button type="button" class="allergy is-extra" data-value="Sesame">Sesame</button>
                        <button type="button" class="allergy" data-value="Shellfish">Shellfish</button>
                        <button type="button" class="allergy" data-value="Soy">Soy</button>
                        <button type="button" class="allergy" data-value="Sulfite">Sulfite</button>
                        <button type="button" class="allergy" data-value="Tree Nut">Tree Nuts</button>
                        <button type="button" class="allergy is-extra" data-value="Wheat">Wheat</button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Cuisine</h3>
                        <button type="button" class="see-all-btn" data-target="cuisine">See all</button>
                    </div>

                    <div class="chip-group text-chip-group" id="cuisine" data-single-select="true">
                        <button type="button" data-value="Italian">Italian</button>
                        <button type="button" data-value="British">British</button>
                        <button type="button" data-value="American">American</button>
                        <button type="button" data-value="Korean">Korean</button>
                        <button type="button" data-value="Indian">Indian</button>
                        <button type="button" data-value="Spanish">Spanish</button>
                        <button type="button" data-value="French">French</button>
                        <button type="button" data-value="Thai">Thai</button>
                        <button type="button" data-value="German">German</button>
                        <button type="button" data-value="Chinese">Chinese</button>
                        <button type="button" class="is-extra" data-value="African">African</button>
                        <button type="button" class="is-extra" data-value="Asian">Asian</button>
                        <button type="button" class="is-extra" data-value="Cajun">Cajun</button>
                        <button type="button" class="is-extra" data-value="Caribbean">Caribbean</button>
                        <button type="button" class="is-extra" data-value="Eastern European">Eastern European</button>
                        <button type="button" class="is-extra" data-value="European">European</button>
                        <button type="button" class="is-extra" data-value="Greek">Greek</button>
                        <button type="button" class="is-extra" data-value="Irish">Irish</button>
                        <button type="button" class="is-extra" data-value="Japanese">Japanese</button>
                        <button type="button" class="is-extra" data-value="Jewish">Jewish</button>
                        <button type="button" class="is-extra" data-value="Latin American">Latin American</button>
                        <button type="button" class="is-extra" data-value="Mediterranean">Mediterranean</button>
                        <button type="button" class="is-extra" data-value="Mexican">Mexican</button>
                        <button type="button" class="is-extra" data-value="Middle Eastern">Middle Eastern</button>
                        <button type="button" class="is-extra" data-value="Nordic">Nordic</button>
                        <button type="button" class="is-extra" data-value="Southern">Southern</button>
                        <button type="button" class="is-extra" data-value="Vietnamese">Vietnamese</button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Dish Type</h3>
                        <button type="button" class="see-all-btn" data-target="dishType">See all</button>
                    </div>

                    <div class="chip-group text-chip-group" id="dishType" data-single-select="true">
                        <button type="button" data-value="main course" data-query="pasta">Pasta</button>
                        <button type="button" data-value="main course" data-query="burger">Burger</button>
                        <button type="button" data-value="main course" data-query="curry">Curry</button>
                        <button type="button" data-value="main course" data-query="chicken">Chicken</button>
                        <button type="button" data-value="main course" data-query="shoarma">Shoarma</button>
                        <button type="button" data-value="main course" data-query="kapsalon">Kapsalon</button>
                        <button type="button" data-value="fingerfood" data-query="sushi">Sushi</button>
                        <button type="button" data-value="soup">Soup</button>
                        <button type="button" data-value="salad">Salad</button>
                        <button type="button" data-value="dessert">Dessert</button>
                        <button type="button" class="is-extra" data-value="side dish">Side dish</button>
                        <button type="button" class="is-extra" data-value="appetizer">Appetizer</button>
                        <button type="button" class="is-extra" data-value="bread">Bread</button>
                        <button type="button" class="is-extra" data-value="breakfast">Breakfast</button>
                        <button type="button" class="is-extra" data-value="beverage">Beverage</button>
                        <button type="button" class="is-extra" data-value="sauce">Sauce</button>
                        <button type="button" class="is-extra" data-value="marinade">Marinade</button>
                        <button type="button" class="is-extra" data-value="snack">Snack</button>
                        <button type="button" class="is-extra" data-value="drink">Drink</button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Cooking Time</h3>
                    </div>

                    <div class="chip-group text-chip-group" id="maxTime" data-single-select="true">
                        <button type="button" data-value="15">15 min</button>
                        <button type="button" data-value="30">30 min</button>
                        <button type="button" data-value="60">60 min</button>
                        <button type="button" data-value="360">60+ min</button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="calorie-control">
                        <h3>Calories</h3>
                        <output id="calorieRangeValue" for="minCalories maxCalories" aria-live="polite">Any - Any</output>

                        <div class="calorie-slider" aria-label="Calories range">
                            <div class="calorie-slider-track"></div>
                            <div class="calorie-slider-range" id="calorieSliderRange"></div>
                            <input type="range" id="minCalories" min="0" max="850" step="50" value="0" aria-label="Minimum calories">
                            <input type="range" id="maxCalories" min="0" max="850" step="50" value="850" aria-label="Maximum calories">
                        </div>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Sort Type</h3>
                    </div>

                    <div class="chip-group text-chip-group" id="sortSelect" data-single-select="true">
                        <button type="button" data-value="popularity">Popularity</button>
                        <button type="button" data-value="spoonacularScore">Score</button>
                        <button type="button" data-value="time">Time</button>
                        <button type="button" data-value="healthScore">Healthiness</button>
                        <button type="button" data-value="price">Price</button>
                        <button type="button" data-value="random">Random</button>
                        <button type="button" data-value="calories">Calories</button>
                        <button type="button" data-value="likes">Likes</button>
                    </div>

                    <div class="sort-direction">
                        <button type="button" id="sortAsc" class="sort-btn active">Ascending</button>
                        <button type="button" id="sortDesc" class="sort-btn">Descending</button>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section-title">
                        <h3>Search Options</h3>
                    </div>

                    <label class="range-control" for="numberOfResults">
                        <span>Quantity of results</span>
                        <output id="numberOfResultsValue">10</output>
                        <input type="range" name="numberOfResults" id="numberOfResults" min="1" max="10" value="10">
                    </label>

                    <label class="toggle-control" for="searchByIngredient">
                        <input type="checkbox" id="searchByIngredient">
                        <span>Search by ingredients</span>
                    </label>
                </section>
            </div>

            <div class="filter-footer">
                <button type="button" id="clearFilters">Clear</button>
                <button type="button" id="applyFilters">Apply</button>
            </div>
        </aside>

        <div class="recipe-list" id="results"></div>

        
    <section class="features-section">
      <div class="container">
        <div class="features-strip">

          <div class="feat">
            <img src="../images/cooking_book.svg" alt="Easy Recipes" />
            <div class="feat-text">
              <strong>Easy Recipes</strong>
              <span>Simple steps, delicious result</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/menu_card.svg" alt="Menu card" />
            <div class="feat-text">
              <strong>Fresh Ingredients</strong>
              <span>Sourced locally and delivered at peak freshness</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/Chef.svg" alt="Community" />
            <div class="feat-text">
              <strong>Chef-Crafted</strong>
              <span>Restaurant-quality meals made by professional chefs</span>
            </div>
          </div>

          <div class="feat">
            <img src="../images/chef2.svg" alt="Eat Better" />
            <div class="feat-text">
              <strong>Eat Better</strong>
              <span>Healthier choices for a better you</span>
            </div>
          </div>

        </div>
      </div>
    </section>

    </main>

    <script>
        const savedRecipeIds = <?php echo json_encode(array_map('strval', $savedRecipeIds)); ?>;
    </script>
    <script src="../js/search.js"></script>
</body>

</html>
