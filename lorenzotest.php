<?php
// Import and use config
$config = [];
$configPath = __DIR__ . '/config.php';

if (is_file($configPath)) {
    $config = require $configPath;
}

// Get API Key
$apiKey = getenv('SPOONACULAR_API_KEY') ?: ($config['api_key'] ?? '');

// Things to filter by:
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
// funny add-on



?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Spoonacular Recipe Search From Tutorial</title>
</head>
<body>
  <div>
    <h1>Spoonacular Recipe Search</h1>
    <div>
      <input type="text" placeholder="Search for recipes">
      <button>Search</button>
    </div>

    <div id="results"></div>
  </div>

  <script>
    // JavaScript code will go here

	// Example search for burger:
		// https://api.spoonacular.com/recipes/complexSearch?query=burger&number=5&addRecipeNutrition=true&apiKey=YOUR_API_KEY
  </script>
</body>
</html>