const searchInput = document.getElementById('searchInput');
const searchButton = document.getElementById('searchButton');
const resultsDiv = document.getElementById('results');
const resultsTitle = document.getElementById('resultsTitle');
const cookingHistory = document.getElementById('cookingHistory');
const numberOfResults = document.getElementById('numberOfResults');
const numberOfResultsValue = document.getElementById('numberOfResultsValue');
const searchByIngredient = document.getElementById('searchByIngredient');
const filterBtn = document.getElementById('filterBtn');
const filterPanel = document.getElementById('filterPanel');
const filterOverlay = document.getElementById('filterOverlay');
const closeFilter = document.getElementById('closeFilter');
const applyFilters = document.getElementById('applyFilters');
const clearFilters = document.getElementById('clearFilters');
const sortAscBtn = document.getElementById('sortAsc');
const sortDescBtn = document.getElementById('sortDesc');
let sortDirection = 'asc';

const recipeDetailsUrl = '../php/recipe.php';

function openFilter() {
	filterPanel.classList.add('open');
	filterOverlay.classList.add('active');
	filterPanel.setAttribute('aria-hidden', 'false');
}

function closeFilterPanel() {
	filterPanel.classList.remove('open');
	filterOverlay.classList.remove('active');
	filterPanel.setAttribute('aria-hidden', 'true');
}

