(function () {
    'use strict';

    function activateTheme() {
        if (document.body && !document.body.classList.contains('lbhotel-theme-active')) {
            document.body.classList.add('lbhotel-theme-active');
        }
    }

    function initialiseMap() {
        if (typeof window === 'undefined' || typeof L === 'undefined') {
            return;
        }

        var mapData = window.lbHotelMapData;
        var mapContainer = document.getElementById('lbhotel-map');

        if (!mapContainer || !mapData) {
            return;
        }

        var dummyCoordinates = [
            { lat: 31.6295, lng: -7.9811 }, // Marrakech
            { lat: 33.5731, lng: -7.5898 }, // Casablanca
            { lat: 34.0209, lng: -6.8416 }, // Rabat
            { lat: 35.7595, lng: -5.8340 }, // Tangier
            { lat: 30.4278, lng: -9.5981 }  // Agadir
        ];

        var hotels = Array.isArray(mapData.hotels) ? mapData.hotels.slice() : [];
        if (!hotels.length) {
            hotels = dummyCoordinates.map(function (coord, index) {
                return {
                    id: 'dummy-' + index,
                    title: 'Le Bon Hotel ' + (index + 1),
                    stars: 4,
                    lat: coord.lat,
                    lng: coord.lng
                };
            });
        }

        hotels.forEach(function (hotel, index) {
            if (typeof hotel.lat !== 'number' || typeof hotel.lng !== 'number') {
                var fallback = dummyCoordinates[index % dummyCoordinates.length];
                hotel.lat = fallback.lat;
                hotel.lng = fallback.lng;
            }
        });

        var focusLat = mapData.defaultCenter && mapData.defaultCenter.lat ? mapData.defaultCenter.lat : 31.7917;
        var focusLng = mapData.defaultCenter && mapData.defaultCenter.lng ? mapData.defaultCenter.lng : -7.0926;
        var focusZoom = 6;

        if (mapData.currentHotel && typeof mapData.currentHotel.lat === 'number' && typeof mapData.currentHotel.lng === 'number') {
            focusLat = mapData.currentHotel.lat;
            focusLng = mapData.currentHotel.lng;
            focusZoom = 13;
        }

        var map = L.map(mapContainer).setView([focusLat, focusLng], focusZoom);

        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var satelliteLayer = null;
        if (L.esri && typeof L.esri.basemapLayer === 'function') {
            satelliteLayer = L.esri.basemapLayer('Imagery');
        }

        var baseLayers = {
            Street: streetLayer
        };

        if (satelliteLayer) {
            baseLayers.Satellite = satelliteLayer;
        }

        L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

        var bounds = [];
        var currentId = mapData.currentHotel ? mapData.currentHotel.id : null;

        hotels.forEach(function (hotel) {
            if (typeof hotel.lat !== 'number' || typeof hotel.lng !== 'number') {
                return;
            }

            var markerOptions = {
                icon: L.divIcon({
                    className: 'lbhotel-marker',
                    html: '<span></span>',
                    iconSize: [18, 18]
                })
            };

            if (hotel.id === currentId) {
                markerOptions.icon = L.divIcon({
                    className: 'lbhotel-marker lbhotel-marker--current',
                    html: '<span></span>',
                    iconSize: [24, 24]
                });
            }

            var marker = L.marker([hotel.lat, hotel.lng], markerOptions).addTo(map);
            var starDisplay = '';
            if (hotel.stars) {
                var cappedStars = Math.max(0, Math.min(5, parseInt(hotel.stars, 10)));
                starDisplay = '<div class="moroccan-stars">' + '★'.repeat(cappedStars) + '</div>';
            }
            var title = hotel.title ? hotel.title : 'Le Bon Hotel';
            marker.bindPopup('<strong>' + title + '</strong><br />' + starDisplay);

            if (hotel.id === currentId) {
                marker.openPopup();
            }

            bounds.push([hotel.lat, hotel.lng]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 100);
    }

    function initialiseLightbox() {
        var gallery = document.querySelector('[data-lightbox-gallery]');
        if (!gallery) {
            return;
        }

        gallery.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-lightbox-item]');
            if (!trigger) {
                return;
            }
            event.preventDefault();
            openLightbox(trigger.getAttribute('href'));
        });
    }

    function openLightbox(imageUrl) {
        if (!imageUrl) {
            return;
        }

        var lightbox = document.createElement('div');
        lightbox.className = 'lbhotel-lightbox';

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'lbhotel-lightbox__close';
        closeButton.innerHTML = '&times;';

        var image = document.createElement('img');
        image.className = 'lbhotel-lightbox__image';
        image.src = imageUrl;
        image.alt = '';

        lightbox.appendChild(closeButton);
        lightbox.appendChild(image);
        document.body.appendChild(lightbox);

        function removeLightbox() {
            if (lightbox && lightbox.parentNode) {
                lightbox.parentNode.removeChild(lightbox);
            }
            document.removeEventListener('keydown', escListener);
        }

        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) {
                removeLightbox();
            }
        });

        closeButton.addEventListener('click', removeLightbox);
        function escListener(event) {
            if (event.key === 'Escape') {
                removeLightbox();
            }
        }

        document.addEventListener('keydown', escListener);
    }

    function initialiseArchiveFilters() {
        var filterForm = document.getElementById('lbhotel-filter-form');
        var grid = document.getElementById('lbhotel-archive-grid');

        if (!filterForm || !grid) {
            return;
        }

        var cards = Array.prototype.slice.call(grid.querySelectorAll('.moroccan-card--hotel'));
        var searchInput = document.getElementById('lbhotel-search');
        var distanceSelect = document.getElementById('lbhotel-distance');
        var sortSelect = document.getElementById('lbhotel-sort');
        var pagination = document.querySelector('.moroccan-pagination');

        var emptyMessage = document.createElement('p');
        emptyMessage.className = 'moroccan-placeholder';
        emptyMessage.textContent = filterForm.getAttribute('data-empty-message') || 'No hotels match your filters.';
        emptyMessage.style.display = 'none';
        grid.parentNode.insertBefore(emptyMessage, grid.nextSibling);

        function matchSearch(card, searchTerm) {
            if (!searchTerm) {
                return true;
            }
            var title = (card.getAttribute('data-title') || '').toLowerCase();
            var city = (card.getAttribute('data-city') || '').toLowerCase();
            return title.indexOf(searchTerm) !== -1 || city.indexOf(searchTerm) !== -1;
        }

        function matchDistance(card, distanceValue) {
            if (!distanceValue || distanceValue === 'all') {
                return true;
            }
            var cardDistance = parseFloat(card.getAttribute('data-distance'));
            if (isNaN(cardDistance)) {
                return true;
            }
            return cardDistance <= parseFloat(distanceValue);
        }

        function applyFilters() {
            var searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var distanceValue = distanceSelect ? distanceSelect.value : 'all';
            var visibleCount = 0;

            cards.forEach(function (card) {
                var visible = matchSearch(card, searchTerm) && matchDistance(card, distanceValue);
                card.dataset.visible = visible ? 'true' : 'false';
                card.style.display = visible ? '' : 'none';
                if (visible) {
                    visibleCount++;
                }
            });

            emptyMessage.style.display = visibleCount ? 'none' : 'block';
            if (pagination) {
                pagination.style.display = visibleCount ? '' : 'none';
            }
        }

        function sortCards() {
            if (!sortSelect) {
                return;
            }
            var sortValue = sortSelect.value;
            var cardsToSort = cards.slice();

            cardsToSort.sort(function (a, b) {
                var visibleA = a.dataset.visible !== 'false';
                var visibleB = b.dataset.visible !== 'false';

                if (!visibleA && visibleB) {
                    return 1;
                }
                if (visibleA && !visibleB) {
                    return -1;
                }

                if (sortValue === 'price') {
                    var priceA = parseFloat(a.getAttribute('data-price'));
                    var priceB = parseFloat(b.getAttribute('data-price'));
                    if (isNaN(priceA)) { priceA = Number.MAX_SAFE_INTEGER; }
                    if (isNaN(priceB)) { priceB = Number.MAX_SAFE_INTEGER; }
                    return priceA - priceB;
                }

                if (sortValue === 'rating') {
                    var ratingA = parseInt(a.getAttribute('data-rating'), 10) || 0;
                    var ratingB = parseInt(b.getAttribute('data-rating'), 10) || 0;
                    return ratingB - ratingA;
                }

                var dateA = parseInt(a.getAttribute('data-date'), 10) || 0;
                var dateB = parseInt(b.getAttribute('data-date'), 10) || 0;
                return dateB - dateA;
            });

            cardsToSort.forEach(function (card) {
                grid.appendChild(card);
            });
        }

        function updateView() {
            applyFilters();
            sortCards();
        }

        if (searchInput) {
            searchInput.addEventListener('input', updateView);
        }
        if (distanceSelect) {
            distanceSelect.addEventListener('change', updateView);
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', updateView);
        }

        filterForm.addEventListener('reset', function () {
            setTimeout(function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                if (distanceSelect) {
                    distanceSelect.value = 'all';
                }
                if (sortSelect) {
                    sortSelect.value = 'date';
                }
                updateView();
            }, 0);
        });

        updateView();
    }

    document.addEventListener('DOMContentLoaded', function () {
        activateTheme();
        initialiseMap();
        initialiseLightbox();
        initialiseArchiveFilters();
    });
})();
