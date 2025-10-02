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

        var $galleryField = $('#lbhotel-gallery-field');

        if ($galleryField.length && typeof wp !== 'undefined' && wp.media && wp.media.attachment) {
            var $galleryList = $galleryField.find('.lbhotel-gallery-list');
            var $galleryInput = $('#lbhotel_gallery_images');
            var $addButton = $galleryField.find('.lbhotel-gallery-add');
            var $helpText = $galleryField.find('.lbhotel-gallery-help');
            var maxImages = parseInt($galleryField.data('max'), 10) || 0;
            var limitTemplate = ($helpText.data('limit-text') || '').toString();
            var adminData = window.lbHotelAdmin && window.lbHotelAdmin.gallery ? window.lbHotelAdmin.gallery : {};
            var frameTitle = adminData.frameTitle || 'Select images';
            var frameButton = adminData.frameButton || 'Use images';
            var removeText = adminData.removeImage || 'Remove';
            var maxReachedText = adminData.maxReached || '';
            var limitTemplateData = adminData.limitText || limitTemplate;
            var frame;

            if (adminData.maxImages) {
                maxImages = parseInt(adminData.maxImages, 10) || maxImages;
            }

            if (limitTemplateData) {
                limitTemplate = limitTemplateData;
            }

            function getIds() {
                return $galleryList.find('.lbhotel-gallery-item').map(function () {
                    return $(this).data('id').toString();
                }).get();
            }

            function updateHelp() {
                if (!limitTemplate) {
                    return;
                }

                var remaining = maxImages ? Math.max(0, maxImages - getIds().length) : 0;
                var text = limitTemplate.replace('%1$d', maxImages).replace('%2$d', remaining);
                $helpText.text(text);
            }

            function toggleAddButton() {
                if (!maxImages) {
                    return;
                }

                var disabled = getIds().length >= maxImages;
                $addButton.prop('disabled', disabled);
            }

            function syncGallery() {
                $galleryInput.val(getIds().join(','));
                updateHelp();
                toggleAddButton();
            }

            function appendImage(id, url) {
                var existingIds = getIds();

                if (existingIds.indexOf(id.toString()) !== -1) {
                    return;
                }

                if (maxImages && existingIds.length >= maxImages) {
                    return;
                }

                var $item = $('<li>', {
                    'class': 'lbhotel-gallery-item',
                    'data-id': id
                });

                var $thumb = $('<div>', { 'class': 'lbhotel-gallery-thumb' });
                $('<img>', {
                    src: url,
                    alt: ''
                }).appendTo($thumb);

                var $remove = $('<button>', {
                    type: 'button',
                    'class': 'button-link lbhotel-gallery-remove',
                    text: removeText
                });

                $item.append($thumb).append($remove);
                $galleryList.append($item);
            }

            function openFrame() {
                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: frameTitle,
                    button: {
                        text: frameButton
                    },
                    library: {
                        type: 'image'
                    },
                    multiple: true
                });

                frame.on('open', function () {
                    var selection = frame.state().get('selection');
                    var ids = getIds();

                    selection.reset();

                    ids.forEach(function (id) {
                        var attachment = wp.media.attachment(id);

                        if (attachment) {
                            attachment.fetch();
                            selection.add(attachment);
                        }
                    });
                });

                frame.on('select', function () {
                    var selection = frame.state().get('selection');
                    var existing = getIds();

                    selection.each(function (attachment) {
                        var data = attachment.toJSON();
                        var id = data && data.id ? data.id : null;

                        if (!id) {
                            return;
                        }

                        if (existing.indexOf(id.toString()) !== -1) {
                            return;
                        }

                        if (maxImages && existing.length >= maxImages) {
                            return;
                        }

                        var url = data && data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;

                        if (!url) {
                            return;
                        }

                        appendImage(id, url);
                        existing.push(id.toString());
                    });

                    syncGallery();
                });

                frame.open();
            }

            $addButton.on('click', function (event) {
                event.preventDefault();

                if (maxImages && getIds().length >= maxImages) {
                    if (maxReachedText) {
                        window.alert(maxReachedText);
                    }
                    return;
                }

                openFrame();
            });

            $galleryField.on('click', '.lbhotel-gallery-remove', function (event) {
                event.preventDefault();
                $(this).closest('.lbhotel-gallery-item').remove();
                syncGallery();
            });

            syncGallery();
        }
    });
})(jQuery);
