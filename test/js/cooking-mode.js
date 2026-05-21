const steps = document.querySelectorAll('.step');
const doneBtn = document.getElementById('doneBtn');
const currentStepText = document.getElementById('currentStepText');

let current = 0;

doneBtn.addEventListener('click', () => {

    steps[current].classList.remove('active');
    steps[current].classList.add('done');

    current++;

    if(current < steps.length) {

        steps[current].classList.add('active');

        currentStepText.innerText =
            steps[current].querySelector('p').innerText;

        steps[current].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    } else {

        currentStepText.innerText =
            'Recipe completed 🎉';

        doneBtn.style.display = 'none';
    }
});