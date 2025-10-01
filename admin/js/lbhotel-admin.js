(function ($) {
    'use strict';

    $(function () {
        var $container = $('#lbhotel-rooms-manager');

        if (!$container.length) {
            return;
        }

        var $hidden = $('#lbhotel_rooms_json');
        var roomIndex = $container.find('.lbhotel-room').length;
        var addLabel = $container.data('locale-add') || 'Add room type';
        var removeLabel = $container.data('locale-remove') || 'Remove';

        function syncRooms() {
            var rooms = [];

            $container.find('.lbhotel-room').each(function () {
                var $room = $(this);
                var name = $room.find('.lbhotel-room-name').val().trim();
                var price = parseFloat($room.find('.lbhotel-room-price').val());
                var capacity = parseInt($room.find('.lbhotel-room-capacity').val(), 10);
                var availability = $room.find('.lbhotel-room-availability').val().trim();
                var imagesRaw = $room.find('.lbhotel-room-images').val();
                var images = imagesRaw ? imagesRaw.split(',').map(function (item) {
                    return item.trim();
                }).filter(Boolean) : [];

                if (!name) {
                    return;
                }

                rooms.push({
                    name: name,
                    price: isNaN(price) ? '' : price,
                    capacity: isNaN(capacity) ? '' : capacity,
                    availability: availability,
                    images: images
                });
            });

            $hidden.val(JSON.stringify(rooms));
        }

        function resetRoom($room) {
            $room.attr('data-index', roomIndex);
            $room.find('input').val('');
            $room.find('.lbhotel-remove-room').text(removeLabel);
            roomIndex++;
        }

        $container.on('click', '.lbhotel-add-room', function (event) {
            event.preventDefault();
            var $rooms = $container.find('.lbhotel-room');
            var $clone = $rooms.last().clone();
            resetRoom($clone);
            $clone.insertBefore($container.find('.lbhotel-add-room'));
            syncRooms();
        });

        $container.on('click', '.lbhotel-remove-room', function (event) {
            event.preventDefault();
            var $rooms = $container.find('.lbhotel-room');
            if ($rooms.length <= 1) {
                $rooms.find('input').val('');
                syncRooms();
                return;
            }

            $(this).closest('.lbhotel-room').remove();
            syncRooms();
        });

        $container.on('keyup change', '.lbhotel-room input', syncRooms);

        $('#post').on('submit', function () {
            syncRooms();
        });

        // Ensure initial state is synced.
        syncRooms();
    });
})(jQuery);
