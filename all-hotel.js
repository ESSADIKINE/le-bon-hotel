    const state = {
        search: '',
        distance: 'all',
        rating: 'all',
        sort: 'date-desc',
        page: 1,
        perPage: 4,
    };

    const selectors = {
        search: document.getElementById('hotel-search'),
        distance: document.getElementById('hotel-distance'),
        rating: document.getElementById('hotel-rating'),
        sort: document.getElementById('hotel-sort'),
        list: document.getElementById('hotel-list'),
        count: document.querySelector('[data-hotel-count]'),
        paginationButtons: document.querySelectorAll('[data-pagination]'),
    };

    const sliderIntervals = new Map();

    function normalize(str) {
        return str.toLowerCase().trim();
    }

    function applyFilters(hotels) {
        return hotels.filter((hotel) => {
            const searchMatch =
                !state.search ||
                normalize(hotel.name).includes(state.search) ||
                normalize(hotel.city).includes(state.search);

            const distanceMatch =
                state.distance === 'all' ||
                (typeof hotel.distance === 'number' && hotel.distance <= Number(state.distance));

            const ratingMatch =
                state.rating === 'all' || (typeof hotel.rating === 'number' && hotel.rating >= Number(state.rating));

            return searchMatch && distanceMatch && ratingMatch;
        });
    }

    function applySort(hotels) {
        const sorted = [...hotels];
        const [field, direction] = state.sort.split('-');

        sorted.sort((a, b) => {
            let compare = 0;
            switch (field) {
                case 'date':
                    compare = new Date(a.available_from) - new Date(b.available_from);
                    break;
                case 'distance':
                    compare = a.distance - b.distance;
                    break;
                case 'rating':
                    compare = a.rating - b.rating;
                    break;
                default:
                    compare = 0;
            }

            return direction === 'asc' ? compare : -compare;
        });

        return sorted;
    }

    function paginate(hotels) {
        const start = (state.page - 1) * state.perPage;
        return hotels.slice(start, start + state.perPage);
    }

    function formatPrice(price) {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
        }).format(price);
    }

    function createStarRating(rating) {
        const fullStars = '★'.repeat(Math.min(5, Math.max(0, Math.round(rating))));
        return `<span class="hotel-card__stars" aria-label="${rating} star rating">${fullStars}</span>`;
    }

    function createSlider(images, hotelId) {
        const slides = images
            .map(
                (image) =>
                    `<div class="hotel-card__slide" style="background-image:url('${image}')" role="img" aria-label="Hotel image"></div>`
            )
            .join('');

        const dots = images
            .map((_, index) => `<span class="hotel-card__dot${index === 0 ? ' is-active' : ''}" data-dot="${index}"></span>`)
            .join('');

        return `
            <div class="hotel-card__slider" data-slider="${hotelId}">
                <div class="hotel-card__slides" data-slides>${slides}</div>
                <div class="hotel-card__slider-dots" role="tablist">${dots}</div>
            </div>
        `;
    }

    function createCard(hotel) {
        const mapUrl = `https://www.google.com/maps/search/?api=1&query=${hotel.coordinates.lat},${hotel.coordinates.lng}`;

        return `
            <article class="hotel-card" data-hotel-id="${hotel.id}">
                ${createSlider(hotel.images, hotel.id)}
                <div class="hotel-card__info">
                    <h2 class="hotel-card__name">${hotel.name}</h2>
                    <div class="hotel-card__location">
                        <span>${hotel.city}</span>
                        <span>•</span>
                        <span>${hotel.region}, ${hotel.country}</span>
                    </div>
                    ${createStarRating(hotel.rating)}
                    <div class="hotel-card__price">${formatPrice(hotel.price)} <span>/ night</span></div>
                    <p class="hotel-card__description">${hotel.description}</p>
                </div>
                <div class="hotel-card__actions">
                    <a class="hotel-card__button hotel-card__button--reserve" href="${hotel.booking_url}" target="_blank" rel="noopener">
                        Reserve Booking
                    </a>
                    <a class="hotel-card__button hotel-card__button--map" href="${mapUrl}" target="_blank" rel="noopener">
                        Show on Map
                    </a>
                    <a class="hotel-card__button hotel-card__button--details" href="${hotel.details_url}">
                        View Details
                    </a>
                </div>
            </article>
        `;
    }

    function clearSliders() {
        sliderIntervals.forEach((intervalId) => window.clearInterval(intervalId));
        sliderIntervals.clear();
    }

    function initSlider(container) {
        const slidesWrapper = container.querySelector('[data-slides]');
        const dots = container.querySelectorAll('.hotel-card__dot');
        if (!slidesWrapper || dots.length === 0) {
            return;
        }

        const sliderId = container.dataset.slider;
        let index = 0;

        function goToSlide(newIndex) {
            index = newIndex;
            slidesWrapper.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, dotIndex) => {
                if (dotIndex === index) {
                    dot.classList.add('is-active');
                } else {
                    dot.classList.remove('is-active');
                }
            });
        }

        function nextSlide() {
            const nextIndex = (index + 1) % dots.length;
            goToSlide(nextIndex);
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const targetIndex = Number(dot.dataset.dot);
                goToSlide(targetIndex);
            });
        });

        goToSlide(0);
        sliderIntervals.set(sliderId, window.setInterval(nextSlide, 5000));
    }

    function updatePaginationControls(total) {
        const totalPages = Math.max(1, Math.ceil(total / state.perPage));
        if (state.page > totalPages) {
            state.page = totalPages;
        }

        selectors.paginationButtons.forEach((button) => {
            const direction = button.dataset.pagination;
            if (direction === 'prev') {
                button.disabled = state.page === 1;
            } else {
                button.disabled = state.page >= totalPages;
            }
        });
    }

    function render() {
        clearSliders();
        const filtered = applyFilters(HOTELS);
        const sorted = applySort(filtered);
        const total = sorted.length;
        updatePaginationControls(total);

        const totalPages = Math.max(1, Math.ceil(total / state.perPage));
        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const paginated = paginate(sorted);
        selectors.count.textContent = total;

        if (paginated.length === 0) {
            selectors.list.innerHTML = `<p class="hotel-card__empty">${total === 0 ? 'No hotels match your search. Try adjusting filters.' : 'No more hotels on this page.'}</p>`;
            return;
        }

        selectors.list.innerHTML = paginated.map(createCard).join('');

        const sliders = selectors.list.querySelectorAll('[data-slider]');
        sliders.forEach((slider) => initSlider(slider));
    }

    function attachEvents() {
        if (selectors.search) {
            selectors.search.addEventListener('input', (event) => {
                state.search = normalize(event.target.value);
                state.page = 1;
                render();
            });
        }

        if (selectors.distance) {
            selectors.distance.addEventListener('change', (event) => {
                state.distance = event.target.value;
                state.page = 1;
                render();
            });
        }

        if (selectors.rating) {
            selectors.rating.addEventListener('change', (event) => {
                state.rating = event.target.value;
                state.page = 1;
                render();
            });
        }

        if (selectors.sort) {
            selectors.sort.addEventListener('change', (event) => {
                state.sort = event.target.value;
                render();
            });
        }

        selectors.paginationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const direction = button.dataset.pagination;
                state.page += direction === 'next' ? 1 : -1;
                if (state.page < 1) {
                    state.page = 1;
                }
                render();
            });
        });

        window.addEventListener('pageshow', () => {
            render();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            attachEvents();
            render();
        });
    } else {
        attachEvents();
        render();
    }
