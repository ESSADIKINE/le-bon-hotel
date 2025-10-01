(function ($) {
    'use strict';

    function buildInfoContent(hotel) {
        var stars = hotel.star_rating ? '<span class="lbhotel-map-stars">' + '★'.repeat(hotel.star_rating) + '</span>' : '';
        var currency = hotel.currency || 'MAD';
        var price = hotel.avg_price_per_night ? '<span class="lbhotel-map-price">' + currency + ' ' + hotel.avg_price_per_night + '</span>' : '';
        var i18n = (window.lbhotelPublic && window.lbhotelPublic.i18n) ? window.lbhotelPublic.i18n : { bookNow: 'Book', viewRooms: 'View rooms' };
        var buttonLabel = hotel.booking_url ? i18n.bookNow : i18n.viewRooms;
        var buttonHref = hotel.booking_url || hotel.permalink;

        return '<div class="lbhotel-map-info"><h3>' + hotel.title + '</h3>' +
            '<div class="lbhotel-map-meta">' + stars + price + '</div>' +
            '<p>' + (hotel.address || '') + '</p>' +
            '<a class="lbhotel-map-button" target="_blank" rel="noopener" href="' + buttonHref + '">' + buttonLabel + '</a></div>';
    }

    function initGoogleMap($map, hotels) {
        if (!window.google || !google.maps) {
            return;
        }

        var center = hotels.length ? { lat: hotels[0].lat, lng: hotels[0].lng } : { lat: 33.5731, lng: -7.5898 };
        var map = new google.maps.Map($map[0], {
            zoom: hotels.length ? 12 : 5,
            center: center,
        });

        var infoWindow = new google.maps.InfoWindow();

        hotels.forEach(function (hotel) {
            if (typeof hotel.lat === 'undefined' || typeof hotel.lng === 'undefined') {
                return;
            }

            var marker = new google.maps.Marker({
                position: { lat: hotel.lat, lng: hotel.lng },
                map: map,
                title: hotel.title,
                icon: hotel.icon || null,
            });

            marker.addListener('click', function () {
                infoWindow.setContent(buildInfoContent(hotel));
                infoWindow.open({ anchor: marker, map: map, shouldFocus: false });
            });
        });
    }

    function hydrateMaps() {
        $('.lbhotel-map').each(function () {
            var $map = $(this);
            var hotels = $map.data('hotels') || [];
            initGoogleMap($map, hotels);
        });
    }

    $(function () {
        hydrateMaps();
    });
})(jQuery);
