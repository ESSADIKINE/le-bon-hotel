(function () {
    const HOTELS = [
        {
            id: 1,
            name: 'Riad Andalus Dream',
            city: 'Marrakesh',
            region: 'Marrakech-Safi',
            country: 'Morocco',
            rating: 5,
            price: 220,
            distance: 4.5,
            coordinates: { lat: 31.6295, lng: -7.9811 },
            description:
                'Immerse yourself in a vibrant riad with mosaic courtyards, perfumed gardens, and contemporary comfort steps away from the Medina.',
            images: [
                'https://images.unsplash.com/photo-1548783313-dd0033688b40?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1505761671935-60b3a7427bad?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/riad-andalus',
            details_url: 'https://example.com/hotels/riad-andalus',
            available_from: '2024-05-10',
        },
        {
            id: 2,
            name: 'Kasbah Desert Pearl',
            city: 'Merzouga',
            region: 'Drâa-Tafilalet',
            country: 'Morocco',
            rating: 4,
            price: 180,
            distance: 9.2,
            coordinates: { lat: 31.0994, lng: -4.0127 },
            description:
                'Luxury desert camping with handcrafted Berber textiles, candlelit dinners beneath the stars, and guided camel treks across Erg Chebbi dunes.',
            images: [
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1513584684374-8bab748fbf90?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/kasbah-desert-pearl',
            details_url: 'https://example.com/hotels/kasbah-desert-pearl',
            available_from: '2024-05-18',
        },
        {
            id: 3,
            name: 'Atlas Skyline Suites',
            city: 'Casablanca',
            region: 'Casablanca-Settat',
            country: 'Morocco',
            rating: 5,
            price: 260,
            distance: 3.3,
            coordinates: { lat: 33.5731, lng: -7.5898 },
            description:
                'Contemporary suites overlooking the Hassan II Mosque with rooftop infinity pool and curated culinary journey celebrating Moroccan flavors.',
            images: [
                'https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1519821172141-b5d8b6f01641?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/atlas-skyline',
            details_url: 'https://example.com/hotels/atlas-skyline',
            available_from: '2024-04-30',
        },
        {
            id: 4,
            name: 'Chefchaouen Sapphire Lodge',
            city: 'Chefchaouen',
            region: 'Tanger-Tetouan-Al Hoceima',
            country: 'Morocco',
            rating: 3,
            price: 140,
            distance: 14.1,
            coordinates: { lat: 35.1688, lng: -5.2636 },
            description:
                'Blue-washed retreats tucked into the Rif Mountains with artisanal breakfasts, cascading terraces, and guided hikes through hidden valleys.',
            images: [
                'https://images.unsplash.com/photo-1543340713-8d95d382f208?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1544986581-efac024faf62?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1501045661006-fcebe0257c3f?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/chefchaouen-sapphire',
            details_url: 'https://example.com/hotels/chefchaouen-sapphire',
            available_from: '2024-05-22',
        },
        {
            id: 5,
            name: 'Essaouira Ocean Breeze',
            city: 'Essaouira',
            region: 'Marrakech-Safi',
            country: 'Morocco',
            rating: 4,
            price: 195,
            distance: 6.4,
            coordinates: { lat: 31.5085, lng: -9.7595 },
            description:
                'Seaside haven with artisan surf workshops, breezy riad courtyards, and locally sourced seafood pairings at sunset.',
            images: [
                'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/essaouira-ocean-breeze',
            details_url: 'https://example.com/hotels/essaouira-ocean-breeze',
            available_from: '2024-05-05',
        },
        {
            id: 6,
            name: "Fès Medina Heritage Hotel",
            city: 'Fès',
            region: 'Fès-Meknès',
            country: 'Morocco',
            rating: 5,
            price: 235,
            distance: 12.6,
            coordinates: { lat: 34.0331, lng: -5.0003 },
            description:
                'Historic riad restored with hand-carved cedar ceilings, hammam spa rituals, and private storytellers illuminating ancient medina secrets.',
            images: [
                'https://images.unsplash.com/photo-1470124182917-cc6e71b22ecc?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1549237511-6d976720f09d?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/fes-medina-heritage',
            details_url: 'https://example.com/hotels/fes-medina-heritage',
            available_from: '2024-05-12',
        },
        {
            id: 7,
            name: 'Tangier Horizon Bay',
            city: 'Tangier',
            region: 'Tanger-Tetouan-Al Hoceima',
            country: 'Morocco',
            rating: 4,
            price: 205,
            distance: 8.8,
            coordinates: { lat: 35.7595, lng: -5.8340 },
            description:
                'Panoramic views across the Strait of Gibraltar, rooftop lounge beats, and curated day trips weaving Andalusian and Moroccan heritage.',
            images: [
                'https://images.unsplash.com/photo-1528901166007-3784c7dd3653?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1469796466635-455ede028aca?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/tangier-horizon-bay',
            details_url: 'https://example.com/hotels/tangier-horizon-bay',
            available_from: '2024-04-27',
        },
        {
            id: 8,
            name: 'Agadir Palm Oasis Resort',
            city: 'Agadir',
            region: 'Souss-Massa',
            country: 'Morocco',
            rating: 3,
            price: 160,
            distance: 18.4,
            coordinates: { lat: 30.4278, lng: -9.5981 },
            description:
                'Sun-drenched escape with private palm-framed pools, surf-ready beaches, and argan-infused wellness rituals.',
            images: [
                'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
            ],
            booking_url: 'https://example.com/booking/agadir-palm-oasis',
            details_url: 'https://example.com/hotels/agadir-palm-oasis',
            available_from: '2024-05-08',
        },
    ];

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
})();
