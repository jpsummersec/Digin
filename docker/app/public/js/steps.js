const steps = document.querySelectorAll('.step');
const nextButton = document.getElementById('next-step-btn');

let currentStepIndex = 0;
let isRecipeCompleted = false;

const gordonAudio = document.getElementById('gordon-audio');

const randomPressureAudios = [
  //add audios to this array, this array contains the random quotes that play during cooking
  '../audio/waitingfortalent.mp3',
  '../audio/whatisthatshit.mp3',
  '../audio/areuconsistantlyshit.mp3',
  '../audio/whatthatstink.mp3',
  '../audio/itsfuckingrotten.mp3',
  '../audio/kitchendramaticsound.mp3',
  '../audio/youresoshit.mp3',
  '../audio/youreuseless.mp3',
  '../audio/iknowyoumaybestupid.mp3',
  '../audio/disaster.mp3',
  '../audio/yourefirstclasscunt.mp3',
  '../audio/wtfisgoingon.mp3',
  '../audio/youtakethepiss.mp3',
  '../audio/wtfishedoin.mp3',
  '../audio/toneyourvoicedown.mp3',
  '../audio/tellhim.mp3',
  '../audio/emailyouthat.mp3',
  '../audio/iamshitting.mp3',
  '../audio/youwombat.mp3',
  '../audio/youdonkey.mp3',
  '../audio/wherelambsauce.mp3',
  '../audio/idiotsandwitch.mp3',
  '../audio/morepumpkin.mp3',
];

const nextStepAudios = [
  //audios that start to play once you click ''next''
  '../audio/getinthere.mp3',
  '../audio/lookatthemess.mp3',
  '../audio/youre-making-me-mad.mp3',
  '../audio/surprise.mp3',
  '../audio/kitchendramaticsound.mp3',
  '../audio/whyoven.mp3',
  '../audio/idtyoucancook.mp3',
  '../audio/youpushingme.mp3',
  '../audio/ifeellikedoing.mp3',
  '../audio/whatherb.mp3',
  '../audio/wecantcookaburger.mp3',
  '../audio/stubbornfcker.mp3',
];

const completeRecipeAudios = [
  '../audio/thatsit.mp3',
  '../audio/icookedthatshit.mp3',
  '../audio/ittasteslikegunk.mp3',
  '../audio/youarenoteatingthat.mp3',
  '../audio/kys.mp3',
  '../audio/whatsthatomg.mp3',
  '../audio/wtfisthat.mp3',
  '../audio/welldonetoyou.mp3',
  '../audio/yoursurpriseme.mp3',
];

let pressureBag = [];
let nextStepBag = [];
let completeBag = [];

function refillBag(sourceArray) {
  const bag = [...sourceArray];

  for (let i = bag.length - 1; i > 0; i--) {
    const randomIndex = Math.floor(Math.random() * (i + 1));
    [bag[i], bag[randomIndex]] = [bag[randomIndex], bag[i]];
  }

  return bag;
}

function getNextFromBag(bagName, sourceArray) {
  if (bagName.length === 0) {
    bagName.push(...refillBag(sourceArray));
  }

  return bagName.pop();
}

let pressureTimer = null;

function setSpotifyVolume(volume) {
  fetch(`/php/spotify-volume.php?volume=${volume}`)
    .then((response) => response.text())
    .then((data) => console.log('Spotify volume:', data))
    .catch((error) => console.log('Spotify volume error:', error));
}

function startRandomPressureTimer() {
  clearTimeout(pressureTimer);

  const minTime = 20 * 1000; // 30s
  const maxTime = 35 * 1000;
  const randomTime =
    Math.floor(Math.random() * (maxTime - minTime + 1)) + minTime;

  pressureTimer = setTimeout(() => {
    playRandomPressureAudio();
    startRandomPressureTimer();
  }, randomTime);
}

function playRandomPressureAudio() {
  const selectedAudio = getNextFromBag(pressureBag, randomPressureAudios);

  setSpotifyVolume(30); // lower Spotify BEFORE Gordon starts

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.volume = 1.0;
  gordonAudio.src = selectedAudio;

  setTimeout(() => {
    gordonAudio.play().catch((error) => {
      console.log('Pressure audio error:', error);
    });
  }, 500);

  gordonAudio.onended = function () {
    setSpotifyVolume(80);
  };
}

function playNextStepAudio() {
  const selectedAudio = getNextFromBag(nextStepBag, nextStepAudios);

  setSpotifyVolume(30);

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.src = selectedAudio;

  gordonAudio.play().catch((error) => {
    console.log('Next step audio error:', error);
  });

  gordonAudio.onended = function () {
    setSpotifyVolume(80);
  };
}

function playCompleteRecipeAudio() {
  const selectedAudio = getNextFromBag(completeBag, completeRecipeAudios);

  setSpotifyVolume(30);

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.src = selectedAudio;

  gordonAudio.play().catch((error) => {
    console.log('Complete audio error:', error);
  });
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
      startRandomPressureTimer();
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
        const selectedAudio = getNextFromBag(completeBag, completeRecipeAudios);

        setSpotifyVolume(30);

        gordonAudio.pause();
        gordonAudio.currentTime = 0;
        gordonAudio.src = selectedAudio;

        gordonAudio.play().catch((error) => {
          console.log('Complete audio error:', error);
        });

        gordonAudio.onended = function () {
          setSpotifyVolume(80);
          window.location.href = 'search-page.php';
        };
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
      playNextStepAudio();

      currentStepIndex++;
      updateStepDisplay();
    } else if (currentStepIndex === steps.length - 1) {
      completeRecipe();
    }
  });
}

// Initialize display
updateStepDisplay(false);

setSpotifyVolume(80);

if (sessionStorage.getItem('playStepOneAudio') === 'yes') {
  sessionStorage.removeItem('playStepOneAudio');
  startRandomPressureTimer();
}
