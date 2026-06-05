const fallbackAudioClips = [
  {
    name: "herbs.mp3",
    file: "../audio/herbs.mp3"
  }
];

const audioClips = Array.isArray(window.availableAudioClips) && window.availableAudioClips.length > 0
  ? window.availableAudioClips
  : fallbackAudioClips;

const ramsayInsults = audioClips.map(clip => ({
  text: clip.name.replace(/\.mp3$/i, "").replace(/ - AUDIO FROM JAYUZUMI\.COM$/i, ""),
  file: clip.file
}));

let cookingTimerInstance = null;
let audioTestTimeout = null;
let activeAudio = null;
let secondsRemaining = 0;
let isRamsayModeEnabled = true;
const DEFAULT_MUSIC_VOLUME = 0.70;
const DUCKED_MUSIC_VOLUME = 0.15;
const ALERT_DURATION_MS = 3500;

// 2. CORE AUDIO INTERACTION LAYER
function triggerGordonScolding() {
  if (!isRamsayModeEnabled) return;

  // Grab the working player instance your teammate set up globally
  const activeSpotifyPlayer = window.spotifyPlayer;

  // Pick a random insult object
  const choice = ramsayInsults[Math.floor(Math.random() * ramsayInsults.length)];

  // STEP A: Lower Spotify Music volume dynamically
  if (activeSpotifyPlayer && typeof activeSpotifyPlayer.setVolume === 'function') {
    activeSpotifyPlayer.setVolume(DUCKED_MUSIC_VOLUME)
      .catch(err => console.warn("Failed to duck Spotify volume:", err));
  }

  // STEP B: Flash the angry text banner across the screen
  triggerVisualFlash(choice.text);

  // STEP C: Play the Ramsay audio asset
  playAudioClip(choice.file, activeSpotifyPlayer);
}

function playAudioClip(file, player) {
  stopActiveAudio(false);

  activeAudio = new Audio(file);
  activeAudio.play().catch(err => {
    console.error("Browser blocked audio playback. Click the page button first.", err);
    restoreSpotifyVolume(player);
  });

  activeAudio.onended = () => {
    restoreSpotifyVolume(player);
  };

  activeAudio.onerror = () => {
    restoreSpotifyVolume(player);
  };

  return activeAudio;
}

function stopActiveAudio(restoreMusic = true) {
  if (!activeAudio) return;

  activeAudio.pause();
  activeAudio.currentTime = 0;
  activeAudio = null;

  if (restoreMusic) {
    restoreSpotifyVolume(window.spotifyPlayer);
  }
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
  const parsedMinutes = Number(allocatedMinutes);
  if (!Number.isFinite(parsedMinutes) || parsedMinutes <= 0) {
    console.warn("Invalid timer duration:", allocatedMinutes);
    return;
  }

  // Clear any existing active interval clocks before starting a new one
  clearInterval(cookingTimerInstance);

  secondsRemaining = Math.max(1, Math.round(parsedMinutes * 60));
  updateTimerDisplay(secondsRemaining);

  cookingTimerInstance = setInterval(() => {
    secondsRemaining--;
    updateTimerDisplay(secondsRemaining);

    // TIME EXPIRED: The user was too slow executing the recipe step
    if (secondsRemaining <= 0) {
      clearInterval(cookingTimerInstance);
      cookingTimerInstance = null;
      triggerGordonScolding();
    }
  }, 1000);
}

// 4. FRONTEND UI MANIPULATION
function updateTimerDisplay(totalSeconds) {
  const displayElement = document.getElementById("countdown-clock");
  if (!displayElement) return;

  const safeSeconds = Math.max(0, Math.floor(totalSeconds));
  const minutes = Math.floor(safeSeconds / 60);
  const seconds = safeSeconds % 60;
  
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
  }, ALERT_DURATION_MS);
}

function bindClipControls() {
  const startButton = document.getElementById("start-step-timer");
  const testButton = document.getElementById("test-scolding");
  const playAllButton = document.getElementById("play-all-audio");
  const stopAudioButton = document.getElementById("stop-audio-test");

  if (startButton) {
    startButton.addEventListener("click", () => {
      startCookingStepTimer(startButton.dataset.minutes);
    });
  }

  if (testButton) {
    testButton.addEventListener("click", triggerGordonScolding);
  }

  if (playAllButton) {
    playAllButton.addEventListener("click", playAllAudioClips);
  }

  if (stopAudioButton) {
    stopAudioButton.addEventListener("click", stopAudioTest);
  }

  renderAudioList();
}

function renderAudioList() {
  const list = document.getElementById("audio-file-list");
  if (!list) return;

  list.innerHTML = "";

  audioClips.forEach((clip, index) => {
    const item = document.createElement("li");
    const name = document.createElement("span");
    const button = document.createElement("button");

    name.textContent = clip.name;
    button.type = "button";
    button.textContent = "Play";
    button.addEventListener("click", () => {
      stopAudioTest();
      setAudioStatus(`Playing ${clip.name}`);
      playAudioClip(clip.file, window.spotifyPlayer);
    });

    item.append(`${index + 1}. `);
    item.append(name, button);
    list.appendChild(item);
  });
}

function playAllAudioClips() {
  stopAudioTest();

  if (audioClips.length === 0) {
    setAudioStatus("No audio files found.");
    return;
  }

  playAudioClipAtIndex(0);
}

function playAudioClipAtIndex(index) {
  if (index >= audioClips.length) {
    setAudioStatus("Finished testing all audio files.");
    stopActiveAudio();
    return;
  }

  const clip = audioClips[index];
  const intervalMs = getAudioIntervalMs();

  setAudioStatus(`Playing ${index + 1} of ${audioClips.length}: ${clip.name}`);
  const audio = playAudioClip(clip.file, window.spotifyPlayer);

  audio.onended = () => {
    restoreSpotifyVolume(window.spotifyPlayer);
    setAudioStatus(`Waiting ${intervalMs / 1000}s before next clip...`);
    audioTestTimeout = setTimeout(() => {
      playAudioClipAtIndex(index + 1);
    }, intervalMs);
  };

  audio.onerror = () => {
    restoreSpotifyVolume(window.spotifyPlayer);
    setAudioStatus(`Could not play ${clip.name}. Skipping...`);
    audioTestTimeout = setTimeout(() => {
      playAudioClipAtIndex(index + 1);
    }, intervalMs);
  };
}

function stopAudioTest() {
  clearTimeout(audioTestTimeout);
  audioTestTimeout = null;
  stopActiveAudio();
  setAudioStatus(`${audioClips.length} audio files ready.`);
}

function getAudioIntervalMs() {
  const input = document.getElementById("audio-interval-seconds");
  const seconds = input ? Number(input.value) : 2;

  if (!Number.isFinite(seconds) || seconds < 0) {
    return 2000;
  }

  return seconds * 1000;
}

function setAudioStatus(message) {
  const status = document.getElementById("audio-test-status");
  if (status) {
    status.textContent = message;
  }
}

// Expose functions globally so buttons or recipe-step event listeners can invoke them
window.startCookingStepTimer = startCookingStepTimer;
window.triggerGordonScolding = triggerGordonScolding;
window.playAllAudioClips = playAllAudioClips;
window.stopAudioTest = stopAudioTest;

document.addEventListener("DOMContentLoaded", bindClipControls);
