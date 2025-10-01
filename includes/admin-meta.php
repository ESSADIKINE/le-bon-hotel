<?php
/**
 * Admin meta boxes and meta registration.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register post meta for hotels.
 */
function lbhotel_register_meta_fields() {
    $meta_fields = array(
        'lbhotel_address'            => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'lbhotel_city'               => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'lbhotel_region'             => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'lbhotel_postal_code'        => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'lbhotel_country'            => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'lbhotel_checkin_time'       => array(
            'type'              => 'string',
            'sanitize_callback' => 'lbhotel_sanitize_time',
        ),
        'lbhotel_checkout_time'      => array(
            'type'              => 'string',
            'sanitize_callback' => 'lbhotel_sanitize_time',
        ),
        'lbhotel_rooms_total'        => array(
            'type'              => 'integer',
            'sanitize_callback' => 'lbhotel_sanitize_int',
        ),
        'lbhotel_avg_price_per_night'=> array(
            'type'              => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
        ),
        'lbhotel_has_free_breakfast' => array(
            'type'              => 'boolean',
            'sanitize_callback' => 'lbhotel_sanitize_bool',
        ),
        'lbhotel_has_parking'        => array(
            'type'              => 'boolean',
            'sanitize_callback' => 'lbhotel_sanitize_bool',
        ),
        'lbhotel_star_rating'        => array(
            'type'              => 'integer',
            'sanitize_callback' => 'lbhotel_sanitize_int',
        ),
        'lbhotel_gallery_images'     => array(
            'type'              => 'array',
            'sanitize_callback' => 'lbhotel_sanitize_gallery_images',
        ),
        'lbhotel_virtual_tour_url'   => array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ),
        'lbhotel_contact_phone'      => array(
            'type'              => 'string',
            'sanitize_callback' => 'lbhotel_sanitize_phone',
        ),
        'lbhotel_booking_url'        => array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ),
        'lbhotel_rooms'              => array(
            'type'              => 'array',
            'sanitize_callback' => 'lbhotel_sanitize_rooms',
        ),
        'lbhotel_latitude'           => array(
            'type'              => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
        ),
        'lbhotel_longitude'          => array(
            'type'              => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
        ),
    );

    foreach ( $meta_fields as $meta_key => $args ) {
        register_post_meta(
            'lbhotel_hotel',
            $meta_key,
            array(
                'type'              => $args['type'],
                'single'            => true,
                'sanitize_callback' => $args['sanitize_callback'],
                'auth_callback'     => 'lbhotel_can_edit_meta',
                'show_in_rest'      => true,
            )
        );
    }
}

/**
 * Sanitize gallery images meta.
 *
 * @param mixed $value Value.
 * @return array
 */
