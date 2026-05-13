<!-- // Things to filter by:
// Dish origin
// Dish ease of cooking (eg. <30 minutes, one-pot)
// 

// Other:
// random dish


// Useful things:
// Get Analyzed Recipe Instructions, will give detailed steps with .jpg as well

// Extract Recipe from Website
// can be used for importing recipes?

// Search Grocery Products
// to see what kind of grocery products could be bought for the recipie, might be reigon-specific though

// Search Menu Items
// people can replicate big mac or starbucks mocha for example

// Random Food Joke
// funny add-on

// Random Food Trivia
// funny add-on -->

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Spoonacular Recipe Search From Tutorial</title>

  <link rel="stylesheet" href="../css/lorenzotest.css">
  <script src="../js/lorenzotest.js" defer></script>
</head>
<body>
  <div> 
    <h1>Spoonacular Recipe Search</h1>

    <div>
      <div>
        <input type="text" id="searchInput" placeholder="Start Digging In!">
      </div>

      <div>
        <input type="range" name="numberOfResults" id="numberOfResults" min="1" max="10" value="5">
        Quantity of results
      </div>

      <!-- Cuisine -->
      <div class="filter-group">
        <label>Cuisine:</label>
        <select id="cuisine">
          <option value="">Any</option>
          <option value="African">African</option>
          <option value="Asian">Asian</option>
          <option value="American">American</option>
          <option value="American">British</option>
          <option value="Cajun">Cajun</option>
          <option value="Caribbean">Caribbean</option>
          <option value="Chinese">Chinese</option>
          <option value="Eastern European">Eastern European</option>
          <option value="European">European</option>
          <option value="French">French</option>
          <option value="German">German</option>
          <option value="Greek">Greek</option>
          <option value="Indian">Indian</option>
          <option value="Irish">Irish</option>
          <option value="Italian">Italian</option>
          <option value="Japanese">Japanese</option>
          <option value="Jewish">Jewish</option>
          <option value="Korean">Korean</option>
          <option value="Latin American">Latin American</option>
          <option value="German">Mediterranean</option>
          <option value="Greek">Mexican</option>
          <option value="Indian">Middle Eastern</option>
          <option value="Irish">Nordic</option>
          <option value="Italian">Southern</option>
          <option value="Spanish">Spanish</option>
          <option value="Thai">Thai</option>
          <option value="Vietnamese">Vietnamese</option>
        </select>
      </div>

       <!-- Diet type -->
      <div class="filter-group">
        <label>Diet type:</label>
        <select id="diet">
          <option value="">No diet</option>
          <option value="gluten free">Gluten Free</option>
          <option value="ketogenic">Ketogenic</option>
          <option value="vegetarian">Vegetarian</option>
          <option value="lacto-vegetarian">Lacto-Vegetarian</option>
          <option value="ovo-vegetarian">Ovo-Vegetarian</option>
          <option value="vegan">Vegan</option>
          <option value="pescetarian">Pescetarian</option>
          <option value="paleo">Paleo</option>
          <option value="primal">Primal</option>
          <option value="low fodmap">Low FODMAP</option>
          <option value="whole30">Whole30</option>
        </select>
      </div>

      <!-- Cooking Time -->
      <div class="filter-group">
        <label>Cooking Time:</label>
        <select id="maxTime">
          <option value="">Any</option>
          <option value="15">≤ 15 min</option>
          <option value="30">≤ 30 min</option>
          <option value="60">≤ 60 min</option>
          <option value="360">> 60 min</option>
        </select>
      </div>

      <!-- Type of Dish -->
      <div class="filter-group">
        <label>Type:</label>
        <select id="dishType">
          <option value="">Any</option>
          <option value="main course">Main Course</option>
          <option value="side dish">Side dish</option>
          <option value="dessert">Dessert</option>
          <option value="appetizer">Appetizer</option>
          <option value="appetizer">Appetizer</option>
          <option value="salad">Salad</option>
          <option value="bread">Bread</option>
          <option value="breakfast">Breakfast</option>
          <option value="soup">Soup</option>
          <option value="beverage">Beverage</option>
          <option value="sauce">Sauce</option>
          <option value="marinade">Marinade</option>
          <option value="fingerfood">Fingerfood</option>
          <option value="snack">Snack</option>
          <option value="drink">Drink</option>
        </select>
      </div>

      <!-- Allergies / Intolerances -->
      <div>
        <label>Allergies / Intolerances</label>
        <div>
          <label><input type="checkbox" class="allergy" value="Dairy"> Dairy</label>
          <label><input type="checkbox" class="allergy" value="Egg"> Eggs</label>
          <label><input type="checkbox" class="allergy" value="Gluten"> Gluten</label>
          <label><input type="checkbox" class="allergy" value="Grain"> Grain</label>
          <label><input type="checkbox" class="allergy" value="Peanut"> Peanuts</label>
          <label><input type="checkbox" class="allergy" value="Seafood"> Seafood</label>
          <label><input type="checkbox" class="allergy" value="Sesame"> Sesame</label>
          <label><input type="checkbox" class="allergy" value="Shellfish"> Shellfish</label>
          <label><input type="checkbox" class="allergy" value="Soy"> Soy</label>
          <label><input type="checkbox" class="allergy" value="Sulfite"> Sulfite</label>
          <label><input type="checkbox" class="allergy" value="Tree Nut"> Tree Nuts</label>
          <label><input type="checkbox" class="allergy" value="Wheat"> Wheat</label>
        </div>
      </div>

      <div class="filter-group">
        <label>Sort Type:</label>
        <select id="sortSelect">
          <option value="">Any</option>
          <option value="popularity">Popularity</option>
          <option value="time">Time</option>
          <option value="healthiness">Healthiness</option>
          <option value="price">Price</option>
          <option value="random">Random</option>
          <option value="calories">Calories</option>
          <option value="max-used-ingredients">Max used ingredients</option>
        </select>
      </div>

      <!-- Return nutrition facts? -->
      <div>
        <input type="checkbox" name="returnRecipeNutrition" id="returnRecipeNutrition">
        return nutrition facts?
      </div>

      <!-- Search by ingredients -->
      <div>
        <input type="checkbox" id="searchByIngredient">
        Search by ingredients (No filters will be applied)
      </div>

      <div>
        <button id="searchButton">Search</button>
      </div>
    </div>

    <div id="results">
    </div>
  </div>
</body>
</html>