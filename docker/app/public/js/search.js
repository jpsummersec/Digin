const searchInput = document.getElementById('searchInput');
const searchButton = document.getElementById('searchButton');
const resultsContainer = document.getElementById('results');
const resultsTitle = document.getElementById('resultsTitle');
const cookingHistory = document.getElementById('cookingHistory');
const numberOfResults = document.getElementById('numberOfResults');
const numberOfResultsValue = document.getElementById('numberOfResultsValue');
const searchByIngredient = document.getElementById('searchByIngredient');
const filterButton = document.getElementById('filterBtn');
const filterPanel = document.getElementById('filterPanel');
const filterOverlay = document.getElementById('filterOverlay');
const closeFilterButton = document.getElementById('closeFilter');
const applyFiltersButton = document.getElementById('applyFilters');
const clearFiltersButton = document.getElementById('clearFilters');
const ascendingSortButton = document.getElementById('sortAsc');
const descendingSortButton = document.getElementById('sortDesc');
const minCaloriesInput = document.getElementById('minCalories');
const maxCaloriesInput = document.getElementById('maxCalories');
const calorieRangeValue = document.getElementById('calorieRangeValue');
const calorieSliderRange = document.getElementById('calorieSliderRange');
let sortDirection = 'asc';

const recipeDetailsUrl = '../php/recipe.php';
const CALORIE_MIN_ANY_LIMIT = 50;
const CALORIE_MAX_ANY_LIMIT = 800;
const CALORIE_SLIDER_MIN = 0;
const CALORIE_SLIDER_MAX = 850;

function openFilter()
{
    filterPanel.classList.add('open');
    filterOverlay.classList.add('active');
    filterPanel.setAttribute('aria-hidden', 'false');
}

function closeFilterPanel()
{
    filterPanel.classList.remove('open');
    filterOverlay.classList.remove('active');
    filterPanel.setAttribute('aria-hidden', 'true');
}

