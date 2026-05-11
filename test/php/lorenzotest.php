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

      <div>
        <input type="checkbox" name="returnRecipeNutrition" id="returnRecipeNutrition">
        return nutrition facts?
      </div>

      <div>
        <button id="searchButton">Search</button>
      </div>
    </div>

    <div id="results"></div>
  </div>
</body>
</html>