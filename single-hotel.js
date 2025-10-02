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

            const update = () => {
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
                index = (nextIndex + slides.length) % slides.length;
                update();
            };

            const next = () => goTo(index + 1);
            const prev = () => goTo(index - 1);

            const play = () => {
                stop();
                timer = window.setInterval(next, 5000);
            };

            const stop = () => {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prev();
                    play();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
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
            update();
            play();
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
            <button type="button" class="lbhotel-slider__dot" aria-label="Image ${index + 1}"></button>`
            )
            .join('');

        return `
        <div class="lbhotel-slider" data-lbhotel-slider>
            <div class="lbhotel-slider__track">
                ${slides}
            </div>
            <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="Previous image">&#10094;</button>
            <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="Next image">&#10095;</button>
            <div class="lbhotel-slider__dots">
                ${dots}
            </div>
        </div>`;
    };

    const buildPopup = (hotel) => {
        const stars = hotel.stars > 0 ? '★'.repeat(Math.min(5, hotel.stars)) : '';
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
            ${stars ? `<div class="lbhotel-popup__stars">${escapeHtml(stars)}</div>` : ''}
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

        L.control.layers(
            {
                Map: baseLayer,
                Satellite: satelliteLayer,
            },
            null,
            { position: 'topright' }
        ).addTo(map);

        const dummyHotels = buildDummyHotels();
        const current = normaliseHotel(window.lbHotelSingleData ? window.lbHotelSingleData.currentHotel : null);

        if (current) {
            const existingIndex = dummyHotels.findIndex((hotel) => String(hotel.id) === String(current.id));
            const withImages = current.images && current.images.length ? current : { ...current, images: dummyHotels[0].images };
            if (existingIndex >= 0) {
                dummyHotels[existingIndex] = { ...dummyHotels[existingIndex], ...withImages };
            } else {
                dummyHotels.push(withImages);
            }

            if (Number.isFinite(current.lat) && Number.isFinite(current.lng)) {
                map.setView([current.lat, current.lng], 13);
            } else if (window.lbHotelSingleData && window.lbHotelSingleData.fallbackCenter) {
                const fallback = window.lbHotelSingleData.fallbackCenter;
                map.setView([fallback.lat, fallback.lng], 6);
            }
        }

        const markers = dummyHotels
            .map(normaliseHotel)
            .filter((hotel) => Number.isFinite(hotel.lat) && Number.isFinite(hotel.lng));

        const bounds = [];

        markers.forEach((hotel) => {
            const marker = L.marker([hotel.lat, hotel.lng]).addTo(map);
            marker.bindPopup(buildPopup(hotel));
            bounds.push([hotel.lat, hotel.lng]);
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
        }, 250);
    };

    document.addEventListener('DOMContentLoaded', () => {
        setupSliders();
        initMap();
    });
})();
