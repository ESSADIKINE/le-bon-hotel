(function () {
    const localized = window.lbhotelAllHotels || {};

    const defaultStrings = {
        empty: 'No hotels match your search. Try adjusting filters.',
        emptyPage: 'No more hotels on this page.',
        reserve: 'Reserve Booking',
        map: 'Show on Map',
        details: 'View Details',
        priceLabel: '/ night',
        noImage: 'Image coming soon',
        imageAlt: 'Hotel gallery image',
    };

    const strings = Object.assign({}, defaultStrings, localized.strings || {});

    const defaultCurrency =
        typeof localized.currency === 'string' && localized.currency ? localized.currency : 'USD';

    const perPageValue = Number(localized.perPage);
    const normalizedPerPage = Number.isFinite(perPageValue) && perPageValue > 0 ? perPageValue : 4;

    function normalizeHotel(rawHotel, index) {
        if (!rawHotel || typeof rawHotel !== 'object') {
            return null;
        }

        const normalized = {
            id: rawHotel.id || rawHotel.ID || index + 1,
            name: rawHotel.name || rawHotel.title || '',
            city: rawHotel.city || '',
            region: rawHotel.region || '',
            country: rawHotel.country || '',
            description: rawHotel.description || rawHotel.excerpt || '',
            booking_url: rawHotel.booking_url || '',
            details_url: rawHotel.details_url || rawHotel.permalink || '',
            available_from: rawHotel.available_from || rawHotel.published_at || rawHotel.date || '',
        };

        const ratingValue = parseFloat(rawHotel.rating);
        normalized.rating = Number.isNaN(ratingValue) ? null : ratingValue;

        const priceValue = parseFloat(rawHotel.price);
        normalized.price = Number.isNaN(priceValue) ? null : priceValue;

        const distanceValue = parseFloat(rawHotel.distance);
        normalized.distance = Number.isNaN(distanceValue) ? null : distanceValue;

        const coordsSource = rawHotel.coordinates || {};
        const lat = parseFloat(coordsSource.lat);
        const lng = parseFloat(coordsSource.lng);
        normalized.coordinates = !Number.isNaN(lat) && !Number.isNaN(lng) ? { lat, lng } : null;

        const images = Array.isArray(rawHotel.images) ? rawHotel.images : [];
        const sanitizedImages = images
            .map((image) => (typeof image === 'string' ? image.trim() : ''))
            .filter((image) => image.length > 0);

        if (sanitizedImages.length === 0 && typeof rawHotel.featured_image === 'string' && rawHotel.featured_image) {
            sanitizedImages.push(rawHotel.featured_image);
        }

        normalized.images = sanitizedImages;

        const dateValue = Date.parse(normalized.available_from);
        normalized._sortDate = Number.isNaN(dateValue) ? 0 : dateValue;

        return normalized;
    }

    const HOTELS = Array.isArray(localized.hotels)
        ? localized.hotels.map(normalizeHotel).filter(Boolean)
        : [];

    const state = {
        search: '',
        distance: 'all',
        rating: 'all',
        sort: 'date-desc',
        page: 1,
        perPage: normalizedPerPage,
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

    function normalizeString(value) {
        if (typeof value === 'string') {
            return value.toLowerCase().trim();
        }

        if (typeof value === 'number') {
            return String(value).toLowerCase().trim();
        }

        return '';
    }

    function applyFilters(hotels) {
        return hotels.filter((hotel) => {
            const searchMatch =
                !state.search ||
                [hotel.name, hotel.city, hotel.region, hotel.country, hotel.description]
                    .map(normalizeString)
                    .some((value) => value.includes(state.search));

            const distanceMatch =
                state.distance === 'all' ||
                (typeof hotel.distance === 'number' && hotel.distance <= Number(state.distance));

            const ratingMatch =
                state.rating === 'all' ||
                (typeof hotel.rating === 'number' && hotel.rating >= Number(state.rating));

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
                    compare = (a._sortDate || 0) - (b._sortDate || 0);
                    break;
                case 'distance': {
                    const aDistance = typeof a.distance === 'number' ? a.distance : Number.POSITIVE_INFINITY;
                    const bDistance = typeof b.distance === 'number' ? b.distance : Number.POSITIVE_INFINITY;
                    compare = aDistance - bDistance;
                    break;
                }
                case 'rating': {
                    const aRating = typeof a.rating === 'number' ? a.rating : 0;
                    const bRating = typeof b.rating === 'number' ? b.rating : 0;
                    compare = aRating - bRating;
                    break;
                }
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
        if (typeof price !== 'number' || Number.isNaN(price)) {
            return '';
        }

        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: defaultCurrency,
                minimumFractionDigits: 0,
            }).format(price);
        } catch (error) {
            return price.toFixed(0);
        }
    }

    function createStarRating(rating) {
        if (typeof rating !== 'number' || Number.isNaN(rating) || rating <= 0) {
            return '';
        }

        const clampedRating = Math.max(0, Math.min(5, Math.round(rating)));
        const fullStars = '★'.repeat(clampedRating);

        return `<span class="hotel-card__stars" aria-label="${clampedRating} star rating">${fullStars}</span>`;
    }

    function createSlider(images, hotelId) {
        if (!Array.isArray(images) || images.length === 0) {
            return `
                <div class="hotel-card__slider hotel-card__slider--empty" aria-hidden="true">
                    <div class="hotel-card__slider-empty-text">${strings.noImage}</div>
                </div>
            `;
        }

        const slides = images
            .map(
                (image) =>
                    `<div class="hotel-card__slide" style="background-image:url('${image}')" role="img" aria-label="${strings.imageAlt}"></div>`
            )
            .join('');

        const dots =
            images.length > 1
                ? images
                      .map(
                          (_, index) =>
                              `<span class="hotel-card__dot${index === 0 ? ' is-active' : ''}" data-dot="${index}"></span>`
                      )
                      .join('')
                : '';

        const dotsMarkup =
            dots && dots.length
                ? `<div class="hotel-card__slider-dots" role="tablist">${dots}</div>`
                : '';

        return `
            <div class="hotel-card__slider" data-slider="${hotelId}">
                <div class="hotel-card__slides" data-slides>${slides}</div>
                ${dotsMarkup}
            </div>
        `;
    }

    function createCard(hotel) {
        const regionCountry = [hotel.region, hotel.country].filter(Boolean).join(', ');
        const hasCity = Boolean(hotel.city);
        const hasRegionCountry = regionCountry.length > 0;
        const hasCoordinates = hotel.coordinates && typeof hotel.coordinates.lat === 'number' && typeof hotel.coordinates.lng === 'number';

        let locationMarkup = '';

        if (hasCity || hasRegionCountry) {
            const bullet = hasCity && hasRegionCountry ? '<span>•</span>' : '';
            const cityMarkup = hasCity ? `<span>${hotel.city}</span>` : '';
            const regionMarkup = hasRegionCountry ? `<span>${regionCountry}</span>` : '';

            locationMarkup = `
                <div class="hotel-card__location">
                    ${cityMarkup}
                    ${bullet}
                    ${regionMarkup}
                </div>
            `;
        }

        const priceMarkup = hotel.price !== null ? `<div class="hotel-card__price">${formatPrice(hotel.price)} <span>${strings.priceLabel}</span></div>` : '';
        const descriptionMarkup = hotel.description
            ? `<p class="hotel-card__description">${hotel.description}</p>`
            : '';

        const mapUrl = hasCoordinates
            ? `https://www.google.com/maps/search/?api=1&query=${hotel.coordinates.lat},${hotel.coordinates.lng}`
            : '';

        const actions = [
            hotel.booking_url
                ? `<a class="hotel-card__button hotel-card__button--reserve" href="${hotel.booking_url}" target="_blank" rel="noopener">${strings.reserve}</a>`
                : '',
            hasCoordinates
                ? `<a class="hotel-card__button hotel-card__button--map" href="${mapUrl}" target="_blank" rel="noopener">${strings.map}</a>`
                : '',
            hotel.details_url
                ? `<a class="hotel-card__button hotel-card__button--details" href="${hotel.details_url}">${strings.details}</a>`
                : '',
        ]
            .filter(Boolean)
            .join('');

        return `
            <article class="hotel-card" data-hotel-id="${hotel.id}">
                ${createSlider(hotel.images, hotel.id)}
                <div class="hotel-card__info">
                    <h2 class="hotel-card__name">${hotel.name}</h2>
                    ${locationMarkup}
                    ${createStarRating(hotel.rating)}
                    ${priceMarkup}
                    ${descriptionMarkup}
                </div>
                <div class="hotel-card__actions">
                    ${actions}
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

        if (!slidesWrapper) {
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

        goToSlide(0);

        if (!dots.length) {
            return;
        }

        function nextSlide() {
            const nextIndex = (index + 1) % dots.length;
            goToSlide(nextIndex);
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const targetIndex = Number(dot.dataset.dot);

                if (Number.isNaN(targetIndex)) {
                    return;
                }

                goToSlide(targetIndex);
            });
        });

        if (dots.length > 1) {
            sliderIntervals.set(sliderId, window.setInterval(nextSlide, 5000));
        }
    }

    function updatePaginationControls(total) {
        const totalPages = total > 0 ? Math.ceil(total / state.perPage) : 1;

        if (state.page > totalPages) {
            state.page = totalPages;
        }

        selectors.paginationButtons.forEach((button) => {
            const direction = button.dataset.pagination;

            if (direction === 'prev') {
                button.disabled = state.page === 1 || total <= 0;
            } else {
                button.disabled = state.page >= totalPages || total <= 0;
            }
        });
    }

    function render() {
        if (!selectors.list || !selectors.count) {
            return;
        }

        clearSliders();

        const filtered = applyFilters(HOTELS);
        const sorted = applySort(filtered);
        const total = sorted.length;

        if (total === 0) {
            state.page = 1;
        }

        updatePaginationControls(total);

        const totalPages = total > 0 ? Math.ceil(total / state.perPage) : 1;

        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const paginated = paginate(sorted);

        selectors.count.textContent = String(total);

        if (paginated.length === 0) {
            const message = total === 0 ? strings.empty : strings.emptyPage;
            selectors.list.innerHTML = `<p class="hotel-card__empty">${message}</p>`;
            return;
        }

        selectors.list.innerHTML = paginated.map(createCard).join('');

        const sliders = selectors.list.querySelectorAll('[data-slider]');
        sliders.forEach((slider) => initSlider(slider));
    }

    function attachEvents() {
        if (selectors.search) {
            selectors.search.addEventListener('input', (event) => {
                state.search = normalizeString(event.target.value);
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

                if ('next' === direction) {
                    state.page += 1;
                } else {
                    state.page -= 1;
                }

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
})();
