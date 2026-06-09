const steps = document.querySelectorAll('.step');
const nextButton = document.getElementById('next-step-btn');

let currentStepIndex = 0;
let isRecipeCompleted = false;

const gordonAudio = document.getElementById('gordon-audio');
const backgroundAudio = document.getElementById('background-audio');

const stepAudios = [
  '../audio/idkwheretostart.mp3',
  '../audio/idkwheretostart.mp3',
  '../audio/idkwheretostart.mp3',
];

const randomPressureAudios = [
  '../audio/idtyoucancook.mp3',
  '../audio/waitingfortalent.mp3',
  '../audio/burntpan.mp3',
];

let pressureTimer = null;

function startRandomPressureTimer() {
  clearTimeout(pressureTimer);

  const minTime = 5000; // 5 sec
  const maxTime = 10000; // 10 sec

  const randomTime =
    Math.floor(Math.random() * (maxTime - minTime + 1)) + minTime;

  pressureTimer = setTimeout(() => {
    playRandomPressureAudio();
    startRandomPressureTimer();
  }, randomTime);
}

function playRandomPressureAudio() {
  const randomIndex = Math.floor(Math.random() * randomPressureAudios.length);

  backgroundAudio.pause();

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.src = randomPressureAudios[randomIndex];

  gordonAudio.play().catch((error) => {
    console.log('Pressure audio error:', error);
  });

  gordonAudio.onended = function () {
    backgroundAudio.play();
  };
}

function playStepAudio() {
  const audioIndex = currentStepIndex % stepAudios.length;

  backgroundAudio.pause();
  backgroundAudio.currentTime = 0;

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.src = stepAudios[audioIndex];

  gordonAudio.play().catch((error) => {
    console.log('Audio error:', error);
  });

  gordonAudio.onended = function () {
    backgroundAudio.volume = 0.25;
    backgroundAudio.play();
  };

  startRandomPressureTimer();
}

// Function to update the display
function updateStepDisplay(playAudio = true) {
  // Hide all steps
  steps.forEach((step) => {
    step.classList.remove('active');
  });

  // Show current step
  if (currentStepIndex < steps.length) {
    steps[currentStepIndex].classList.add('active');

    if (currentStepIndex === steps.length - 1) {
      nextButton.innerText = 'Complete Recipe';
    } else {
      nextButton.innerText = 'Next';
    }

    if (playAudio) {
      playStepAudio();
    }

    // Scroll to the step
    steps[currentStepIndex].scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

function completeRecipe() {
  if (isRecipeCompleted) {
    return;
  }

  isRecipeCompleted = true;

  const params = new URLSearchParams(window.location.search);
  const recipeId = params.get('id');

  const formData = new FormData();
  formData.append('recipe_id', recipeId);
  formData.append('isRecipeCompleted', 'true');

  fetch('steps.php', {
    method: 'POST',
    body: formData,
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        window.location.href = 'search-page.php';
      } else {
        isRecipeCompleted = false;
      }
    })
    .catch(() => {
      isRecipeCompleted = false;
    });
}

// Keyboard event listener
document.addEventListener('keydown', (event) => {
  // Right arrow or Space to advance
  if (event.key === 'ArrowRight' || event.code === 'Space') {
    event.preventDefault();
    if (currentStepIndex < steps.length - 1) {
      currentStepIndex++;
      updateStepDisplay();
    } else if (currentStepIndex === steps.length - 1) {
      completeRecipe();
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
if (nextButton) {
  nextButton.addEventListener('click', () => {
    if (currentStepIndex < steps.length - 1) {
      currentStepIndex++;
      updateStepDisplay();
    } else if (currentStepIndex === steps.length - 1) {
      completeRecipe();
    }
  });
}

// Initialize display
updateStepDisplay(false);

if (sessionStorage.getItem('playStepOneAudio') === 'yes') {
  sessionStorage.removeItem('playStepOneAudio');
  playStepAudio();
}
