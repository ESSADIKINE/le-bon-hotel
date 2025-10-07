(function () {
    const sliders = document.querySelectorAll('[data-lbhotel-slider]');

    sliders.forEach((slider) => {
        if (slider.dataset.lbhotelSliderInitialised) {
            return;
        }

        const track = slider.querySelector('.lbhotel-single-place__slides');
        const slides = track ? Array.from(track.children) : [];
        const prev = slider.querySelector('.lbhotel-single-place__nav--prev');
        const next = slider.querySelector('.lbhotel-single-place__nav--next');
        const dots = Array.from(slider.querySelectorAll('.lbhotel-single-place__dot'));
        let index = 0;

        if (!track || slides.length === 0) {
            slider.dataset.lbhotelSliderInitialised = 'true';
            return;
        }

        const update = () => {
            track.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        };

        const goTo = (nextIndex) => {
            index = (nextIndex + slides.length) % slides.length;
            update();
        };

        const handleActivation = (event, callback) => {
            if (typeof callback !== 'function') {
                return;
            }

            if (event.type === 'click') {
                event.preventDefault();
                callback();
            } else if (event.type === 'keydown') {
                if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                    event.preventDefault();
                    callback();
                }
            }
        };

        if (prev) {
            ['click', 'keydown'].forEach((type) => {
                prev.addEventListener(type, (event) => handleActivation(event, () => goTo(index - 1)));
            });
        }

        if (next) {
            ['click', 'keydown'].forEach((type) => {
                next.addEventListener(type, (event) => handleActivation(event, () => goTo(index + 1)));
            });
        }

        dots.forEach((dot, dotIndex) => {
            ['click', 'keydown'].forEach((type) => {
                dot.addEventListener(type, (event) => handleActivation(event, () => goTo(dotIndex)));
            });
        });

        slider.dataset.lbhotelSliderInitialised = 'true';
        update();
    });
})();
