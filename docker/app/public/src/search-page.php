<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recipe Finder</title>
    <link rel="stylesheet" href="../css/root.css" />
    <link rel="stylesheet" href="../css/search-page.css" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
</head>

<body>

    <div class="topbar">
        <button class="back-btn" aria-label="Back">
            <img src="../images/search-page/arrow.png" alt="back">
        </button>

        <div class="search-wrap">
            <span class="search-icon">
                <button id="searchButton">
                    <img src="../images/search-page/search.png" alt="search">
                </button>
            </span>

            <input type="text" id="searchInput" placeholder="Search for recipes, cuisines..." />
        </div>

        <button class="filter-btn" id="filterBtn" aria-label="Open filters">
            <img src="../images/search-page/filter.png" alt="filters">
        </button>
    </div>

    <div class="filter-overlay" id="filterOverlay"></div>

    <aside class="filter-panel" id="filterPanel" aria-hidden="true">
        <div class="filter-header">
            <h2 class="filter-title">Filters</h2>
            <button class="close-btn" id="closeFilter">&times;</button>
        </div>

        <div class="filter-body">

            <section class="filter-section">
                <h3>Cuisine</h3>
                <div class="chip-group" id="cuisine">
                    <button>African</button>
                    <button>Asian</button>
                    <button>American</button>
                    <button>British</button>
                    <button>Cajun</button>
                    <button>Caribbean</button>
                    <button>Chinese</button>
                    <button>Eastern European</button>
                    <button>European</button>
                    <button>French</button>
                    <button>German</button>
                    <button>Greek</button>
                    <button>Indian</button>
                    <button>Irish</button>
                    <button>Italian</button>
                    <button>Japanese</button>
                    <button>Jewish</button>
                    <button>Korean</button>
                    <button>Latin American</button>
                    <button>Mediterranean</button>
                    <button>Mexican</button>
                    <button>Middle Eastern</button>
                    <button>Nordic</button>
                    <button>Southern</button>
                    <button>Spanish</button>
                    <button>Thai</button>
                    <button>Vietnamese</button>
                </div>
            </section>

            <section class="filter-section">
                <h3>Diet</h3>
                <div class="chip-group" id="diet">
                    <button>Vegetarian</button>
                    <button>Vegan</button>
                    <button>Ketogenic</button>
                </div>
            </section>

            <section class="filter-section">
                <h3>Cooking Time</h3>
                <div class="chip-group" id="maxTime">
                    <button value="15">≤ 15 min</button>
                    <button value="30">≤ 30 min</button>
                    <button value="60">≤ 60 min</button>
                </div>
            </section>

            <section class="filter-section">
                <h3>Dish Type</h3>
                <div class="chip-group" id="dishType">
                    <button>Main course</button>
                    <button>Dessert</button>
                    <button>Snack</button>
                </div>
            </section>

            <section class="filter-section">
                <h3>Allergies</h3>

                <label><input type="checkbox" class="allergy" value="Dairy"> Dairy</label>
                <label><input type="checkbox" class="allergy" value="Egg"> Egg</label>
                <label><input type="checkbox" class="allergy" value="Gluten"> Gluten</label>
                <label><input type="checkbox" class="allergy" value="Peanut"> Peanut</label>
                <label><input type="checkbox" class="allergy" value="Seafood"> Seafood</label>
            </section>


            <section class="filter-section">
                <h3>Sort</h3>
                <select id="sortSelect">
                    <option value="">Default</option>
                    <option value="popularity">Popularity</option>
                    <option value="healthiness">Healthiness</option>
                    <option value="time">Time</option>
                </select>
            </section>

        </div>

        <div class="filter-footer">
            <button id="clearFilters">Clear</button>
            <button id="applyFilters">Apply</button>
        </div>
    </aside>

    <div class="recipe-list" id="results">

    </div>

    <select id="numberOfResults" hidden>
        <option value="10" selected>10</option>
    </select>

    <input type="checkbox" id="searchByIngredient" hidden />

    <script src="../js/search.js"></script>
</body>

</html>