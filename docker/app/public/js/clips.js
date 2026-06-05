
const ramsayInsults = [
  { text: "IT'S RAWWWW!", file: "/assets/audio/raw.mp3" },
  { text: "You absolute idiot sandwich!", file: "/assets/audio/idiot-sandwich.mp3" },
  { text: "This is a total disaster! Shut it down!", file: "/assets/audio/disaster.mp3" },
  { text: "Wake up! You're moving like a snail!", file: "/assets/audio/wake-up.mp3" }
];

let cookingTimerInstance = null;
let secondsRemaining = 0;
let isRamsayModeEnabled = true;
const DEFAULT_MUSIC_VOLUME = 0.70;
const DUCKED_MUSIC_VOLUME = 0.15;  

// 2. CORE AUDIO INTERACTION LAYER
function triggerGordonScolding() {
  if (!isRamsayModeEnabled) return;

  // Grab the working player instance your teammate set up globally
  const activeSpotifyPlayer = window.spotifyPlayer;

  // Pick a random insult object
  const choice = ramsayInsults[Math.floor(Math.random() * ramsayInsults.length)];
  const gordonAudio = new Audio(choice.file);

  // STEP A: Lower Spotify Music volume dynamically
  if (activeSpotifyPlayer && typeof activeSpotifyPlayer.setVolume === 'function') {
    activeSpotifyPlayer.setVolume(DUCKED_MUSIC_VOLUME)
      .catch(err => console.warn("Failed to duck Spotify volume:", err));
  }

  // STEP B: Flash the angry text banner across the screen
  triggerVisualFlash(choice.text);

  // STEP C: Play the Ramsay audio asset
  gordonAudio.play().catch(err => {
    console.error("Browser blocked autoplay. User must click on the page first.", err);
    // Force music restoration if audio playback is entirely blocked
    restoreSpotifyVolume(activeSpotifyPlayer);
  });

  // STEP D: Restore full music volume once the clip finishes playing completely
  gordonAudio.onended = () => {
    restoreSpotifyVolume(activeSpotifyPlayer);
  };
}

// Helper to safely return Spotify volume to its original level
function restoreSpotifyVolume(player) {
  if (player && typeof player.setVolume === 'function') {
    player.setVolume(DEFAULT_MUSIC_VOLUME)
      .catch(err => console.warn("Failed to restore Spotify volume:", err));
  }
}

// 3. RECIPE STEP TIMER ENGINE
function startCookingStepTimer(allocatedMinutes) {
  // Clear any existing active interval clocks before starting a new one
  clearInterval(cookingTimerInstance);
  
  secondsRemaining = allocatedMinutes * 60;
  updateTimerDisplay(secondsRemaining);

  cookingTimerInstance = setInterval(() => {
    secondsRemaining--;
    updateTimerDisplay(secondsRemaining);

    // TIME EXPIRED: The user was too slow executing the recipe step
    if (secondsRemaining <= 0) {
      clearInterval(cookingTimerInstance);
      triggerGordonScolding();
    }
  }, 1000);
}

// 4. FRONTEND UI MANIPULATION
function updateTimerDisplay(totalSeconds) {
  const displayElement = document.getElementById("countdown-clock");
  if (!displayElement) return;

  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  
  // Formats output string nicely as 05:09 instead of 5:9
  displayElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function triggerVisualFlash(textMessage) {
  const overlay = document.getElementById("gordon-alert-banner");
  if (!overlay) return;

  overlay.textContent = textMessage;
  overlay.classList.add("active-yelling-flash");

  // Remove visual animations after 3.5 seconds
  setTimeout(() => {
    overlay.classList.remove("active-yelling-flash");
  }, 3500);
}

// Expose functions globally so buttons or recipe-step event listeners can invoke them
window.startCookingStepTimer = startCookingStepTimer;
window.triggerGordonScolding = triggerGordonScolding;