document.addEventListener('DOMContentLoaded', () =>
{
    const menuButton = document.getElementById('diginMenuBtn');
    const dropdown = document.getElementById('diginDropdown');

    if (menuButton && dropdown)
    {
        menuButton.addEventListener('click', (event) =>
        {
            event.stopPropagation();
            const isOpen = dropdown.classList.toggle('active');
            menuButton.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', isOpen);
        });

        // Close the menu when the user clicks outside it.
        document.addEventListener('click', (event) =>
        {
            if (!dropdown.contains(event.target) && !menuButton.contains(event.target))
            {
                dropdown.classList.remove('active');
                menuButton.classList.remove('open');
                menuButton.setAttribute('aria-expanded', 'false');
            }
        });

        // Close the menu when the user presses Escape.
        document.addEventListener('keydown', (event) =>
        {
            if (event.key === 'Escape')
            {
                dropdown.classList.remove('active');
                menuButton.classList.remove('open');
                menuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
