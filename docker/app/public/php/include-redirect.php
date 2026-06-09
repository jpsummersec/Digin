<!-- This overlay stays hidden until authentication succeeds. -->
<div id="redirect-overlay" hidden>
    <div id="redirect-container">
        <p id="thank-you">Thank you for</p>
        <img id="brand" src="../images/diggingin_logo.svg" alt="Digging In">
        <audio id="thank-you-audio" src="../audio/thank-you-thank-you-thank-you-meme.mp3" preload="auto"></audio>
        <p id="redirect-message">You will be redirected<br>in <span id="countdown">5</span> seconds.</p>
    </div>
</div>

<script>
    // Shared elements used by both authentication forms.
    const authForm = document.getElementById('auth-form');
    const authErrors = document.getElementById('auth-errors');
    const submitButton = authForm.querySelector('button[type="submit"]');
    const thankYouAudio = document.getElementById('thank-you-audio');
    const delayMs = 250; // Audio clip delay.

    // Submit without leaving the page so the button click can unlock audio.
    authForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        submitButton.disabled = true;
        authErrors.hidden = true;

        // Start audio during the user action, then silence it while PHP responds.
        thankYouAudio.loop = true;
        thankYouAudio.play();
        thankYouAudio.volume = 0;

        try {
            // Ask PHP to validate the form and return a JSON result.
            const response = await fetch(authForm.action, {
                method: 'POST',
                body: new FormData(authForm),
                headers: { 'X-Requested-With': 'fetch' }
            });
            const result = await response.json();

            if (!result.success) {
                throw result.errors;
            }

            // Show the success screen after valid authentication.
            document.getElementById('redirect-overlay').hidden = false;
            document.getElementById('brand').classList.add('is-bouncing');

            // Restart the unlocked audio after the animation delay.
            setTimeout(function() {
                thankYouAudio.currentTime = 0;
                thankYouAudio.loop = false;
                thankYouAudio.volume = 1;
            }, delayMs);

            // Count down before opening the search page.
            let seconds = 5;
            const interval = setInterval(function() {
                seconds--;
                document.getElementById('countdown').textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = 'search-page.php';
                }
            }, 1000);
        } catch (errors) {
            // Reset the audio and display validation or request errors.
            thankYouAudio.pause();
            thankYouAudio.currentTime = 0;
            thankYouAudio.loop = false;
            thankYouAudio.volume = 1;

            if (Array.isArray(errors)) {
                authErrors.textContent = errors.join('\n');
            } else {
                authErrors.textContent = 'Something went wrong. Please try again.';
            }

            authErrors.hidden = false;
            submitButton.disabled = false;
        }
    });
</script>
