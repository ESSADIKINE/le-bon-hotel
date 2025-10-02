(function () {
    const sliderSelector = '[data-lbhotel-slider]';

    const escapeHtml = (value) => {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const normaliseHotel = (hotel) => {
        if (!hotel) {
            return null;
        }
        const lat = Number(hotel.lat);
        const lng = Number(hotel.lng);
        return {
            id: hotel.id || `hotel-${Math.random().toString(16).slice(2)}`,
            name: hotel.title || hotel.name || 'Hotel',
            city: hotel.city || '',
            price: hotel.price || '',
            stars: Number(hotel.stars) || 0,
            bookingUrl: hotel.bookingUrl || hotel.booking_url || '',
            mapUrl: hotel.mapUrl || hotel.map_url || '',
            permalink: hotel.permalink || hotel.detailsUrl || '#',
            lat: Number.isFinite(lat) ? lat : null,
            lng: Number.isFinite(lng) ? lng : null,
            images: Array.isArray(hotel.images) ? hotel.images.slice(0, 5) : [],
        };
    };

    const setupSliders = (root = document) => {
        const sliders = root.querySelectorAll(sliderSelector);
        sliders.forEach((slider) => {
            if (slider.dataset.lbhotelSliderInitialised) {
                return;
            }

            const track = slider.querySelector('.lbhotel-slider__track');
            const slides = Array.from(slider.querySelectorAll('.lbhotel-slider__slide'));
            const prevBtn = slider.querySelector('.lbhotel-slider__nav--prev');
            const nextBtn = slider.querySelector('.lbhotel-slider__nav--next');
            const dots = Array.from(slider.querySelectorAll('.lbhotel-slider__dot'));
            let index = 0;
            let timer = null;

            if (!track || slides.length === 0) {
                slider.dataset.lbhotelSliderInitialised = 'true';
                return;
            }

            // Hide navigation if only one slide
            if (slides.length <= 1) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                if (dots.length > 0) {
                    const dotsContainer = slider.querySelector('.lbhotel-slider__dots');
                    if (dotsContainer) dotsContainer.style.display = 'none';
                }
            }

            const update = () => {
                if (slides.length === 0) return;
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, dotIndex) => {
                    if (dotIndex === index) {
                        dot.classList.add('is-active');
                    } else {
                        dot.classList.remove('is-active');
                    }
                });
            };

            const goTo = (nextIndex) => {
                if (slides.length === 0) return;
                index = (nextIndex + slides.length) % slides.length;
                update();
            };

            const next = () => goTo(index + 1);
            const prev = () => goTo(index - 1);

            const play = () => {
                stop();
                if (slides.length > 1) {
                    timer = window.setInterval(next, 5000);
                }
            };

            const stop = () => {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };

            const isActivationKey = (event) => event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar';

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prev();
                    play();
                });
                prevBtn.addEventListener('keydown', (e) => {
                    if (isActivationKey(e)) {
                        e.preventDefault();
                        prev();
                        play();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    next();
                    play();
                });
                nextBtn.addEventListener('keydown', (e) => {
                    if (isActivationKey(e)) {
                        e.preventDefault();
                        next();
                        play();
                    }
                });
            }

            dots.forEach((dot, dotIndex) => {
                dot.addEventListener('click', () => {
                    goTo(dotIndex);
                    play();
                });
                dot.addEventListener('keydown', (e) => {
                    if (isActivationKey(e)) {
                        e.preventDefault();
                        goTo(dotIndex);
                        play();
                    }
                });
            });

            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', play);

            slider.dataset.lbhotelSliderInitialised = 'true';
            update();
            play();
            
            // Debug: log slider info
            console.log('Slider initialized:', {
                slides: slides.length,
                hasImages: slides.some(slide => slide.querySelector('img')),
                track: !!track
            });
        });
    };

    const buildPopupSlider = (hotel) => {
        if (!hotel.images || hotel.images.length === 0) {
            return '';
        }

        const slides = hotel.images
            .map(
                (url) => `
            <div class="lbhotel-slider__slide">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(hotel.name)}" />
            </div>`
            )
            .join('');

        const dots = hotel.images
            .map(
                (_, index) => `
            <div class="lbhotel-slider__dot" aria-label="Image ${index + 1}" role="button" tabindex="0"></div>`
            )
            .join('');

        return `
        <div class="lbhotel-slider" data-lbhotel-slider>
            <div class="lbhotel-slider__track">
                ${slides}
            </div>
            <div class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="Previous image" role="button" tabindex="0">&#10094;</div>
            <div class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="Next image" role="button" tabindex="0">&#10095;</div>
            <div class="lbhotel-slider__dots">
                ${dots}
            </div>
        </div>`;
    };

    const buildPopup = (hotel) => {
        const numericStars = Math.max(0, Math.min(5, Number(hotel.stars) || 0));
        const stars = numericStars > 0 ? '★'.repeat(numericStars) : '';
        const slider = buildPopupSlider(hotel);
        const metaParts = [hotel.city, hotel.price].filter(Boolean).join(' · ');

        const buttons = [
            hotel.bookingUrl
                ? `<a class="lbhotel-button lbhotel-button--reserve" href="${escapeHtml(hotel.bookingUrl)}" target="_blank" rel="noopener noreferrer">Reserve Booking</a>`
                : '',
            hotel.mapUrl
                ? `<a class="lbhotel-button lbhotel-button--map" href="${escapeHtml(hotel.mapUrl)}" target="_blank" rel="noopener noreferrer">Show in Google Map</a>`
                : '',
            `<a class="lbhotel-button lbhotel-button--details" href="${escapeHtml(hotel.permalink)}">View Details</a>`,
        ]
            .filter(Boolean)
            .join('');

        return `
        <div class="lbhotel-popup">
            <h3>${escapeHtml(hotel.name)}</h3>
            ${slider}
            ${metaParts ? `<p class="lbhotel-popup__meta">${escapeHtml(metaParts)}</p>` : ''}
            ${stars ? `<div class="lbhotel-popup__stars">${escapeHtml(stars)} <span class="lbhotel-popup__stars-text">${numericStars}/5</span></div>` : ''}
            <div class="lbhotel-popup__actions">${buttons}</div>
        </div>`;
    };

    const buildDummyHotels = () => {
        return [
            {
                id: 'riad-atlas',
                name: 'Riad Atlas Splendide',
                city: 'Marrakech',
                price: '€160/night',
                stars: 5,
                lat: 31.6306,
                lng: -7.9906,
                bookingUrl: 'https://example.com/riad-atlas',
                mapUrl: 'https://www.google.com/maps/search/?api=1&query=31.6306,-7.9906',
                permalink: '#',
                images: [
                    'https://images.unsplash.com/photo-1505761671935-60b3a7427bad?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1470246973918-29a93221c455?auto=format&fit=crop&w=600&q=60',
                ],
            },
            {
                id: 'sahara-retreat',
                name: 'Sahara Retreat & Spa',
                city: 'Merzouga',
                price: '€220/night',
                stars: 4,
                lat: 31.0994,
                lng: -4.0127,
                bookingUrl: 'https://example.com/sahara-retreat',
                mapUrl: 'https://www.google.com/maps/search/?api=1&query=31.0994,-4.0127',
                permalink: '#',
                images: [
                    'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=600&q=60',
                ],
            },
            {
                id: 'chefchaouen-charms',
                name: 'Chefchaouen Charms',
                city: 'Chefchaouen',
                price: '€120/night',
                stars: 4,
                lat: 35.1714,
                lng: -5.2697,
                bookingUrl: 'https://example.com/chefchaouen-charms',
                mapUrl: 'https://www.google.com/maps/search/?api=1&query=35.1714,-5.2697',
                permalink: '#',
                images: [
                    'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=600&q=60',
                ],
            },
            {
                id: 'fes-imperial',
                name: 'Fès Imperial Palace',
                city: 'Fès',
                price: '€180/night',
                stars: 5,
                lat: 34.0331,
                lng: -5.0003,
                bookingUrl: 'https://example.com/fes-imperial',
                mapUrl: 'https://www.google.com/maps/search/?api=1&query=34.0331,-5.0003',
                permalink: '#',
                images: [
                    'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1551888419-7f082cb1f022?auto=format&fit=crop&w=600&q=60',
                ],
            },
            {
                id: 'agadir-coast',
                name: 'Agadir Coastline Resort',
                city: 'Agadir',
                price: '€140/night',
                stars: 4,
                lat: 30.4278,
                lng: -9.5981,
                bookingUrl: 'https://example.com/agadir-coast',
                mapUrl: 'https://www.google.com/maps/search/?api=1&query=30.4278,-9.5981',
                permalink: '#',
                images: [
                    'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=60',
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=600&q=60',
                ],
            },
        ];
    };

    const initMap = () => {
        const mapElement = document.getElementById('lbhotel-map');
        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
        });

        const satelliteLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 19,
            }
        );

        const map = L.map(mapElement, {
            center: [31.7917, -7.0926],
            zoom: 6,
            layers: [baseLayer],
            scrollWheelZoom: false,
        });

        const layersControl = L.control.layers(
            {
                Map: baseLayer,
                Satellite: satelliteLayer,
            },
            null,
            { position: 'topright' }
        ).addTo(map);

        // Keep layers list visible without hover by forcing expanded state
        if (layersControl && layersControl._container) {
            layersControl._container.classList.add('leaflet-control-layers-expanded');
        }

        const current = normaliseHotel(window.lbHotelSingleData ? window.lbHotelSingleData.currentHotel : null);
        const hotels = current ? [current] : [];

        if (current) {
            if (Number.isFinite(current.lat) && Number.isFinite(current.lng)) {
                map.setView([current.lat, current.lng], 13);
            } else if (window.lbHotelSingleData && window.lbHotelSingleData.fallbackCenter) {
                const fallback = window.lbHotelSingleData.fallbackCenter;
                map.setView([fallback.lat, fallback.lng], 6);
            }
        }

        // Custom SVG pin icon
        const pinSvg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="150" zoomAndPan="magnify" viewBox="0 0 112.5 149.999998" height="200" preserveAspectRatio="xMidYMid meet" version="1.0"><defs><clipPath id="d8767f843b"><path d="M 0.910156 1.421875 L 110.671875 1.421875 L 110.671875 111.183594 L 0.910156 111.183594 Z M 0.910156 1.421875 " clip-rule="nonzero"/></clipPath><clipPath id="a0f5e86848"><path d="M 0 0.332031 L 112 0.332031 L 112 148 L 0 148 Z M 0 0.332031 " clip-rule="nonzero"/></clipPath></defs><g clip-path="url(#d8767f843b)"><path fill="#d50000" d="M 110.671875 56.304688 C 110.671875 58.101562 110.582031 59.894531 110.40625 61.683594 C 110.230469 63.472656 109.96875 65.25 109.617188 67.011719 C 109.265625 68.773438 108.832031 70.515625 108.308594 72.234375 C 107.785156 73.957031 107.183594 75.644531 106.492188 77.304688 C 105.804688 78.96875 105.039062 80.589844 104.191406 82.175781 C 103.34375 83.761719 102.421875 85.300781 101.421875 86.792969 C 100.425781 88.289062 99.355469 89.730469 98.214844 91.121094 C 97.074219 92.511719 95.867188 93.839844 94.597656 95.109375 C 93.328125 96.382812 91.996094 97.585938 90.605469 98.726562 C 89.21875 99.867188 87.773438 100.9375 86.28125 101.9375 C 84.785156 102.933594 83.246094 103.859375 81.660156 104.707031 C 80.074219 105.550781 78.453125 106.320312 76.792969 107.007812 C 75.132812 107.695312 73.441406 108.300781 71.722656 108.820312 C 70 109.34375 68.261719 109.78125 66.496094 110.128906 C 64.734375 110.480469 62.957031 110.746094 61.167969 110.921875 C 59.382812 111.097656 57.589844 111.183594 55.789062 111.183594 C 53.992188 111.183594 52.199219 111.097656 50.410156 110.921875 C 48.621094 110.746094 46.847656 110.480469 45.082031 110.128906 C 43.320312 109.78125 41.578125 109.34375 39.859375 108.820312 C 38.140625 108.300781 36.449219 107.695312 34.789062 107.007812 C 33.128906 106.320312 31.503906 105.550781 29.917969 104.707031 C 28.335938 103.859375 26.792969 102.933594 25.300781 101.9375 C 23.804688 100.9375 22.363281 99.867188 20.972656 98.726562 C 19.585938 97.585938 18.253906 96.382812 16.984375 95.109375 C 15.710938 93.839844 14.507812 92.511719 13.367188 91.121094 C 12.226562 89.730469 11.15625 88.289062 10.160156 86.792969 C 9.160156 85.300781 8.238281 83.761719 7.390625 82.175781 C 6.542969 80.589844 5.773438 78.96875 5.085938 77.304688 C 4.398438 75.644531 3.792969 73.957031 3.273438 72.234375 C 2.75 70.515625 2.316406 68.773438 1.964844 67.011719 C 1.613281 65.25 1.351562 63.472656 1.171875 61.683594 C 0.996094 59.894531 0.910156 58.101562 0.910156 56.304688 C 0.910156 54.507812 0.996094 52.714844 1.171875 50.925781 C 1.351562 49.136719 1.613281 47.359375 1.964844 45.597656 C 2.316406 43.835938 2.75 42.09375 3.273438 40.375 C 3.792969 38.652344 4.398438 36.960938 5.085938 35.300781 C 5.773438 33.640625 6.542969 32.019531 7.390625 30.433594 C 8.238281 28.847656 9.160156 27.308594 10.160156 25.8125 C 11.15625 24.320312 12.226562 22.878906 13.367188 21.488281 C 14.507812 20.097656 15.710938 18.769531 16.984375 17.496094 C 18.253906 16.226562 19.585938 15.019531 20.972656 13.878906 C 22.363281 12.742188 23.804688 11.671875 25.300781 10.671875 C 26.792969 9.671875 28.335938 8.75 29.917969 7.902344 C 31.503906 7.054688 33.128906 6.289062 34.789062 5.601562 C 36.449219 4.914062 38.140625 4.308594 39.859375 3.785156 C 41.578125 3.265625 43.320312 2.828125 45.082031 2.476562 C 46.847656 2.128906 48.621094 1.863281 50.410156 1.6875 C 52.199219 1.511719 53.992188 1.421875 55.789062 1.421875 C 57.589844 1.421875 59.382812 1.511719 61.167969 1.6875 C 62.957031 1.863281 64.734375 2.128906 66.496094 2.476562 C 68.261719 2.828125 70 3.265625 71.722656 3.785156 C 73.441406 4.308594 75.132812 4.914062 76.792969 5.601562 C 78.453125 6.289062 80.074219 7.054688 81.660156 7.902344 C 83.246094 8.75 84.785156 9.671875 86.28125 10.671875 C 87.773438 11.671875 89.21875 12.742188 90.605469 13.878906 C 91.996094 15.019531 93.328125 16.226562 94.597656 17.496094 C 95.867188 18.769531 97.074219 20.097656 98.214844 21.488281 C 99.355469 22.878906 100.425781 24.320312 101.421875 25.8125 C 102.421875 27.308594 103.34375 28.847656 104.191406 30.433594 C 105.039062 32.019531 105.804688 33.640625 106.492188 35.300781 C 107.183594 36.960938 107.785156 38.652344 108.308594 40.375 C 108.832031 42.09375 109.265625 43.835938 109.617188 45.597656 C 109.96875 47.359375 110.230469 49.136719 110.40625 50.925781 C 110.582031 52.714844 110.671875 54.507812 110.671875 56.304688 Z M 110.671875 56.304688 " fill-opacity="1" fill-rule="nonzero"/></g><path fill="#1b5e20" d="M 43.304688 75.511719 L 48.070312 60.839844 L 35.601562 51.769531 L 51.027344 51.769531 L 55.789062 37.097656 L 60.574219 51.769531 L 75.980469 51.769531 L 63.511719 60.835938 L 68.277344 75.511719 L 55.808594 66.445312 Z M 58.453125 64.511719 L 62.316406 67.328125 L 60.847656 62.769531 Z M 50.734375 62.769531 L 49.265625 67.328125 L 53.125 64.511719 Z M 51.742188 59.644531 L 55.789062 62.582031 L 59.820312 59.644531 L 58.285156 54.914062 L 53.296875 54.914062 Z M 45.234375 54.914062 L 49.09375 57.714844 L 50 54.914062 Z M 61.582031 54.914062 L 62.484375 57.714844 L 66.363281 54.914062 Z M 54.304688 51.769531 L 57.273438 51.769531 L 55.789062 47.207031 Z M 54.304688 51.769531 " fill-opacity="1" fill-rule="nonzero"/><g clip-path="url(#a0f5e86848)"><path fill="#1b5e20" d="M 111.859375 56.074219 C 111.800781 25.238281 86.753906 0.28125 55.917969 0.339844 C 25.082031 0.402344 0.132812 25.445312 0.1875 56.285156 C 0.207031 66.980469 3.441406 76.835938 8.460938 85.441406 C 19.804688 104.878906 56.195312 147.820312 56.195312 147.820312 C 56.195312 147.820312 92.925781 104.105469 104.109375 84.5625 C 108.921875 76.140625 111.882812 66.472656 111.859375 56.074219 Z M 55.941406 13.234375 C 79.664062 13.1875 98.921875 32.382812 98.96875 56.097656 C 99.011719 79.8125 79.824219 99.078125 56.101562 99.128906 C 32.390625 99.167969 13.128906 79.976562 13.082031 56.261719 C 13.035156 32.542969 32.222656 13.277344 55.941406 13.234375 Z M 55.941406 13.234375 " fill-opacity="1" fill-rule="nonzero"/></g></svg>`;
        const pinUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(pinSvg);
        const hotelIcon = L.icon({
            iconUrl: pinUrl,
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -40],
            className: 'lbhotel-marker-icon'
        });

        const markers = hotels
            .map(normaliseHotel)
            .filter((hotel) => Number.isFinite(hotel.lat) && Number.isFinite(hotel.lng));

        const bounds = [];
        const markerById = {};

        markers.forEach((hotel) => {
            const marker = L.marker([hotel.lat, hotel.lng], { icon: hotelIcon }).addTo(map);
            marker.bindPopup(buildPopup(hotel));
            bounds.push([hotel.lat, hotel.lng]);
            markerById[String(hotel.id)] = marker;
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        }

        map.on('popupopen', (event) => {
            const popupRoot = event.popup.getElement();
            if (popupRoot) {
                setupSliders(popupRoot);
            }
        });

        window.setTimeout(() => {
            map.invalidateSize();
            // On single hotel page: focus and open popup for current hotel
            if (current && Number.isFinite(current.lat) && Number.isFinite(current.lng)) {
                const target = markerById[String(current.id)];
                map.setView([current.lat, current.lng], 15);
                if (target) {
                    target.openPopup();
                }
            }
        }, 250);
    };

    document.addEventListener('DOMContentLoaded', () => {
        setupSliders();
        initMap();
    });
})();
