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
            id: hotel.id || hotel.ID || `hotel-${Math.random().toString(16).slice(2)}`,
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

    const collectHotels = (data) => {
        const hotels = [];

        if (data) {
            const list = Array.isArray(data.hotels) ? data.hotels : [];
            list
                .map(normaliseHotel)
                .filter(Boolean)
                .forEach((hotel) => hotels.push(hotel));

            const current = normaliseHotel(data.currentHotel);
            if (current) {
                const existingIndex = hotels.findIndex(
                    (hotel) => hotel.id === current.id || (hotel.lat === current.lat && hotel.lng === current.lng)
                );

                if (existingIndex >= 0) {
                    hotels[existingIndex] = { ...hotels[existingIndex], ...current };
                } else {
                    hotels.push(current);
                }
            }
        }

        return hotels.filter((hotel) => Number.isFinite(hotel.lat) && Number.isFinite(hotel.lng));
    };

    const buildSliderMarkup = (hotel) => {
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

        const controls = hotel.images.length > 1
            ? `
            <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="Previous image">&#10094;</button>
            <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="Next image">&#10095;</button>
            <div class="lbhotel-slider__dots">
                ${hotel.images
                    .map((_, index) => `<button type="button" class="lbhotel-slider__dot" aria-label="Image ${index + 1}"></button>`)
                    .join('')}
            </div>`
            : '';

        return `
        <div class="lbhotel-slider" data-lbhotel-slider>
            <div class="lbhotel-slider__track">
                ${slides}
            </div>
            ${controls}
        </div>`;
    };

    const buildPopupContent = (hotel) => {
        const isDesktop = typeof window === 'undefined' ? true : window.innerWidth > 768;
        const meta = [hotel.city, hotel.price].filter(Boolean).join(' · ');
        const stars = hotel.stars > 0 ? '★'.repeat(Math.min(5, hotel.stars)) : '';

        const media = isDesktop
            ? buildSliderMarkup(hotel)
            : hotel.images && hotel.images.length > 0
                ? `<div class="lbhotel-popup__image"><img src="${escapeHtml(hotel.images[0])}" alt="${escapeHtml(hotel.name)}" /></div>`
                : '';

        const actions = isDesktop
            ? [
                  hotel.bookingUrl
                      ? `<a class="lbhotel-button lbhotel-button--reserve" href="${escapeHtml(hotel.bookingUrl)}" target="_blank" rel="noopener noreferrer">Reserve Booking</a>`
                      : '',
                  hotel.mapUrl
                      ? `<a class="lbhotel-button lbhotel-button--map" href="${escapeHtml(hotel.mapUrl)}" target="_blank" rel="noopener noreferrer">Show in Google Map</a>`
                      : '',
                  `<a class="lbhotel-button lbhotel-button--details" href="${escapeHtml(hotel.permalink)}">View Details</a>`,
              ]
                  .filter(Boolean)
                  .join('')
            : '';

        return `
        <div class="lbhotel-popup">
            <h3>${escapeHtml(hotel.name)}</h3>
            ${media}
            ${meta ? `<p class="lbhotel-popup__meta">${escapeHtml(meta)}</p>` : ''}
            ${stars ? `<div class="lbhotel-popup__stars">${escapeHtml(stars)}</div>` : ''}
            ${actions ? `<div class="lbhotel-popup__actions">${actions}</div>` : ''}
        </div>`;
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

            if (!track || slides.length === 0) {
                slider.dataset.lbhotelSliderInitialised = 'true';
                return;
            }

            let index = 0;
            let timer = null;

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

    const initMap = () => {
        if (typeof L === 'undefined') {
            return;
        }

        const mapElement = document.getElementById('lbhotel-map');
        if (!mapElement) {
            return;
        }

        const data = window.lbHotelSingleData || {};
        const fallback = data.fallbackCenter || { lat: 31.7917, lng: -7.0926 };
        const hotels = collectHotels(data);

        const startHotel = normaliseHotel(data.currentHotel);
        const initialLat = Number.isFinite(startHotel?.lat) ? startHotel.lat : fallback.lat;
        const initialLng = Number.isFinite(startHotel?.lng) ? startHotel.lng : fallback.lng;

        const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        });

        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        });

        const map = L.map(mapElement, {
            center: [initialLat, initialLng],
            zoom: 6,
            layers: [streets],
        });

        let activeLayer = streets;

        const toggleButtons = document.querySelectorAll('[data-lbhotel-layer]');
        toggleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const layerKey = button.dataset.lbhotelLayer;
                const targetLayer = layerKey === 'satellite' ? satellite : streets;
                if (targetLayer === activeLayer) {
                    return;
                }

                map.removeLayer(activeLayer);
                targetLayer.addTo(map);
                activeLayer = targetLayer;

                toggleButtons.forEach((btn) => btn.classList.remove('is-active'));
                button.classList.add('is-active');
            });
        });

        const bounds = [];
        hotels.forEach((hotel) => {
            const marker = L.marker([hotel.lat, hotel.lng]).addTo(map);
            const popupContent = buildPopupContent(hotel);
            marker.bindPopup(popupContent, { maxWidth: 320 });
            bounds.push([hotel.lat, hotel.lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { maxZoom: 13, padding: [20, 20] });
        }

        if (Number.isFinite(startHotel?.lat) && Number.isFinite(startHotel?.lng)) {
            map.setView([startHotel.lat, startHotel.lng], 14);
        }

        map.on('popupopen', (event) => {
            const popupEl = event?.popup?.getElement();
            if (popupEl) {
                setupSliders(popupEl);
            }
        });
    };

    const onReady = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    onReady(() => {
        setupSliders(document);
        initMap();
    });
})();
