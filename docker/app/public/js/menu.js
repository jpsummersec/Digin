document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('diginMenuBtn');
    const dropdown = document.getElementById('diginDropdown');
    
    if (menuBtn && dropdown) {
        menuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = dropdown.classList.toggle('active');
            menuBtn.classList.toggle('open');
            menuBtn.setAttribute('aria-expanded', isOpen);
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && !menuBtn.contains(event.target)) {
                dropdown.classList.remove('active');
                menuBtn.classList.remove('open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close menu on pressing Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                dropdown.classList.remove('active');
                menuBtn.classList.remove('open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});