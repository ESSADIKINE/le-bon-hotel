(function ($) {
    'use strict';

    $(function () {
        var $container = $('#lbhotel-rooms-manager');
        if ($container.length) {
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
        }

        // Gallery picker for main gallery field
        var $galleryInput = $('#lbhotel_gallery_images');
        var $galleryBtn = $('#lbhotel_gallery_picker');
        var $galleryPreview = $('#lbhotel_gallery_preview');
        var frame = null;

        function renderGalleryPreview(ids) {
            $galleryPreview.empty();
            if (!ids || !ids.length) return;
            ids.forEach(function (id) {
                var thumb = $('<div/>').css({ width: '64px', height: '64px', overflow: 'hidden', border: '1px solid #e2e2e2', borderRadius: '4px', position: 'relative' });
                var img = $('<img/>').css({ width: '100%', height: '100%', objectFit: 'cover' });
                var removeBtn = $('<button/>').attr('type', 'button').text('×').css({ 
                    position: 'absolute', 
                    top: '2px', 
                    right: '2px', 
                    width: '18px', 
                    height: '18px', 
                    background: '#d63638', 
                    color: 'white', 
                    border: 'none', 
                    borderRadius: '50%', 
                    fontSize: '12px', 
                    cursor: 'pointer',
                    lineHeight: '1'
                });
                thumb.append(img);
                thumb.append(removeBtn);
                $galleryPreview.append(thumb);
                if (window.wp && wp.media && wp.media.attachment) {
                    var att = wp.media.attachment(id);
                    att.fetch().then(function () {
                        var url = (att.get('sizes') && att.get('sizes').thumbnail && att.get('sizes').thumbnail.url) || att.get('url');
                        img.attr('src', url);
                    });
                }
                removeBtn.on('click', function() {
                    var newIds = ids.filter(function(i) { return i != id; });
                    $galleryInput.val(newIds.join(','));
                    renderGalleryPreview(newIds);
                });
            });
            $('#lbhotel_gallery_count').text(ids.length + ' / 5');
        }

        function parseIds(val) {
            if (!val) return [];
            return val.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        }

        renderGalleryPreview(parseIds($galleryInput.val()));

        $galleryBtn.on('click', function (e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                title: (window.lbHotelRooms && window.lbHotelRooms.i18n ? window.lbHotelRooms.i18n.selectImages : 'Select Images'),
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection');
                var ids = selection.map(function (attachment) { return attachment.id; });
                if (ids.length > 5) {
                    ids = ids.slice(0, 5);
                }
                $galleryInput.val(ids.join(','));
                renderGalleryPreview(ids);
            });

            frame.open();
        });
    });
})(jQuery);
