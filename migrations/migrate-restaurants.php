<?php
/**
 * Migration helpers from the legacy restaurant plugin.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Migrate restaurant posts to hotels.
 *
 * @return int|WP_Error Number of migrated posts or WP_Error on failure.
 */
function lbhotel_migrate_from_restaurant() {
    if ( ! post_type_exists( 'restaurant' ) ) {
        return new WP_Error( 'lbhotel_no_restaurants', __( 'Restaurant post type not available for migration.', 'lbhotel' ) );
    }

    $restaurants = get_posts(
        array(
            'post_type'      => 'restaurant',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
        )
    );

    if ( empty( $restaurants ) ) {
        update_option( 'lbhotel_restaurant_migrated', time() );
        return 0;
    }

    $count = 0;

    foreach ( $restaurants as $restaurant ) {
        $update = array(
            'ID'        => $restaurant->ID,
            'post_type' => 'lbhotel_hotel',
        );

        $result = wp_update_post( $update, true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        lbhotel_migrate_restaurant_meta( $restaurant->ID );
        lbhotel_migrate_restaurant_terms( $restaurant->ID );

        clean_post_cache( $restaurant->ID );
        $count++;
    }

    update_option( 'lbhotel_restaurant_migrated', time() );

    return $count;
}

/**
 * Map restaurant meta to hotel meta.
 *
 * @param int $post_id Post ID.
 */
function lbhotel_migrate_restaurant_meta( $post_id ) {
    $mapping = array(
        'address'        => 'lbhotel_address',
        'avg_price'      => 'lbhotel_avg_price_per_night',
        'seating_capacity' => 'lbhotel_rooms_total',
        'rating'         => 'lbhotel_star_rating',
        'virtual_tour_url' => 'lbhotel_virtual_tour_url',
        'contact_phone'  => 'lbhotel_contact_phone',
    );

    foreach ( $mapping as $old_key => $new_key ) {
        $value = get_post_meta( $post_id, $old_key, true );
        if ( '' === $value || null === $value ) {
            continue;
        }

        switch ( $new_key ) {
            case 'lbhotel_rooms_total':
            case 'lbhotel_star_rating':
                $value = lbhotel_sanitize_int( $value );
                break;
            case 'lbhotel_avg_price_per_night':
                $value = lbhotel_sanitize_decimal( $value );
                break;
            case 'lbhotel_contact_phone':
                $value = lbhotel_sanitize_phone( $value );
                break;
            default:
                $value = sanitize_text_field( $value );
                break;
        }

        update_post_meta( $post_id, $new_key, $value );
    }

    // Map booleans.
    update_post_meta( $post_id, 'lbhotel_has_free_breakfast', (bool) get_post_meta( $post_id, 'has_delivery', true ) );
    update_post_meta( $post_id, 'lbhotel_has_parking', (bool) get_post_meta( $post_id, 'takeaway', true ) );

    // Opening hours to check-in/out.
    $opening = get_post_meta( $post_id, 'opening_hours', true );
    if ( $opening ) {
        $parts = preg_split( '/\s*-\s*/', $opening );
        if ( isset( $parts[0] ) ) {
            update_post_meta( $post_id, 'lbhotel_checkin_time', lbhotel_sanitize_time( $parts[0] ) );
        }
        if ( isset( $parts[1] ) ) {
            update_post_meta( $post_id, 'lbhotel_checkout_time', lbhotel_sanitize_time( $parts[1] ) );
        }
    }

    // Gallery images.
    $gallery = get_post_meta( $post_id, 'gallery_images', true );
    if ( ! empty( $gallery ) ) {
        update_post_meta( $post_id, 'lbhotel_gallery_images', lbhotel_sanitize_gallery_images( $gallery ) );
    }

    // Booking URL was not present previously; attempt to reuse menu_url if available.
    $booking = get_post_meta( $post_id, 'booking_url', true );
    if ( ! $booking ) {
        $booking = get_post_meta( $post_id, 'menu_url', true );
    }
    if ( $booking ) {
        update_post_meta( $post_id, 'lbhotel_booking_url', esc_url_raw( $booking ) );
    }

    // Rooms JSON fallback from seating capacity.
    $rooms_total = (int) get_post_meta( $post_id, 'seating_capacity', true );
    if ( $rooms_total ) {
        $rooms = array(
            array(
                'name'         => __( 'Standard Room', 'lbhotel' ),
                'price'        => lbhotel_sanitize_decimal( get_post_meta( $post_id, 'avg_price', true ) ),
                'capacity'     => 2,
                'images'       => array(),
                'availability' => __( 'Available', 'lbhotel' ),
            ),
        );
        update_post_meta( $post_id, 'lbhotel_rooms', $rooms );
    }

    // Attempt to split address into city when stored in meta.
    $address = get_post_meta( $post_id, 'address', true );
    if ( $address ) {
        $parts = array_map( 'trim', explode( ',', $address ) );
        if ( isset( $parts[1] ) && ! get_post_meta( $post_id, 'lbhotel_city', true ) ) {
            update_post_meta( $post_id, 'lbhotel_city', $parts[1] );
        }

        if ( count( $parts ) >= 3 ) {
            $region_index = count( $parts ) - 2;
            if ( ! get_post_meta( $post_id, 'lbhotel_region', true ) ) {
                update_post_meta( $post_id, 'lbhotel_region', $parts[ $region_index ] );
            }
            if ( ! get_post_meta( $post_id, 'lbhotel_country', true ) ) {
                update_post_meta( $post_id, 'lbhotel_country', $parts[ count( $parts ) - 1 ] );
            }
        }
    }
}

/**
 * Map restaurant taxonomies to hotel taxonomies.
 *
 * @param int $post_id Post ID.
 */
function lbhotel_migrate_restaurant_terms( $post_id ) {
    $type_terms = wp_get_post_terms( $post_id, 'cuisine', array( 'fields' => 'names' ) );

    if ( ! empty( $type_terms ) ) {
        $mapped_types = array();
        $allowed      = array( 'Boutique', 'Business', 'Resort', 'Hostel', 'Apartment' );

        foreach ( $type_terms as $term_name ) {
            $normalized = ucfirst( strtolower( $term_name ) );
            if ( in_array( $normalized, $allowed, true ) ) {
                $mapped_types[] = $normalized;
            } else {
                $mapped_types[] = 'Boutique';
            }
        }

        wp_set_object_terms( $post_id, $mapped_types, 'lbhotel_hotel_type', false );
    }

    $location_terms = wp_get_post_terms( $post_id, 'location', array( 'fields' => 'names' ) );

    if ( ! empty( $location_terms ) ) {
        wp_set_object_terms( $post_id, $location_terms, 'lbhotel_location', false );
    }
}
