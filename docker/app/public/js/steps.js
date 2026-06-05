const steps = document.querySelectorAll('.step');
const currentStepDisplay = document.getElementById('current-step');
const totalStepsDisplay = document.getElementById('total-steps');

let currentStepIndex = 0;

// Function to update the display
function updateStepDisplay() {
    // Hide all steps
    steps.forEach(step => {
        step.classList.remove('active');
    });
    
    // Show current step
    if (currentStepIndex < steps.length) {
        steps[currentStepIndex].classList.add('active');
        currentStepDisplay.innerText = currentStepIndex + 1;
        
        // Scroll to the step
        steps[currentStepIndex].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

// Keyboard event listener
document.addEventListener('keydown', (event) => {
    // Right arrow or Space to advance
    if (event.key === 'ArrowRight' || event.code === 'Space') {
        event.preventDefault();
        if (currentStepIndex < steps.length - 1) {
            currentStepIndex++;
            updateStepDisplay();
        }
    }
    // Left arrow to go back
    else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        if (currentStepIndex > 0) {
            currentStepIndex--;
            updateStepDisplay();
        }
    }
});

// Reset to step 1 button
const resetButton = document.getElementById('reset-to-step-one');
if (resetButton) {
    resetButton.addEventListener('click', () => {
        currentStepIndex = 0;
        updateStepDisplay();
    });
}

// Previous step button
const prevButton = document.getElementById('prev-step-btn');
if (prevButton) {
    prevButton.addEventListener('click', () => {
        if (currentStepIndex > 0) {
            currentStepIndex--;
            updateStepDisplay();
        }
    });
}

// Next step button
const nextButton = document.getElementById('next-step-btn');
if (nextButton) {
    nextButton.addEventListener('click', () => {
        if (currentStepIndex < steps.length - 1) {
            currentStepIndex++;
            updateStepDisplay();
        }
    });
}

// Initialize display
updateStepDisplay();