function escapeHtml(value)
{
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getSelectedButton(groupId)
{
    return document.querySelector(`#${groupId} button.active`);
}

function getSelectedValue(groupId)
{
    const button = getSelectedButton(groupId);
    return button ? button.dataset.value || button.value || button.textContent.trim() : '';
}

function getSelectedQuery(groupId)
{
    const button = getSelectedButton(groupId);
    return button ? button.dataset.query || '' : '';
}

function getSelectedIntolerances()
{
    return Array.from(document.querySelectorAll('.allergy.active'))
        .map(button => button.dataset.value || button.textContent.trim());
}

function getCalorieBounds()
{
    const minCalories = Number(minCaloriesInput.value);
    const maxCalories = Number(maxCaloriesInput.value);

    return {
        minCalories: minCalories >= CALORIE_MIN_ANY_LIMIT && minCalories <= CALORIE_MAX_ANY_LIMIT ? String(minCalories) : '',
        maxCalories: maxCalories >= CALORIE_MIN_ANY_LIMIT && maxCalories <= CALORIE_MAX_ANY_LIMIT ? String(maxCalories) : '',
    };
}

function hasSelectedCalories()
{
    const calorieBounds = getCalorieBounds();
    return Boolean(calorieBounds.minCalories || calorieBounds.maxCalories);
}

function hasSelectedFilters()
{
    return Boolean(
        getSelectedValue('diet') ||
        getSelectedValue('cuisine') ||
        getSelectedValue('dishType') ||
        getSelectedValue('maxTime') ||
        getSelectedValue('sortSelect') ||
        hasSelectedCalories() ||
        getSelectedIntolerances().length
    );
}

function getCalories(recipe)
{
    const nutrients = recipe.nutrition?.nutrients || [];
    const calories = nutrients.find(nutrient => nutrient.name === 'Calories');

    if (!calories)
    {
        return '- kcal';
    }

    return `${Math.round(calories.amount)} ${calories.unit}`;
}

function getIngredients(recipe)
{
    const ingredients =
        recipe.extendedIngredients ||
        recipe.usedIngredients ||
        recipe.missedIngredients ||
        [];

    return ingredients
        .map(ingredient => ingredient.original || ingredient.originalString || ingredient.name)
        .filter(Boolean);
}

function getStarRating(recipe)
{
    const maxStars = 5;
    const rawScore = Number(recipe.spoonacularScore) || 0;
    const starScore = Math.min(Math.max(rawScore / 20, 0), maxStars);
    const fullStars = Math.round(starScore);
    return Array.from({ length: maxStars }, (_, index) =>
    {
        const className = index < fullStars ? 'rating-star is-filled' : 'rating-star is-empty';
        return `<span class="${className}" aria-hidden="true">⭐</span>`;
    }).join('');
}

function renderRecipe(recipe)
{
    const id = encodeURIComponent(recipe.id);
    const recipeId = String(recipe.id);
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
    let favoriteAction = 'Add';
    let favoriteDirection = 'to';
    let favoritePressed = 'false';
    let heartImage = 'heart-empty.svg';

    if (savedRecipeIds.includes(recipeId))
    {
        favoriteAction = 'Remove';
        favoriteDirection = 'from';
        favoritePressed = 'true';
        heartImage = 'heart-full.svg';
    }

    const recipeElement = document.createElement('article');
    recipeElement.className = 'recipe';

    recipeElement.innerHTML = `
        <a class="recipe-link" href="${recipeDetailsUrl}?id=${id}">
            <img class="recipe-image" src="${image}" alt="${title}" loading="lazy">
            <div class="recipe-content">
                <h2>${title}</h2>
                <div class="recipe-meta">
                    <span><span class="meta-bolt" aria-hidden="true"><img src="../images/search-page/calories.svg"></span>${calories}</span>
                    <span><span class="meta-clock" aria-hidden="true"><img src="../images/search-page/time.svg"></span>${time}</span>
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
        <button type="button" class="favorite-btn" data-recipe-id="${id}" aria-label="${favoriteAction} ${title} ${favoriteDirection} favorites" aria-pressed="${favoritePressed}">
            <img src="../images/search-page/${heartImage}" alt="" aria-hidden="true">
        </button>
    `;

    resultsContainer.appendChild(recipeElement);
}

function setLoadingState()
{
    resultsContainer.innerHTML = '<div class="status-message">One moment...</div>';
}

function setMessage(message, className = 'status-message')
{
    resultsContainer.innerHTML = `<div class="${className}">${escapeHtml(message)}</div>`;
}

function formatCalorieRange(calorieBounds)
{
    const minCalories = calorieBounds.minCalories || 'Any';
    const maxCalories = calorieBounds.maxCalories || 'Any';

    if (calorieBounds.minCalories && calorieBounds.maxCalories)
    {
        return `${minCalories} - ${maxCalories} kcal`;
    }

    if (calorieBounds.minCalories)
    {
        return `${minCalories}+ kcal - Any`;
    }

    if (calorieBounds.maxCalories)
    {
        return `Any - ${maxCalories} kcal`;
    }

    return 'Any - Any';
}

function updateCalorieSlider(changedInput = null)
{
    let minCalories = Number(minCaloriesInput.value);
    let maxCalories = Number(maxCaloriesInput.value);

    if (minCalories < CALORIE_MIN_ANY_LIMIT)
    {
        minCalories = CALORIE_SLIDER_MIN;
    }

    if (minCalories > CALORIE_MAX_ANY_LIMIT)
    {
        minCalories = CALORIE_MAX_ANY_LIMIT;
    }

    if (maxCalories < CALORIE_MIN_ANY_LIMIT)
    {
        maxCalories = CALORIE_MIN_ANY_LIMIT;
    }

    if (maxCalories > CALORIE_MAX_ANY_LIMIT)
    {
        maxCalories = CALORIE_SLIDER_MAX;
    }

    if (minCalories > maxCalories)
    {
        if (changedInput === minCaloriesInput)
        {
            maxCalories = minCalories;
        }
        else
        {
            minCalories = maxCalories;
        }
    }

    minCaloriesInput.value = String(minCalories);
    maxCaloriesInput.value = String(maxCalories);

    const calorieBounds = getCalorieBounds();
    const minPercent = ((minCalories - CALORIE_SLIDER_MIN) / (CALORIE_SLIDER_MAX - CALORIE_SLIDER_MIN)) * 100;
    const maxPercent = ((maxCalories - CALORIE_SLIDER_MIN) / (CALORIE_SLIDER_MAX - CALORIE_SLIDER_MIN)) * 100;

    calorieSliderRange.style.left = `${minPercent}%`;
    calorieSliderRange.style.right = `${100 - maxPercent}%`;
    minCaloriesInput.style.zIndex = minCalories >= maxCalories ? '3' : '2';
    maxCaloriesInput.style.zIndex = minCalories >= maxCalories ? '2' : '3';
    calorieRangeValue.textContent = formatCalorieRange(calorieBounds);
}

function resetCalorieSlider()
{
    minCaloriesInput.value = String(CALORIE_SLIDER_MIN);
    maxCaloriesInput.value = String(CALORIE_SLIDER_MAX);
    updateCalorieSlider();
}

function buildSearchParams()
{
    const typedQuery = searchInput.value.trim();
    const ingredientMode = searchByIngredient.checked;
    const dishQuery = getSelectedQuery('dishType');
    const fallbackQuery = hasSelectedFilters() ? (dishQuery || 'recipe') : '';
    const query = typedQuery || fallbackQuery;
    const calorieBounds = getCalorieBounds();

    if (!query)
    {
        return null;
    }

    const parameters = new URLSearchParams({
        query,
        number: numberOfResults.value,
        addRecipeNutrition: 'true',
        ingredientSearch: ingredientMode ? 'true' : 'false',
        sortDirection: sortDirection,
        minCalories: calorieBounds.minCalories,
        maxCalories: calorieBounds.maxCalories
    });

    if (!ingredientMode)
    {
        parameters.set('cuisine', getSelectedValue('cuisine'));
        parameters.set('diet', getSelectedValue('diet'));
        parameters.set('maxTime', getSelectedValue('maxTime'));
        parameters.set('type', getSelectedValue('dishType'));
        parameters.set('intolerances', getSelectedIntolerances().join(','));
        parameters.set('sort', getSelectedValue('sortSelect'));
    }

    return parameters;
}

function searchRecipes()
{
    const parameters = buildSearchParams();

    if (!parameters)
    {
        alert('Please enter a search term or choose a filter');
        return;
    }

    cookingHistory.hidden = true;
    resultsTitle.hidden = false;
    setLoadingState();

    fetch(`../php/spoonacular-search.php?${parameters.toString()}`)
        .then(response =>
        {
            if (!response.ok)
            {
                throw new Error(`Request failed with status ${response.status}`);
            }

            return response.json();
        })
        .then(data =>
        {
            if (data.error)
            {
                throw new Error(data.error);
            }

            const results = Array.isArray(data) ? data : data.results;
            resultsContainer.innerHTML = '';

            if (!results || results.length === 0)
            {
                setMessage('No recipes found', 'no-results');
                return;
            }

            results.forEach(renderRecipe);
        })
        .catch(() =>
        {
            setMessage('An error occurred while fetching recipes. Please try again.', 'error');
        });
}

document.querySelectorAll('.chip-group button').forEach(button =>
{
    button.addEventListener('click', () =>
    {
        const group = button.closest('.chip-group');

        if (group.dataset.singleSelect === 'true')
        {
            group.querySelectorAll('button').forEach(groupButton =>
            {
                if (groupButton !== button)
                {
                    groupButton.classList.remove('active');
                }
            });
        }

        button.classList.toggle('active');
    });
});

document.querySelectorAll('.see-all-btn').forEach(button =>
{
    button.addEventListener('click', () =>
    {
        const group = document.getElementById(button.dataset.target);
        const expanded = group.classList.toggle('is-expanded');
        button.textContent = expanded ? 'Show less' : 'See all';
    });
});

searchButton.addEventListener('click', searchRecipes);

searchInput.addEventListener('keydown', event =>
{
    if (event.key === 'Enter')
    {
        searchRecipes();
    }
});

filterButton.addEventListener('click', openFilter);
closeFilterButton.addEventListener('click', closeFilterPanel);
filterOverlay.addEventListener('click', closeFilterPanel);

applyFiltersButton.addEventListener('click', () =>
{
    closeFilterPanel();
    searchRecipes();
});

function setSortDirection(direction)
{
    sortDirection = direction;

    ascendingSortButton.classList.toggle('active', direction === 'asc');
    descendingSortButton.classList.toggle('active', direction === 'desc');
}

ascendingSortButton?.addEventListener('click', () => setSortDirection('asc'));
descendingSortButton?.addEventListener('click', () => setSortDirection('desc'));

clearFiltersButton.addEventListener('click', () =>
{
    document.querySelectorAll('.chip-group button').forEach(button => button.classList.remove('active'));
    document.querySelectorAll('.chip-group.is-expanded').forEach(group => group.classList.remove('is-expanded'));
    document.querySelectorAll('.see-all-btn').forEach(button => button.textContent = 'See all');
    numberOfResults.value = '10';
    numberOfResultsValue.value = '10';
    numberOfResultsValue.textContent = '10';
    searchByIngredient.checked = false;
    resetCalorieSlider();
});

numberOfResults.addEventListener('input', () =>
{
    numberOfResultsValue.value = numberOfResults.value;
    numberOfResultsValue.textContent = numberOfResults.value;
});

minCaloriesInput.addEventListener('input', () => updateCalorieSlider(minCaloriesInput));
maxCaloriesInput.addEventListener('input', () => updateCalorieSlider(maxCaloriesInput));
updateCalorieSlider();

document.addEventListener('click', event =>
{
    const favoriteButton = event.target.closest('.favorite-btn');

    if (!favoriteButton)
    {
        return;
    }

    const isFavorite = favoriteButton.getAttribute('aria-pressed') === 'true';
    const recipeTitle = favoriteButton.closest('.recipe')?.querySelector('h2')?.textContent.trim() || 'recipe';
    const recipeId = favoriteButton.dataset.recipeId;
    const newFavoriteState = !isFavorite;
    const formData = new FormData();
    formData.append('recipe_id', recipeId);
    formData.append('isFavorite', String(newFavoriteState));

    fetch('../php/favorite-recipe.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(result =>
        {
            if (!result.success)
            {
                return;
            }

            if (newFavoriteState)
            {
                savedRecipeIds.push(recipeId);
            }
            else
            {
                const savedRecipeIndex = savedRecipeIds.indexOf(recipeId);

                if (savedRecipeIndex !== -1)
                {
                    savedRecipeIds.splice(savedRecipeIndex, 1);
                }
            }

            document.querySelectorAll(`.favorite-btn[data-recipe-id="${recipeId}"]`).forEach(button =>
            {
                const favoriteImage = button.querySelector('img');
                button.setAttribute('aria-pressed', String(newFavoriteState));

                if (newFavoriteState)
                {
                    button.setAttribute('aria-label', `Remove ${recipeTitle} from favorites`);
                    favoriteImage.src = '../images/search-page/heart-full.svg';
                }
                else
                {
                    button.setAttribute('aria-label', `Add ${recipeTitle} to favorites`);
                    favoriteImage.src = '../images/search-page/heart-empty.svg';
                }
            });
        });
});

document.addEventListener('keydown', event =>
{
    if (event.key === 'Escape')
    {
        closeFilterPanel();
    }
});
