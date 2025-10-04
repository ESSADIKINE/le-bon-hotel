// Ensure legacy scripts expecting a global HOTELS array do not fail.
if (typeof window !== 'undefined') {
    window.HOTELS = window.HOTELS || [];
}

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
            virtualTourUrl: hotel.virtualTourUrl || hotel.virtual_tour_url || hotel.tourUrl || '',
        };
    };

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

    const parseHotelFromCard = (card) => {
        if (!card || !card.dataset || !card.dataset.hotel) {
            return null;
        }

        try {
            const parsed = JSON.parse(card.dataset.hotel);
            return normaliseHotel(parsed);
        } catch (error) {
            return null;
        }
    };

    const collectHotelsData = (selectedHotel = null) => {
        const data = window.lbHotelArchiveData || {};
        const fallback = data.fallbackCenter || { lat: 31.7917, lng: -7.0926 };
        const hotelsMap = new Map();

        const addHotel = (hotel) => {
            if (!hotel) {
                return;
            }

            const key = String(hotel.id || hotel.permalink || hotel.name || Math.random().toString(16).slice(2));
            if (hotelsMap.has(key)) {
                Object.assign(hotelsMap.get(key), hotel);
            } else {
                hotelsMap.set(key, hotel);
            }
        };

        if (Array.isArray(data.hotels)) {
            data.hotels.map(normaliseHotel).forEach(addHotel);
        }

        const cards = document.querySelectorAll('.lbhotel-info-card[data-hotel]');
        cards.forEach((card) => {
            const parsed = parseHotelFromCard(card);
            if (parsed) {
                addHotel(parsed);
            }
        });

        let current = null;

        if (selectedHotel) {
            const selectedNormalised = normaliseHotel(selectedHotel);
            if (selectedNormalised) {
                const key = String(selectedNormalised.id || selectedNormalised.permalink || selectedNormalised.name);
                addHotel(selectedNormalised);
                current = hotelsMap.get(key) || selectedNormalised;
            }
        }

        if (!current) {
            const iterator = hotelsMap.values().next();
            if (!iterator.done) {
                current = iterator.value;
            }
        }

        return {
            hotels: Array.from(hotelsMap.values()),
            current,
            fallbackCenter: fallback,
        };
    };

    const createBaseMap = (element, fallbackCenter, options = {}) => {
        if (!element || typeof L === 'undefined') {
            return null;
        }

        const lat = Number(fallbackCenter && fallbackCenter.lat);
        const lng = Number(fallbackCenter && fallbackCenter.lng);
        const defaultCenter = Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : [31.7917, -7.0926];

        const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
        });

        const satelliteLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution:
                    'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 19,
            }
        );

        const map = L.map(
            element,
            Object.assign(
                {
                    center: defaultCenter,
                    zoom: 6,
                    layers: [baseLayer],
                    scrollWheelZoom: false,
                },
                options
            )
        );

        const layersControl = L.control.layers(
            {
                Map: baseLayer,
                Satellite: satelliteLayer,
            },
            null,
            { position: 'topright' }
        ).addTo(map);

        if (layersControl && layersControl._container) {
            layersControl._container.classList.add('leaflet-control-layers-expanded');
        }

        return { map, layersControl };
    };

    const renderHotelsOnMap = (map, hotels) => {
        if (!map || typeof L === 'undefined') {
            return { markerById: {}, bounds: [] };
        }

        const pinSvg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="150" zoomAndPan="magnify" viewBox="0 0 112.5 149.999998" height="200" preserveAspectRatio="xMidYMid meet"><defs><filter id="shadow" x="-0.0869574" y="-0.0869565" width="1.17391" height="1.17391"><feGaussianBlur stdDeviation="1.449"/></filter><linearGradient x1="0" y1="0.498047" x2="1" y2="0.498047" id="gradient"><stop offset="0" stop-color="#8d0e15"/><stop offset="1" stop-color="#c1272d"/></linearGradient></defs><path d="M 56.25 0 C 25.214844 0 0 25.214844 0 56.25 C 0 69.492188 4.367188 82.363281 12.285156 92.855469 L 52.382812 145.523438 C 52.703125 145.945312 53.175781 146.210938 53.699219 146.25 C 53.730469 146.25 53.769531 146.25 53.808594 146.25 C 54.292969 146.25 54.753906 146.066406 55.109375 145.734375 C 55.222656 145.625 55.308594 145.5 55.394531 145.394531 L 100.214844 85.894531 C 108.085938 75.515625 112.5 62.621094 112.5 49.394531 C 112.5 22.128906 86.917969 0 56.25 0 Z" fill="url(#gradient)"/><path d="M 56.25 0 C 25.214844 0 0 25.214844 0 56.25 C 0 69.492188 4.367188 82.363281 12.285156 92.855469 L 52.382812 145.523438 C 52.703125 145.945312 53.175781 146.210938 53.699219 146.25 C 53.730469 146.25 53.769531 146.25 53.808594 146.25 C 54.292969 146.25 54.753906 146.066406 55.109375 145.734375 C 55.222656 145.625 55.308594 145.5 55.394531 145.394531 L 100.214844 85.894531 C 108.085938 75.515625 112.5 62.621094 112.5 49.394531 C 112.5 22.128906 86.917969 0 56.25 0 Z" fill="#000" fill-opacity="0.15" filter="url(#shadow)"/><path d="M 56.25 9.375 C 30.210938 9.375 9.375 30.210938 9.375 56.25 C 9.375 68.191406 13.289062 79.847656 20.753906 89.5 L 56.113281 136.214844 L 90.449219 91.425781 C 97.871094 81.566406 101.796875 69.855469 101.796875 57.875 C 101.796875 30.195312 81.089844 9.375 56.25 9.375 Z" fill="#fff"/><path d="M 56.25 23.4375 C 38.101562 23.4375 23.4375 38.101562 23.4375 56.25 C 23.4375 74.398438 38.101562 89.0625 56.25 89.0625 C 74.398438 89.0625 89.0625 74.398438 89.0625 56.25 C 89.0625 38.101562 74.398438 23.4375 56.25 23.4375 Z" fill="#f7f7f7"/><path d="M 56.25 33.75 C 43.347656 33.75 32.8125 44.285156 32.8125 57.1875 C 32.8125 70.089844 43.347656 80.625 56.25 80.625 C 69.152344 80.625 79.6875 70.089844 79.6875 57.1875 C 79.6875 44.285156 69.152344 33.75 56.25 33.75 Z" fill="#c1272d"/></svg>`;
        const pinUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(pinSvg);
        const hotelIcon = L.icon({
            iconUrl: pinUrl,
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -40],
            className: 'lbhotel-marker-icon',
        });

        const markerById = {};
        const bounds = [];

        (hotels || [])
            .filter((hotel) => hotel && Number.isFinite(hotel.lat) && Number.isFinite(hotel.lng))
            .forEach((hotel) => {
                const marker = L.marker([hotel.lat, hotel.lng], { icon: hotelIcon }).addTo(map);
                marker.bindPopup(buildPopup(hotel));
                bounds.push([hotel.lat, hotel.lng]);
                markerById[String(hotel.id)] = marker;
            });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], 13);
        }

        map.on('popupopen', (event) => {
            const popupRoot = event.popup.getElement();
            if (popupRoot) {
                initSliders(popupRoot);
            }
        });

        return { markerById, bounds };
    };

    const focusOnHotel = (map, current, markerById, fallbackCenter) => {
        if (!map) {
            return;
        }

        const fallbackLat = Number(fallbackCenter && fallbackCenter.lat);
        const fallbackLng = Number(fallbackCenter && fallbackCenter.lng);

        window.setTimeout(() => {
            map.invalidateSize();

            if (current && Number.isFinite(current.lat) && Number.isFinite(current.lng)) {
                map.setView([current.lat, current.lng], 15);
                const target = markerById[String(current.id)];
                if (target) {
                    target.openPopup();
                }
                return;
            }

            if (Number.isFinite(fallbackLat) && Number.isFinite(fallbackLng)) {
                map.setView([fallbackLat, fallbackLng], 6);
            }
        }, 250);
    };

    const createOverlay = () => {
        const overlay = document.createElement('div');
        overlay.className = 'lbhotel-popup-overlay';

        const content = document.createElement('div');
        content.className = 'lbhotel-popup-content';

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'lbhotel-popup-close';
        closeButton.setAttribute('aria-label', 'Close popup');
        closeButton.textContent = '✕';

        const body = document.createElement('div');
        body.className = 'lbhotel-popup-body';

        content.appendChild(closeButton);
        content.appendChild(body);
        overlay.appendChild(content);
        document.body.appendChild(overlay);

        const destroy = () => {
            document.removeEventListener('keydown', onKeyDown);
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape' || event.key === 'Esc') {
                destroy();
            }
        };

        closeButton.addEventListener('click', destroy);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                destroy();
            }
        });
        document.addEventListener('keydown', onKeyDown);

        return { overlay, content, body, destroy };
    };

    const openVirtualTourOverlay = (url, hotelName) => {
        if (!url) {
            return;
        }

        const overlay = createOverlay();
        if (!overlay) {
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.title = hotelName ? `${hotelName} – Virtual Tour` : 'Virtual Tour';
        iframe.loading = 'lazy';
        iframe.allowFullscreen = true;
        overlay.body.appendChild(iframe);
    };

    const openMapOverlay = (hotels, current, fallbackCenter) => {
        const overlay = createOverlay();
        if (!overlay) {
            return;
        }

        if (typeof L === 'undefined') {
            const message = document.createElement('p');
            message.textContent = 'Map view is currently unavailable.';
            message.style.padding = '2rem';
            overlay.body.appendChild(message);
            return;
        }

        const mapContainer = document.createElement('div');
        mapContainer.className = 'lbhotel-popup-map';
        overlay.body.appendChild(mapContainer);

        window.setTimeout(() => {
            const base = createBaseMap(mapContainer, fallbackCenter, { scrollWheelZoom: true });
            if (!base) {
                return;
            }

            const { markerById } = renderHotelsOnMap(base.map, hotels);
            focusOnHotel(base.map, current, markerById, fallbackCenter);
        }, 0);
    };

    const attachInfoCardHandlers = () => {
        const cards = document.querySelectorAll('.lbhotel-info-card');
        cards.forEach((card) => {
            if (card.dataset.lbhotelIconsInitialised) {
                return;
            }

            card.dataset.lbhotelIconsInitialised = 'true';

            const hotelData = parseHotelFromCard(card);

            const tourButton = card.querySelector('.lbhotel-icon--tour');
            if (tourButton) {
                tourButton.addEventListener('click', () => {
                    const url = tourButton.dataset.tourUrl || (hotelData ? hotelData.virtualTourUrl : '');
                    if (url) {
                        openVirtualTourOverlay(url, hotelData ? hotelData.name : document.title);
                    }
                });
            }

            const mapButton = card.querySelector('.lbhotel-icon--map');
            if (mapButton) {
                mapButton.addEventListener('click', () => {
                    const { hotels, current, fallbackCenter } = collectHotelsData(hotelData);
                    openMapOverlay(hotels, current, fallbackCenter);
                });
            }
        });
    };

    onReady(() => {
        initSliders(document);
        initPerPageSelector();
        attachInfoCardHandlers();
    });
})();