function lbhotel_sanitize_gallery_images( $value ) {
    if ( is_string( $value ) ) {
        $value = explode( ',', $value );
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    return array_values( array_filter( array_map( 'absint', $value ) ) );
}

/**
 * Basic phone sanitizer.
 *
 * @param string $value Phone.
 * @return string
 */
function lbhotel_sanitize_phone( $value ) {
    $value = preg_replace( '/[^0-9+\-\s]/', '', (string) $value );

    return sanitize_text_field( $value );
}

/**
 * Auth callback for post meta.
 *
 * @param bool   $allowed Whether allowed.
 * @param string $meta_key Meta key.
 * @param int    $post_id Post ID.
 * @param int    $user_id User ID.
 * @param string $cap Capability.
 * @param array  $caps Caps.
 * @return bool
 */
function lbhotel_can_edit_meta( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
    unset( $allowed, $meta_key, $user_id, $cap, $caps );

    return current_user_can( 'edit_post', $post_id );
}

/**
 * Setup admin meta boxes.
 */
function lbhotel_setup_admin_meta_boxes() {
    static $bootstrapped = false;

    if ( $bootstrapped ) {
        return;
    }

    $bootstrapped = true;

    add_action( 'add_meta_boxes_lbhotel_hotel', 'lbhotel_register_meta_boxes' );
    add_action( 'save_post_lbhotel_hotel', 'lbhotel_save_meta', 10, 2 );
    add_action( 'quick_edit_custom_box', 'lbhotel_quick_edit_fields', 10, 2 );
    add_action( 'save_post_lbhotel_hotel', 'lbhotel_save_quick_edit_meta', 20, 2 );
}

/**
 * Register hotel meta boxes.
 */
function lbhotel_register_meta_boxes() {
    add_meta_box(
        'lbhotel_details_meta',
        __( 'Hotel Details', 'lbhotel' ),
        'lbhotel_render_details_meta_box',
        'lbhotel_hotel',
        'normal',
        'high'
    );

    add_meta_box(
        'lbhotel_location_meta',
        __( 'Location', 'lbhotel' ),
        'lbhotel_render_location_meta_box',
        'lbhotel_hotel',
        'normal',
        'default'
    );

    add_meta_box(
        'lbhotel_rooms_meta',
        __( 'Rooms & Suites', 'lbhotel' ),
        'lbhotel_render_rooms_meta_box',
        'lbhotel_hotel',
        'normal',
        'default'
    );

    add_meta_box(
        'lbhotel_contact_meta',
        __( 'Contact & Media', 'lbhotel' ),
        'lbhotel_render_contact_meta_box',
        'lbhotel_hotel',
        'side',
        'default'
    );
}

/**
 * Render hotel details meta box.
 *
 * @param WP_Post $post Post object.
 */
function lbhotel_render_details_meta_box( $post ) {
    wp_nonce_field( 'lbhotel_save_meta', 'lbhotel_meta_nonce' );

    $checkin  = get_post_meta( $post->ID, 'lbhotel_checkin_time', true );
    $checkout = get_post_meta( $post->ID, 'lbhotel_checkout_time', true );
    $rooms    = get_post_meta( $post->ID, 'lbhotel_rooms_total', true );
    $price    = get_post_meta( $post->ID, 'lbhotel_avg_price_per_night', true );
    $stars    = get_post_meta( $post->ID, 'lbhotel_star_rating', true );
    $breakfast= get_post_meta( $post->ID, 'lbhotel_has_free_breakfast', true );
    $parking  = get_post_meta( $post->ID, 'lbhotel_has_parking', true );

    echo '<p><label for="lbhotel_checkin_time"><strong>' . esc_html__( 'Check-in Time', 'lbhotel' ) . '</strong></label><br />';
    echo '<input type="time" id="lbhotel_checkin_time" name="lbhotel_checkin_time" value="' . esc_attr( $checkin ?: lbhotel_get_option( 'default_checkin_time' ) ) . '" /></p>';

    echo '<p><label for="lbhotel_checkout_time"><strong>' . esc_html__( 'Check-out Time', 'lbhotel' ) . '</strong></label><br />';
    echo '<input type="time" id="lbhotel_checkout_time" name="lbhotel_checkout_time" value="' . esc_attr( $checkout ?: lbhotel_get_option( 'default_checkout_time' ) ) . '" /></p>';

    echo '<p><label for="lbhotel_rooms_total"><strong>' . esc_html__( 'Total Rooms', 'lbhotel' ) . '</strong></label><br />';
    echo '<input type="number" min="0" id="lbhotel_rooms_total" name="lbhotel_rooms_total" value="' . esc_attr( $rooms ) . '" /></p>';

    echo '<p><label for="lbhotel_avg_price_per_night"><strong>' . esc_html__( 'Average Price per Night', 'lbhotel' ) . '</strong></label><br />';
    echo '<input type="number" step="0.01" id="lbhotel_avg_price_per_night" name="lbhotel_avg_price_per_night" value="' . esc_attr( $price ) . '" /> ' . esc_html( lbhotel_get_option( 'default_currency' ) ) . '</p>';

    echo '<p><label for="lbhotel_star_rating"><strong>' . esc_html__( 'Star Rating', 'lbhotel' ) . '</strong></label><br />';
    echo '<select id="lbhotel_star_rating" name="lbhotel_star_rating">';
    echo '<option value="">' . esc_html__( 'Select rating', 'lbhotel' ) . '</option>';
    for ( $i = 1; $i <= 5; $i++ ) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr( $i ),
            selected( (int) $stars, $i, false ),
            esc_html( sprintf( _n( '%d Star', '%d Stars', $i, 'lbhotel' ), $i ) )
        );
    }
    echo '</select></p>';

    echo '<p><label><input type="checkbox" name="lbhotel_has_free_breakfast" value="1" ' . checked( (bool) $breakfast, true, false ) . ' /> ' . esc_html__( 'Includes free breakfast', 'lbhotel' ) . '</label></p>';
    echo '<p><label><input type="checkbox" name="lbhotel_has_parking" value="1" ' . checked( (bool) $parking, true, false ) . ' /> ' . esc_html__( 'On-site parking available', 'lbhotel' ) . '</label></p>';
}