function escapeHtml(value) {
	return String(value ?? '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function getSelectedButton(groupId) {
	return document.querySelector(`#${groupId} button.active`);
}

function getSelectedValue(groupId) {
	const button = getSelectedButton(groupId);
	return button ? button.dataset.value || button.value || button.textContent.trim() : '';
}

function getSelectedQuery(groupId) {
	const button = getSelectedButton(groupId);
	return button ? button.dataset.query || '' : '';
}

function getSelectedIntolerances() {
	return Array.from(document.querySelectorAll('.allergy.active'))
		.map(button => button.dataset.value || button.textContent.trim());
}

function hasSelectedFilters() {
	return Boolean(
		getSelectedValue('diet') ||
		getSelectedValue('cuisine') ||
		getSelectedValue('dishType') ||
		getSelectedValue('maxTime') ||
		getSelectedValue('sortSelect') ||
		getSelectedIntolerances().length
	);
}

function getCalories(recipe) {
	const nutrients = recipe.nutrition?.nutrients || [];
	const calories = nutrients.find(nutrient => nutrient.name === 'Calories');

	if (!calories) {
		return '- kcal';
	}

	return `${Math.round(calories.amount)} ${calories.unit}`;
}

function getIngredients(recipe) {
	const ingredients =
		recipe.extendedIngredients ||
		recipe.usedIngredients ||
		recipe.missedIngredients ||
		[];

	return ingredients
		.map(ingredient => ingredient.original || ingredient.originalString || ingredient.name)
		.filter(Boolean);
}

function getStarRating(recipe) {
	const maxStars = 5;
	const rawScore = Number(recipe.spoonacularScore) || 0;
	const starScore = Math.min(Math.max(rawScore / 20, 0), maxStars);
	const fullStars = Math.round(starScore);
	return Array.from({ length: maxStars }, (_, index) => {
		const className = index < fullStars ? 'rating-star is-filled' : 'rating-star is-empty';
		return `<span class="${className}" aria-hidden="true">⭐</span>`;
	}).join('');
}

function renderRecipe(recipe) {
	const id = encodeURIComponent(recipe.id);
	const title = escapeHtml(recipe.title || 'Untitled recipe');
	const image = escapeHtml(recipe.image || '../images/');
	const time = recipe.readyInMinutes ? `${recipe.readyInMinutes} minutes` : '- minutes';
	const calories = escapeHtml(getCalories(recipe));
	const ingredients = getIngredients(recipe);
	const rating = getStarRating(recipe);
	const previewIngredients = ingredients.slice(0, 6);
	const hasMoreIngredients = ingredients.length > previewIngredients.length;
	const ingredientItems = previewIngredients
		.map(ingredient => `<li>${escapeHtml(ingredient)}</li>`)
		.join('');

	const recipeDiv = document.createElement('article');
	recipeDiv.className = 'recipe';

	recipeDiv.innerHTML = `
		<a class="recipe-link" href="${recipeDetailsUrl}?id=${id}">
			<img class="recipe-image" src="${image}" alt="${title}" loading="lazy">
			<div class="recipe-content">
				<h2>${title}</h2>
				<div class="recipe-meta">
					<span><span class="meta-bolt" aria-hidden="true"><img src = "../images/search-page/calories.svg"></span>${calories}</span>
					<span><span class="meta-clock" aria-hidden="true"><img src = "../images/search-page/time.svg"></span>${time}</span>
				</div>
				<div class="ingredients-block">
					<h3>Ingredients</h3>
					<ul class="ingredients">
						${ingredientItems}
						${hasMoreIngredients ? '<li>...</li>' : ''}
					</ul>
				</div>
				<span class="meta-rating" aria-hidden="true">${rating}</span>
			</div>
		</a>
	`;

	resultsDiv.appendChild(recipeDiv);
}

function setLoadingState() {
	resultsDiv.innerHTML = '<div class="status-message">One moment...</div>';
}

function setMessage(message, className = 'status-message') {
	resultsDiv.innerHTML = `<div class="${className}">${escapeHtml(message)}</div>`;
}

function buildSearchParams() {
	const typedQuery = searchInput.value.trim();
	const ingredientMode = searchByIngredient.checked;
	const dishQuery = getSelectedQuery('dishType');
	const fallbackQuery = hasSelectedFilters() ? (dishQuery || 'recipe') : '';
	const query = typedQuery || fallbackQuery;

	if (!query) {
		return null;
	}

	const params = new URLSearchParams({
		query,
		number: numberOfResults.value,
		addRecipeNutrition: 'true',
		ingredientSearch: ingredientMode ? 'true' : 'false',
		sortDirection: sortDirection 
	});

	if (!ingredientMode) {
		params.set('cuisine', getSelectedValue('cuisine'));
		params.set('diet', getSelectedValue('diet'));
		params.set('maxTime', getSelectedValue('maxTime'));
		params.set('type', getSelectedValue('dishType'));
		params.set('intolerances', getSelectedIntolerances().join(','));
		params.set('sort', getSelectedValue('sortSelect'));
	}

	return params;
}

function searchRecipes() {
	const params = buildSearchParams();

	if (!params) {
		alert('Please enter a search term or choose a filter');
		return;
	}

	cookingHistory.hidden = true;
	resultsTitle.hidden = false;
	setLoadingState();

	fetch(`../php/spoonacular-search.php?${params.toString()}`)
		.then(response => {
			if (!response.ok) {
				throw new Error(`Request failed with status ${response.status}`);
			}

			return response.json();
		})
		.then(data => {
			if (data.error) {
				throw new Error(data.error);
			}

			const results = Array.isArray(data) ? data : data.results;
			resultsDiv.innerHTML = '';

			if (!results || results.length === 0) {
				setMessage('No recipes found', 'no-results');
				return;
			}

			results.forEach(renderRecipe);
		})
		.catch(error => {
			console.error('Error fetching recipes:', error);
			setMessage('An error occurred while fetching recipes. Please try again.', 'error');
		});
}

document.querySelectorAll('.chip-group button').forEach(button => {
	button.addEventListener('click', () => {
		const group = button.closest('.chip-group');

		if (group.dataset.singleSelect === 'true') {
			group.querySelectorAll('button').forEach(groupButton => {
				if (groupButton !== button) {
					groupButton.classList.remove('active');
				}
			});
		}

		button.classList.toggle('active');
	});
});

document.querySelectorAll('.see-all-btn').forEach(button => {
	button.addEventListener('click', () => {
		const group = document.getElementById(button.dataset.target);
		const expanded = group.classList.toggle('is-expanded');
		button.textContent = expanded ? 'Show less' : 'See all';
	});
});

searchButton.addEventListener('click', searchRecipes);

searchInput.addEventListener('keydown', event => {
	if (event.key === 'Enter') {
		searchRecipes();
	}
});

filterBtn.addEventListener('click', openFilter);
closeFilter.addEventListener('click', closeFilterPanel);
filterOverlay.addEventListener('click', closeFilterPanel);

applyFilters.addEventListener('click', () => {
	closeFilterPanel();
	searchRecipes();
});

function setSortDirection(direction) {
	sortDirection = direction;

	sortAscBtn.classList.toggle('active', direction === 'asc');
	sortDescBtn.classList.toggle('active', direction === 'desc');
}

sortAscBtn?.addEventListener('click', () => setSortDirection('asc'));
sortDescBtn?.addEventListener('click', () => setSortDirection('desc'));

clearFilters.addEventListener('click', () => {
	document.querySelectorAll('.chip-group button').forEach(button => button.classList.remove('active'));
	document.querySelectorAll('.chip-group.is-expanded').forEach(group => group.classList.remove('is-expanded'));
	document.querySelectorAll('.see-all-btn').forEach(button => button.textContent = 'See all');
	numberOfResults.value = '10';
	numberOfResultsValue.value = '10';
	numberOfResultsValue.textContent = '10';
	searchByIngredient.checked = false;
});

numberOfResults.addEventListener('input', () => {
	numberOfResultsValue.value = numberOfResults.value;
	numberOfResultsValue.textContent = numberOfResults.value;
});

document.addEventListener('keydown', event => {
	if (event.key === 'Escape') {
		closeFilterPanel();
	}
});
