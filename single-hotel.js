(function () {
    const sliderSelector = '[data-lbhotel-slider]';
    const defaultFallback = { lat: 31.7917, lng: -7.0926 };

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

    const normalisePlace = (place) => {
        if (!place) {
            return null;
        }

        const lat = Number(place.lat);
        const lng = Number(place.lng);
        const actions = Array.isArray(place.actions) ? place.actions : [];

        return {
            id: place.id || place.slug || place.permalink || `place-${Math.random().toString(16).slice(2)}`,
            name: place.name || place.title || place.label || 'Place',
            category: place.category || '',
            city: place.city || '',
            region: place.region || '',
            country: place.country || '',
            location: place.location || '',
            address: place.address || '',
            summary: place.summary || '',
            rating: Number(place.rating) || 0,
            ratingText: place.rating_text || place.ratingText || '',
            mapUrl: place.mapUrl || place.map_url || '',
            virtualTourUrl: place.virtualTourUrl || place.virtual_tour_url || '',
            permalink: place.permalink || place.url || '#',
            lat: Number.isFinite(lat) ? lat : null,
            lng: Number.isFinite(lng) ? lng : null,
            images: Array.isArray(place.images) ? place.images.filter(Boolean) : [],
            actions: actions
                .filter((action) => action && action.url && action.label)
                .map((action) => ({
                    label: action.label,
                    url: action.url,
                    className: action.class || action.className || 'lbhotel-button',
                    target: action.target || '_blank',
                })),
        };
    };

    const setupSliders = (root = document) => {
        const sliders = root.querySelectorAll(sliderSelector);

        sliders.forEach((slider) => {
            if (slider.dataset.lbhotelSliderInitialised) {
                return;
            }

            const track = slider.querySelector('.lbhotel-slider__track');
            const slides = track ? Array.from(track.children) : [];
            const prevBtn = slider.querySelector('.lbhotel-slider__nav--prev');
            const nextBtn = slider.querySelector('.lbhotel-slider__nav--next');
            const dots = Array.from(slider.querySelectorAll('.lbhotel-slider__dot'));
            let index = 0;
            let timer = null;

            if (!track || slides.length === 0) {
                slider.dataset.lbhotelSliderInitialised = 'true';
                return;
            }

            if (slides.length <= 1) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                const dotsContainer = slider.querySelector('.lbhotel-slider__dots');
                if (dotsContainer) {
                    dotsContainer.style.display = 'none';
                }
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
                prevBtn.addEventListener('keydown', (event) => {
                    if (isActivationKey(event)) {
                        event.preventDefault();
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
                nextBtn.addEventListener('keydown', (event) => {
                    if (isActivationKey(event)) {
                        event.preventDefault();
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
                dot.addEventListener('keydown', (event) => {
                    if (isActivationKey(event)) {
                        event.preventDefault();
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
        });
    };

    const bindButtonLike = (element, handler) => {
        if (!element || typeof handler !== 'function') {
            return;
        }

        element.addEventListener('click', (event) => {
            event.preventDefault();
            handler(event);
        });

        element.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                event.preventDefault();
                handler(event);
            }
        });
    };

    const parsePlaceFromCard = (card) => {
        if (!card || !card.dataset || !card.dataset.hotel) {
            return null;
        }

        try {
            const parsed = JSON.parse(card.dataset.hotel);
            return normalisePlace(parsed);
        } catch (error) {
            return null;
        }
    };

    const collectPlacesData = (selectedPlace = null) => {
        const data = window.lbHotelSingleData || {};
        const fallbackCenter = data.fallbackCenter || defaultFallback;
        const placesMap = new Map();

        const addPlace = (place) => {
            const normalised = normalisePlace(place);
            if (!normalised) {
                return;
            }

            const key = String(normalised.id || normalised.permalink || normalised.name || Math.random().toString(16).slice(2));
            if (placesMap.has(key)) {
                Object.assign(placesMap.get(key), normalised);
            } else {
                placesMap.set(key, normalised);
            }
        };

        addPlace(data.currentPlace);

        if (Array.isArray(data.otherPlaces)) {
            data.otherPlaces.forEach(addPlace);
        }

        document.querySelectorAll('.lbhotel-info-card[data-hotel]').forEach((card) => {
            const parsed = parsePlaceFromCard(card);
            if (parsed) {
                addPlace(parsed);
            }
        });

        if (selectedPlace) {
            addPlace(selectedPlace);
        }

        let current = data.currentPlace ? normalisePlace(data.currentPlace) : null;

        if (selectedPlace) {
            const selectedNormalised = normalisePlace(selectedPlace);
            if (selectedNormalised) {
                const key = String(selectedNormalised.id || selectedNormalised.permalink || selectedNormalised.name);
                current = placesMap.get(key) || selectedNormalised;
            }
        }

        if (!current) {
            const iterator = placesMap.values().next();
            if (!iterator.done) {
                current = iterator.value;
            }
        }

        return {
            places: Array.from(placesMap.values()),
            current,
            fallbackCenter,
        };
    };

    const buildPopupSlider = (place) => {
        if (!place.images || place.images.length === 0) {
            return '';
        }

        const slides = place.images
            .map((url) => `
            <div class="lbhotel-slider__slide">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(place.name)}" />
            </div>`)
            .join('');

        const dots = place.images
            .map(
                (_, index) => `
            <div class="lbhotel-slider__dot" aria-label="${escapeHtml((index + 1).toString())}" role="button" tabindex="0"></div>`
            )
            .join('');

        return `
        <div class="lbhotel-slider" data-lbhotel-slider>
            <div class="lbhotel-slider__track">
                ${slides}
            </div>
            <div class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="Previous image" role="button" tabindex="0">&#10094;</div>
            <div class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="Next image" role="button" tabindex="0">&#10095;</div>
            <div class="lbhotel-slider__dots" role="tablist">
                ${dots}
            </div>
        </div>`;
    };

    const buildPopup = (place) => {
        const slider = buildPopupSlider(place);
        const locationParts = [place.location, place.city, place.region, place.country].filter(Boolean);
        const metaParts = [];

        if (locationParts.length) {
            metaParts.push(locationParts.join(', '));
        }

        if (place.summary) {
            metaParts.push(place.summary);
        }

        const ratingNumber = Math.max(0, Math.min(5, Number(place.rating) || 0));
        let ratingMarkup = '';
        if (ratingNumber > 0) {
            const rounded = Math.round(ratingNumber * 10) / 10;
            const full = Math.floor(rounded);
            const half = rounded - full >= 0.5;
            let stars = '';
            for (let i = 0; i < full; i += 1) {
                stars += '★';
            }
            if (half) {
                stars += '☆';
            }
            ratingMarkup = `<div class="lbhotel-popup__stars">${escapeHtml(stars)} <span class="lbhotel-popup__stars-text">${escapeHtml(rounded.toFixed(1))}/5</span></div>`;
        }

        const buttons = [];

        (place.actions || []).forEach((action) => {
            if (!action || !action.url || !action.label) {
                return;
            }

            const targetAttr = action.target && action.target !== '_self' ? ' target="_blank" rel="noopener noreferrer"' : '';
            buttons.push(
                `<a class="${escapeHtml(action.className || 'lbhotel-button')}" href="${escapeHtml(action.url)}"${targetAttr}>${escapeHtml(action.label)}</a>`
            );
        });

        if (!buttons.length && place.permalink) {
            buttons.push(
                `<a class="lbhotel-button lbhotel-button--details" href="${escapeHtml(place.permalink)}">${escapeHtml('View Details')}</a>`
            );
        }

        return `
        <div class="lbhotel-popup">
            <h3>${escapeHtml(place.name)}</h3>
            ${slider}
            ${metaParts.length ? `<p class="lbhotel-popup__meta">${escapeHtml(metaParts.join(' • '))}</p>` : ''}
            ${ratingMarkup}
            <div class="lbhotel-popup__actions">${buttons.join('')}</div>
        </div>`;
    };

    const createBaseMap = (element, fallbackCenter, options = {}) => {
        if (!element || typeof L === 'undefined') {
            return null;
        }

        const lat = Number(fallbackCenter && fallbackCenter.lat);
        const lng = Number(fallbackCenter && fallbackCenter.lng);
        const defaultCenter = Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : [defaultFallback.lat, defaultFallback.lng];

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

    const renderPlacesOnMap = (map, places) => {
        if (!map || typeof L === 'undefined') {
            return { markerById: {}, bounds: [] };
        }

        const pinSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 112.5 150" width="112.5" height="150" preserveAspectRatio="xMidYMid meet"><defs><filter id="shadow" x="-0.08695" y="-0.08695" width="1.17391" height="1.17391"><feGaussianBlur stdDeviation="1.449"/></filter><linearGradient x1="0" y1="0.498" x2="1" y2="0.498" id="gradient"><stop offset="0" stop-color="#8d0e15"/><stop offset="1" stop-color="#c1272d"/></linearGradient></defs><path d="M 56.25 0 C 25.21 0 0 25.21 0 56.25 C 0 69.49 4.37 82.36 12.29 92.86 L 52.38 145.52 C 52.70 145.95 53.18 146.21 53.70 146.25 C 53.73 146.25 53.77 146.25 53.81 146.25 C 54.29 146.25 54.75 146.07 55.11 145.73 C 55.22 145.62 55.31 145.50 55.39 145.39 L 100.21 85.89 C 108.09 75.52 112.5 62.62 112.5 49.39 C 112.5 22.13 86.92 0 56.25 0 Z" fill="url(#gradient)"/><path d="M 56.25 0 C 25.21 0 0 25.21 0 56.25 C 0 69.49 4.37 82.36 12.29 92.86 L 52.38 145.52 C 52.70 145.95 53.18 146.21 53.70 146.25 C 53.73 146.25 53.77 146.25 53.81 146.25 C 54.29 146.25 54.75 146.07 55.11 145.73 C 55.22 145.62 55.31 145.50 55.39 145.39 L 100.21 85.89 C 108.09 75.52 112.5 62.62 112.5 49.39 C 112.5 22.13 86.92 0 56.25 0 Z" fill="#000" fill-opacity="0.15" filter="url(#shadow)"/><path d="M 56.25 9.38 C 30.21 9.38 9.38 30.21 9.38 56.25 C 9.38 68.19 13.29 79.85 20.75 89.5 L 56.11 136.21 L 90.45 91.43 C 97.87 81.57 101.80 69.86 101.80 57.88 C 101.80 30.20 81.09 9.38 56.25 9.38 Z" fill="#fff"/><path d="M 56.25 23.44 C 38.10 23.44 23.44 38.10 23.44 56.25 C 23.44 74.40 38.10 89.06 56.25 89.06 C 74.40 89.06 89.06 74.40 89.06 56.25 C 89.06 38.10 74.40 23.44 56.25 23.44 Z" fill="#f7f7f7"/><path d="M 56.25 33.75 C 43.35 33.75 32.81 44.29 32.81 57.19 C 32.81 70.09 43.35 80.63 56.25 80.63 C 69.15 80.63 79.69 70.09 79.69 57.19 C 79.69 44.29 69.15 33.75 56.25 33.75 Z" fill="#c1272d"/></svg>`;
        const pinUrl = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(pinSvg)}`;
        const placeIcon = L.icon({
            iconUrl: pinUrl,
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -40],
            className: 'lbhotel-marker-icon',
        });

        const markerById = {};
        const bounds = [];

        (places || [])
            .filter((place) => place && Number.isFinite(place.lat) && Number.isFinite(place.lng))
            .forEach((place) => {
                const marker = L.marker([place.lat, place.lng], { icon: placeIcon }).addTo(map);
                marker.bindPopup(buildPopup(place));
                bounds.push([place.lat, place.lng]);
                markerById[String(place.id)] = marker;
            });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], 13);
        }

        map.on('popupopen', (event) => {
            const popupRoot = event.popup.getElement();
            if (popupRoot) {
                setupSliders(popupRoot);
            }
        });

        return { markerById, bounds };
    };

    const focusOnPlace = (map, current, markerById, fallbackCenter) => {
        if (!map) {
            return;
        }

        const fallbackLat = Number(fallbackCenter && fallbackCenter.lat);
        const fallbackLng = Number(fallbackCenter && fallbackCenter.lng);

        window.setTimeout(() => {
            map.invalidateSize();

            if (current && Number.isFinite(current.lat) && Number.isFinite(current.lng)) {
                map.setView([current.lat, current.lng], 15);
                const marker = markerById[String(current.id)];
                if (marker) {
                    marker.openPopup();
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
        closeButton.className = 'lbhotel-popup-close';
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.textContent = '✕';

        const body = document.createElement('div');
        body.className = 'lbhotel-popup-body';

        content.appendChild(closeButton);
        content.appendChild(body);
        overlay.appendChild(content);
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        content.setAttribute('role', 'document');
        document.body.appendChild(overlay);
        document.body.classList.add('lbhotel-no-scroll');

        const destroy = () => {
            document.removeEventListener('keydown', onKeyDown);
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            document.body.classList.remove('lbhotel-no-scroll');
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape' || event.key === 'Esc') {
                destroy();
            }
        };

        bindButtonLike(closeButton, destroy);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                destroy();
            }
        });
        document.addEventListener('keydown', onKeyDown);

        return { overlay, content, body, destroy };
    };

    const openVirtualTourOverlay = (url, placeName) => {
        if (!url) {
            return;
        }

        const overlay = createOverlay();
        if (!overlay) {
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.title = placeName ? `${placeName} – Virtual Tour` : 'Virtual Tour';
        iframe.loading = 'lazy';
        iframe.setAttribute('allowfullscreen', 'true');
        overlay.body.appendChild(iframe);
    };

    const openMapOverlay = (places, current, fallbackCenter) => {
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

            const { markerById } = renderPlacesOnMap(base.map, places);
            focusOnPlace(base.map, current, markerById, fallbackCenter);
        }, 100);
    };

    const attachInfoCardHandlers = () => {
        const cards = document.querySelectorAll('.lbhotel-info-card');
        cards.forEach((card) => {
            const placeData = parsePlaceFromCard(card);

            const tourButton = card.querySelector('.lbhotel-icon--tour');
            if (tourButton) {
                bindButtonLike(tourButton, () => {
                    const url = tourButton.dataset.tourUrl || (placeData && placeData.virtualTourUrl);
                    openVirtualTourOverlay(url, placeData ? placeData.name : document.title);
                });
            }

            const mapButton = card.querySelector('.lbhotel-icon--map');
            if (mapButton) {
                bindButtonLike(mapButton, () => {
                    const { places, current, fallbackCenter } = collectPlacesData(placeData);
                    openMapOverlay(places, current, fallbackCenter);
                });
            }
        });
    };

    const initMap = () => {
        const mapElement = document.getElementById('lbhotel-map');
        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        const { places, current, fallbackCenter } = collectPlacesData();
        const base = createBaseMap(mapElement, fallbackCenter);
        if (!base) {
            return;
        }

        const { markerById } = renderPlacesOnMap(base.map, places);
        focusOnPlace(base.map, current, markerById, fallbackCenter);
    };

    document.addEventListener('DOMContentLoaded', () => {
        setupSliders();
        attachInfoCardHandlers();
        initMap();
    });
})();