/**
 * Render location meta box.
 *
 * @param WP_Post $post Post.
 */
function lbhotel_render_location_meta_box( $post ) {
    $address = get_post_meta( $post->ID, 'lbhotel_address', true );
    $city    = get_post_meta( $post->ID, 'lbhotel_city', true );
    $region  = get_post_meta( $post->ID, 'lbhotel_region', true );
    $postal  = get_post_meta( $post->ID, 'lbhotel_postal_code', true );
    $country = get_post_meta( $post->ID, 'lbhotel_country', true );
    $lat     = get_post_meta( $post->ID, 'lbhotel_latitude', true );
    $lng     = get_post_meta( $post->ID, 'lbhotel_longitude', true );

    echo '<p><label for="lbhotel_address">' . esc_html__( 'Street Address', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_address" name="lbhotel_address" value="' . esc_attr( $address ) . '" /></p>';

    echo '<p><label for="lbhotel_city">' . esc_html__( 'City', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_city" name="lbhotel_city" value="' . esc_attr( $city ) . '" /></p>';

    echo '<p><label for="lbhotel_region">' . esc_html__( 'Region/State', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_region" name="lbhotel_region" value="' . esc_attr( $region ) . '" /></p>';

    echo '<p><label for="lbhotel_postal_code">' . esc_html__( 'Postal Code', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_postal_code" name="lbhotel_postal_code" value="' . esc_attr( $postal ) . '" /></p>';

    echo '<p><label for="lbhotel_country">' . esc_html__( 'Country', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_country" name="lbhotel_country" value="' . esc_attr( $country ) . '" /></p>';

    echo '<div class="lbhotel-location-coordinates">';
    echo '<p><label for="lbhotel_latitude">' . esc_html__( 'Latitude', 'lbhotel' ) . '</label><br />';
    echo '<input type="number" step="0.000001" class="widefat" id="lbhotel_latitude" name="lbhotel_latitude" value="' . esc_attr( $lat ) . '" /></p>';

    echo '<p><label for="lbhotel_longitude">' . esc_html__( 'Longitude', 'lbhotel' ) . '</label><br />';
    echo '<input type="number" step="0.000001" class="widefat" id="lbhotel_longitude" name="lbhotel_longitude" value="' . esc_attr( $lng ) . '" /></p>';
    echo '</div>';
}

/**
 * Render rooms meta box.
 *
 * @param WP_Post $post Post.
 */
function lbhotel_render_rooms_meta_box( $post ) {
    $rooms = get_post_meta( $post->ID, 'lbhotel_rooms', true );

    if ( empty( $rooms ) ) {
        $rooms = array();
    }

    echo '<div id="lbhotel-rooms-manager" class="lbhotel-rooms-manager" data-locale-add="' . esc_attr__( 'Add room type', 'lbhotel' ) . '" data-locale-remove="' . esc_attr__( 'Remove', 'lbhotel' ) . '">';

    foreach ( $rooms as $index => $room ) {
        lbhotel_render_room_row( $index, $room );
    }

    if ( empty( $rooms ) ) {
        lbhotel_render_room_row( 0, array() );
    }

    echo '<button type="button" class="button lbhotel-add-room">' . esc_html__( 'Add room type', 'lbhotel' ) . '</button>';
    echo '<input type="hidden" name="lbhotel_rooms_json" id="lbhotel_rooms_json" value="' . esc_attr( wp_json_encode( $rooms ) ) . '" />';
    echo '</div>';
}

/**
 * Render a single room row.
 *
 * @param int   $index Index.
 * @param array $room Room data.
 */
function lbhotel_render_room_row( $index, $room ) {
    $name        = isset( $room['name'] ) ? $room['name'] : '';
    $price       = isset( $room['price'] ) ? $room['price'] : '';
    $capacity    = isset( $room['capacity'] ) ? $room['capacity'] : '';
    $images      = isset( $room['images'] ) ? implode( ',', (array) $room['images'] ) : '';
    $availability= isset( $room['availability'] ) ? $room['availability'] : '';

    echo '<div class="lbhotel-room" data-index="' . esc_attr( $index ) . '">';
    echo '<p><label>' . esc_html__( 'Room name', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat lbhotel-room-name" value="' . esc_attr( $name ) . '" /></p>';

    echo '<div class="lbhotel-room-grid">';
    echo '<p><label>' . esc_html__( 'Price', 'lbhotel' ) . '</label><br /><input type="number" step="0.01" class="lbhotel-room-price" value="' . esc_attr( $price ) . '" /></p>';
    echo '<p><label>' . esc_html__( 'Capacity', 'lbhotel' ) . '</label><br /><input type="number" class="lbhotel-room-capacity" value="' . esc_attr( $capacity ) . '" /></p>';
    echo '<p><label>' . esc_html__( 'Availability', 'lbhotel' ) . '</label><br /><input type="text" class="lbhotel-room-availability" value="' . esc_attr( $availability ) . '" placeholder="' . esc_attr__( 'e.g. Available, Sold out', 'lbhotel' ) . '" /></p>';
    echo '</div>';

    echo '<p><label>' . esc_html__( 'Image URLs or IDs (comma separated)', 'lbhotel' ) . '</label><br /><input type="text" class="widefat lbhotel-room-images" value="' . esc_attr( $images ) . '" /></p>';
    echo '<button type="button" class="button-link-delete lbhotel-remove-room">' . esc_html__( 'Remove', 'lbhotel' ) . '</button>';
    echo '<hr /></div>';
}

/**
 * Render contact meta box.
 *
 * @param WP_Post $post Post.
 */
function lbhotel_render_contact_meta_box( $post ) {
    $phone   = get_post_meta( $post->ID, 'lbhotel_contact_phone', true );
    $tour    = get_post_meta( $post->ID, 'lbhotel_virtual_tour_url', true );
    $booking = get_post_meta( $post->ID, 'lbhotel_booking_url', true );
    $gallery = get_post_meta( $post->ID, 'lbhotel_gallery_images', true );

    echo '<p><label for="lbhotel_contact_phone">' . esc_html__( 'Contact phone', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_contact_phone" name="lbhotel_contact_phone" value="' . esc_attr( $phone ) . '" /></p>';

    echo '<p><label for="lbhotel_booking_url">' . esc_html__( 'Booking URL', 'lbhotel' ) . '</label><br />';
    echo '<input type="url" class="widefat" id="lbhotel_booking_url" name="lbhotel_booking_url" value="' . esc_attr( $booking ) . '" placeholder="https://" /></p>';

    echo '<p><label for="lbhotel_virtual_tour_url">' . esc_html__( 'Virtual tour URL', 'lbhotel' ) . '</label><br />';
    echo '<input type="url" class="widefat" id="lbhotel_virtual_tour_url" name="lbhotel_virtual_tour_url" value="' . esc_attr( $tour ) . '" placeholder="https://" /></p>';

    echo '<p><label for="lbhotel_gallery_images">' . esc_html__( 'Gallery image IDs (comma separated)', 'lbhotel' ) . '</label><br />';
    echo '<input type="text" class="widefat" id="lbhotel_gallery_images" name="lbhotel_gallery_images" value="' . esc_attr( is_array( $gallery ) ? implode( ',', $gallery ) : $gallery ) . '" placeholder="123,124" /></p>';
}

/**
 * Save meta box data.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function lbhotel_save_meta( $post_id, $post ) {
    if ( ! isset( $_POST['lbhotel_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbhotel_meta_nonce'] ) ), 'lbhotel_save_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'lbhotel_address'            => 'sanitize_text_field',
        'lbhotel_city'               => 'sanitize_text_field',
        'lbhotel_region'             => 'sanitize_text_field',
        'lbhotel_postal_code'        => 'sanitize_text_field',
        'lbhotel_country'            => 'sanitize_text_field',
        'lbhotel_checkin_time'       => 'lbhotel_sanitize_time',
        'lbhotel_checkout_time'      => 'lbhotel_sanitize_time',
        'lbhotel_rooms_total'        => 'lbhotel_sanitize_int',
        'lbhotel_avg_price_per_night'=> 'lbhotel_sanitize_decimal',
        'lbhotel_star_rating'        => 'lbhotel_sanitize_int',
        'lbhotel_contact_phone'      => 'lbhotel_sanitize_phone',
        'lbhotel_virtual_tour_url'   => 'esc_url_raw',
        'lbhotel_booking_url'        => 'esc_url_raw',
        'lbhotel_latitude'           => 'lbhotel_sanitize_decimal',
        'lbhotel_longitude'          => 'lbhotel_sanitize_decimal',
    );

    foreach ( $fields as $meta_key => $sanitize ) {
        if ( isset( $_POST[ $meta_key ] ) ) {
            $raw = wp_unslash( $_POST[ $meta_key ] );
            if ( '' === trim( (string) $raw ) ) {
                delete_post_meta( $post_id, $meta_key );
                continue;
            }

            $value = call_user_func( $sanitize, $raw );
            if ( 'lbhotel_star_rating' === $meta_key ) {
                $value = max( 0, min( 5, (int) $value ) );
            }
            update_post_meta( $post_id, $meta_key, $value );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }

    update_post_meta( $post_id, 'lbhotel_has_free_breakfast', isset( $_POST['lbhotel_has_free_breakfast'] ) );
    update_post_meta( $post_id, 'lbhotel_has_parking', isset( $_POST['lbhotel_has_parking'] ) );

    if ( isset( $_POST['lbhotel_gallery_images'] ) ) {
        $gallery = lbhotel_sanitize_gallery_images( wp_unslash( $_POST['lbhotel_gallery_images'] ) );
        update_post_meta( $post_id, 'lbhotel_gallery_images', $gallery );
    }

    if ( isset( $_POST['lbhotel_rooms_json'] ) ) {
        $rooms = lbhotel_sanitize_rooms( wp_unslash( $_POST['lbhotel_rooms_json'] ) );
        update_post_meta( $post_id, 'lbhotel_rooms', $rooms );
    }
}

/**
 * Output quick edit fields.
 *
 * @param string $column_name Column name.
 * @param string $post_type   Post type.
 */
function lbhotel_quick_edit_fields( $column_name, $post_type ) {
    if ( 'lbhotel_hotel' !== $post_type ) {
        return;
    }

    if ( 'lbhotel_star_rating' === $column_name ) {
        echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><label class="alignleft"><span class="title">' . esc_html__( 'Star Rating', 'lbhotel' ) . '</span>';
        echo '<span class="input-text-wrap"><select name="lbhotel_star_rating">';
        echo '<option value="">' . esc_html__( 'Select', 'lbhotel' ) . '</option>';
        for ( $i = 1; $i <= 5; $i++ ) {
            echo '<option value="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</option>';
        }
        echo '</select></span></label></div></fieldset>';
    }

    if ( 'lbhotel_city' === $column_name ) {
        echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><label class="alignleft"><span class="title">' . esc_html__( 'City', 'lbhotel' ) . '</span>';
        echo '<span class="input-text-wrap"><input type="text" name="lbhotel_city" value="" /></span></label></div></fieldset>';
    }
}

/**
 * Save quick edit fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function lbhotel_save_quick_edit_meta( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['lbhotel_star_rating'] ) ) {
        update_post_meta( $post_id, 'lbhotel_star_rating', lbhotel_sanitize_int( wp_unslash( $_POST['lbhotel_star_rating'] ) ) );
    }

    if ( isset( $_POST['lbhotel_city'] ) ) {
        update_post_meta( $post_id, 'lbhotel_city', sanitize_text_field( wp_unslash( $_POST['lbhotel_city'] ) ) );
    }
}
