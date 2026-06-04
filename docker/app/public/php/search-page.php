<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recipe Finder</title>
    <link rel="stylesheet" href="../css/root.css" />
    <link rel="stylesheet" href="../css/search-page.css" />
</head>

<body>
    <main class="search-page">
        <div class="topbar">
            <button type="button" class="back-btn" aria-label="Back">
               <img src="../images/search-page/arrow.svg" alt="back-arrow">
            </button>

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

        <h1 class="results-title">Results</h1>

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
                    <div class="filter-section-title">
                        <h3>Sort Type</h3>
                    </div>

                    <div class="chip-group text-chip-group" id="sortSelect" data-single-select="true">
                        <button type="button" data-value="popularity">Popularity</button>
                        <button type="button" data-value="meta-score">Score</button>
                        <button type="button" data-value="time">Time</button>
                        <button type="button" data-value="healthiness">Healthiness</button>
                        <button type="button" data-value="price">Price</button>
                        <button type="button" data-value="random">Random</button>
                        <button type="button" data-value="calories">Calories</button>
                        <button type="button" data-value="max-used-ingredients">Max used ingredients</button>
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

    </main>

    <script src="../js/search.js"></script>
</body>

</html>
