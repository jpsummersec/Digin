const steps = document.querySelectorAll('.step');
const nextButton = document.getElementById('next-step-btn');

let currentStepIndex = 0;
let isRecipeCompleted = false;

const gordonAudio = document.getElementById('gordon-audio');

// Audio played at random times while the user is cooking
const randomPressureAudios = [
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
  '../audio/idkwheretostart.mp3',
  '../audio/idiotsandwitch.mp3',
  '../audio/morepumpkin.mp3',
  '../audio/yourunashithole.mp3',
];

// Audio played when the user moves to another step
const nextStepAudios = [
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

// Audio played after the user completed recipe
const completeRecipeAudios = [
  '../audio/thatsit.mp3',
  '../audio/icookedthatshit.mp3',
  '../audio/ittasteslikegunk.mp3',
  '../audio/youarenoteatingthat.mp3',
  '../audio/kys.mp3',
  '../audio/whatsthatomg.mp3',
  '../audio/wtfisthat.mp3',
  '../audio/welldonetoyou.mp3',
  '../audio/yousurpriseme.mp3',
];

// Shuffled bags prevent the same clips from repeating until each list is used.
let pressureBag = [];
let nextStepBag = [];
let completeBag = [];

function refillBag(source) {
  const bag = [...source];

  for (let i = bag.length - 1; i > 0; i--) {
    const randomIndex = Math.floor(Math.random() * (i + 1));
    [bag[i], bag[randomIndex]] = [bag[randomIndex], bag[i]];
  }

  return bag;
}

function getNextFromBag(bag, source) {
  if (bag.length === 0) {
    bag.push(...refillBag(source));
  }

  return bag.pop();
}

let pressureTimer = null;

function setSpotifyVolume(volume) {
  fetch(`/php/spotify-volume.php?volume=${volume}`).catch(() => {});
}

function startRandomPressureTimer() {
  clearTimeout(pressureTimer);

  const minimumDelay = 20 * 1000;
  const maximumDelay = 35 * 1000;
  const randomTime =
    Math.floor(Math.random() * (maximumDelay - minimumDelay + 1)) +
    minimumDelay;

  pressureTimer = setTimeout(() => {
    playRandomPressureAudio();
    startRandomPressureTimer();
  }, randomTime);
}

function playRandomPressureAudio() {
  const selectedAudio = getNextFromBag(pressureBag, randomPressureAudios);

  // Lower Spotify before playing the Gordon Ramsay audio
  setSpotifyVolume(30);

  gordonAudio.pause();
  gordonAudio.currentTime = 0;
  gordonAudio.volume = 1.0;
  gordonAudio.src = selectedAudio;

  setTimeout(() => {
    gordonAudio.play().catch(() => {});
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

  gordonAudio.play().catch(() => {});

  gordonAudio.onended = function () {
    setSpotifyVolume(80);
  };
}

// Show the active step and update the navigation controls.
function updateStepDisplay(playAudio = true) {
  steps.forEach((step) => {
    step.classList.remove('active');
  });

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

    steps[currentStepIndex].scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

// Record completion before playing the final audio and leaving cooking mode.
function completeRecipe() {
  if (isRecipeCompleted) {
    return;
  }

  isRecipeCompleted = true;

  const queryParameters = new URLSearchParams(window.location.search);
  const recipeId = queryParameters.get('id');

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

        gordonAudio.play().catch(() => {});

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

// Right arrow or Space advances; left arrow returns to the previous step.
document.addEventListener('keydown', (event) => {
  if (event.key === 'ArrowRight' || event.code === 'Space') {
    event.preventDefault();
    if (currentStepIndex < steps.length - 1) {
      currentStepIndex++;
      updateStepDisplay();
    } else if (currentStepIndex === steps.length - 1) {
      completeRecipe();
    }
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault();
    if (currentStepIndex > 0) {
      currentStepIndex--;
      updateStepDisplay();
    }
  }
});

// Bind the visible step navigation buttons.
const previousButton = document.getElementById('prev-step-btn');
if (previousButton) {
  previousButton.addEventListener('click', () => {
    if (currentStepIndex > 0) {
      currentStepIndex--;
      updateStepDisplay();
    }
  });
}

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

// Initialize the first step without starting a new pressure timer.
updateStepDisplay(false);

setSpotifyVolume(80);

if (sessionStorage.getItem('playStepOneAudio') === 'yes') {
  sessionStorage.removeItem('playStepOneAudio');
  startRandomPressureTimer();
}
