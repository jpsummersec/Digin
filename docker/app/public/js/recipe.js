function toggleDesc()
{
    const short = document.getElementById('desc-short');
    const full = document.getElementById('desc-full');
    const btn = event.target;

    if (full.style.display === 'none') // Short description shown -> button clicked -> long description shown.
    {
        short.style.display = 'none';
        full.style.display = 'inline';
        btn.textContent = 'View less';
    }
    else // Long description shown -> button clicked -> short description shown.
    {
        short.style.display = 'inline';
        full.style.display = 'none';
        btn.textContent = 'View all';
    }
}

function toggleIngredients()
{
    const namesExtra = document.getElementById('ingredient-names-extra');
    const amountsExtra = document.getElementById('ingredient-amounts-extra');
    const btn = event.target;

    const isHidden = namesExtra.style.display === 'none'; // True if extra names are hidden, false if they are shown.

    if (isHidden) // If extra names are hidden.
    {
        namesExtra.style.display = 'contents';
        amountsExtra.style.display = 'contents';
        btn.textContent = 'View less';
    }
    else // If extra names are shown.
    {
        namesExtra.style.display = 'none';
        amountsExtra.style.display = 'none';
        btn.textContent = 'View all';
    }
}

function toggleSteps()
{
    const extra = document.getElementById('steps-extra');
    const btn = document.getElementById('steps-btn');

    if (extra.style.display === 'none') // Extra description not shown -> button clicked -> extra description shown.
    {
        extra.style.display = 'block';
        btn.textContent = 'View less';
    }
    else // Extra description shown -> button clicked -> extra description not shown.
    {
        extra.style.display = 'none';
        btn.textContent = 'View all';
    }
}

const favoriteButton = document.querySelector('.favorite-btn'); // Find the first instance of the class favorite-btn.

const recipeId = favoriteButton.dataset.recipeId; // Gets recipe ID from the data-recipe-id attribute.
const recipeTitle = document.querySelector('#recipe-title .title').textContent; // Gets recipe title from #recipe-title > title.

favoriteButton.addEventListener('click', () =>
{
    const isFavorite = favoriteButton.getAttribute('aria-pressed') === 'true'; // If button pressed -> true, otherwise false.
    const newFavoriteState = !isFavorite;
    const formData = new FormData();
    formData.append('recipe_id', recipeId);
    formData.append('isFavorite', String(newFavoriteState));

    fetch('favorite-recipe.php', // 1. Fetch favorite-recipe.php
    {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json()) // 2. After that, convert the response into json.
        .then(result => // 3. Lastly, check if that was successful.
        {
            if (!result.success)
            {
                return;
            }

            const favoriteImage = favoriteButton.querySelector('img');
            favoriteButton.setAttribute('aria-pressed', String(newFavoriteState));

            // aria-label is an accessibility tool used to provide an invisible, programatically readable text (used by screenreaders).
            if (newFavoriteState)
            {
                favoriteButton.setAttribute('aria-label', `Remove ${recipeTitle} from favorites`);
                favoriteImage.src = '../images/search-page/heart-full.png';
            }
            else
            {
                favoriteButton.setAttribute('aria-label', `Add ${recipeTitle} to favorites`);
                favoriteImage.src = '../images/search-page/heart-empty.png';
            }
        });
});