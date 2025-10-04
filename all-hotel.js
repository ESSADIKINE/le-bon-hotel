// Ensure legacy scripts expecting a global HOTELS array do not fail.
if (typeof window !== 'undefined') {
    window.HOTELS = window.HOTELS || [];
}

(function () {
    const sliderSelector = '[data-lbhotel-slider]';

    function setupSlider(slider) {
        if (!slider || slider.dataset.lbhotelSliderInitialised) {
            return;
        }

        const track = slider.querySelector('.lbhotel-slider__track');
        const slides = Array.from(slider.querySelectorAll('.lbhotel-slider__slide'));
        const prevButton = slider.querySelector('.lbhotel-slider__nav--prev');
        const nextButton = slider.querySelector('.lbhotel-slider__nav--next');
        const dots = Array.from(slider.querySelectorAll('.lbhotel-slider__dot'));

        if (!track || slides.length === 0) {
            slider.dataset.lbhotelSliderInitialised = 'true';
            return;
        }

        let index = 0;
        let timerId = null;

        function updateDots() {
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        }

        function goTo(newIndex) {
            index = (newIndex + slides.length) % slides.length;
            track.style.transform = `translateX(-${index * 100}%)`;
            updateDots();
        }

        function next() {
            goTo(index + 1);
        }

        function prev() {
            goTo(index - 1);
        }

        function play() {
            stop();
            if (slides.length > 1) {
                timerId = window.setInterval(next, 5000);
            }
        }

        function stop() {
            if (timerId) {
                window.clearInterval(timerId);
                timerId = null;
            }
        }

        if (prevButton) {
            prevButton.addEventListener('click', () => {
                prev();
                play();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                next();
                play();
            });
        }

        dots.forEach((dot, dotIndex) => {
            dot.addEventListener('click', () => {
                goTo(dotIndex);
                play();
            });
        });

        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', play);

        slider.dataset.lbhotelSliderInitialised = 'true';
        goTo(0);
        play();
    }

    function initSliders(root) {
        const scope = root || document;
        const sliders = scope.querySelectorAll(sliderSelector);
        sliders.forEach(setupSlider);
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function initPerPageSelector() {
        const select = document.getElementById('hotels-per-page');
        if (!select) {
            return;
        }

        const options = Array.from(select.options).map((option) => option.value);
        const params = new URLSearchParams(window.location.search);
        const current = params.get('per_page');

        if (current && options.includes(current)) {
            select.value = current;
        }

        select.addEventListener('change', () => {
            const value = select.value;
            const updatedParams = new URLSearchParams(window.location.search);

            if (options.includes(value)) {
                updatedParams.set('per_page', value);
            } else {
                updatedParams.delete('per_page');
            }

            updatedParams.delete('paged');

            const queryString = updatedParams.toString();
            const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;
            window.location.assign(newUrl);
        });
    }

    onReady(() => {
        initSliders(document);
        initPerPageSelector();
    });
})();
