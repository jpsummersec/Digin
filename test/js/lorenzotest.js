const searchInput = document.getElementById('searchInput');
const searchButton = document.getElementById('searchButton');
const resultsDiv = document.getElementById('results');
const numberOfResults = document.getElementById('numberOfResults');
const returnRecipeNutrition = document.getElementById('returnRecipeNutrition');
const searchByIngredient = document.getElementById('searchByIngredient');
const cuisine = document.getElementById('cuisine');
const maxTime = document.getElementById('maxTime');
const dishType = document.getElementById('dishType');
const allergyCheckboxes = document.querySelectorAll('.allergy');
const sortSelect = document.getElementById('sortSelect');

searchButton.addEventListener('click', searchRecipes);

function searchRecipes() {
	const searchQuery = searchInput.value.trim();

	if (!searchQuery) {
		alert('Please enter a search term');
		return;
	}

	let intolerances = [];

	document.querySelectorAll('.allergy').forEach(cb => {
		if (cb.checked) {
			intolerances.push(cb.value);
		}
	});

	const apiUrl = `spoonacular-search.php?query=${encodeURIComponent(searchQuery)}&number=${numberOfResults.value}&addRecipeNutrition=${returnRecipeNutrition.checked}&ingredientSearch=${searchByIngredient.checked}&cuisine=${cuisine.value}&maxTime=${maxTime.value}&type=${dishType.value}&intolerances=${intolerances.join(',')}&sort=${sortSelect.value}`;

	resultsDiv.innerHTML = '<p>one moment...</p>';

	fetch(apiUrl)
	.then(response => response.json())
	.then(data => {
		resultsDiv.innerHTML = '';

		const results = Array.isArray(data) ? data : data.results;

		if (!results || results.length === 0) {
			resultsDiv.innerHTML = '<div class="no-results">No recipes found</div>';
			return;
		}

		results.forEach(recipe => {
			const title = recipe.title;
			const image = recipe.image;

			let calories = '', protein = '', fat = '', carbs = '';

			if (recipe.nutrition && recipe.nutrition.nutrients) {
				const nutrients = recipe.nutrition.nutrients;
				const calInfo = nutrients.find(n => n.name === 'Calories');
				const proteinInfo = nutrients.find(n => n.name === 'Protein');
				const fatInfo = nutrients.find(n => n.name === 'Fat');
				const carbsInfo = nutrients.find(n => n.name === 'Carbohydrates');

				if (calInfo) calories = Math.round(calInfo.amount) + ' ' + calInfo.unit;
				if (proteinInfo) protein = Math.round(proteinInfo.amount) + ' ' + proteinInfo.unit;
				if (fatInfo) fat = Math.round(fatInfo.amount) + ' ' + fatInfo.unit;
				if (carbsInfo) carbs = Math.round(carbsInfo.amount) + ' ' + carbsInfo.unit;
			}

			const recipeDiv = document.createElement('div');
			recipeDiv.className = 'recipe';

			recipeDiv.innerHTML = `
				<h3>${title}</h3>
				<img src="${image}" alt="${title}">
				<p><strong>Calories:</strong> ${calories}</p>
				<p><strong>Carbs:</strong> ${carbs}</p>
				<p><strong>Protein:</strong> ${protein}</p>
				<p><strong>Fat:</strong> ${fat}</p>
			`;

			resultsDiv.appendChild(recipeDiv);
		});
	})
	.catch(error => {
		console.error('Error fetching recipes:', error);
		resultsDiv.innerHTML = '<div class="error">An error occurred while fetching recipes. Please try again.</div>';
	});
}