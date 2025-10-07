<?php
/**
 * Helper utilities for Le Bon Hotel.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return plugin defaults.
 *
 * @return array
 */
function lbhotel_get_default_settings() {
    return array(
        'default_checkin_time'  => '14:00',
        'default_checkout_time' => '12:00',
        'default_currency'      => 'MAD',
        'enable_booking_widget' => true,
        'default_star_rating'   => 4,
    );
}

/**
 * Retrieve a plugin option with fallback to defaults.
 *
 * @param string $key Option key.
 * @return mixed
 */
function lbhotel_get_option( $key ) {
    $options  = get_option( 'lbhotel_settings', array() );
    $defaults = lbhotel_get_default_settings();

    return isset( $options[ $key ] ) ? $options[ $key ] : ( $defaults[ $key ] ?? null );
}

/**
 * Sanitize time field (HH:MM).
 *
 * @param string $value Raw value.
 * @return string
 */
function lbhotel_sanitize_time( $value ) {
    $value = trim( $value );
    if ( preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ) {
        return $value;
    }

    return '';
}

/**
 * Sanitize integer meta value.
 *
 * @param mixed $value Value.
 * @return int
 */
function lbhotel_sanitize_int( $value ) {
    return (int) $value;
}

/**
 * Sanitize boolean meta value.
 *
 * @param mixed $value Value.
 * @return bool
 */
function lbhotel_sanitize_bool( $value ) {
    return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Sanitize decimal meta value.
 *
 * @param mixed $value Value.
 * @return float
 */
function lbhotel_sanitize_decimal( $value ) {
    $value = str_replace( ',', '.', (string) $value );

    return floatval( $value );
}

/**
 * Sanitize rating meta values.
 *
 * @param mixed $value Raw value.
 * @return float
 */
function lbhotel_sanitize_rating( $value ) {
    $value = floatval( str_replace( ',', '.', (string) $value ) );

    if ( $value < 0 ) {
        $value = 0;
    }

    if ( $value > 5 ) {
        $value = 5;
    }

    return round( $value, 1 );
}

/**
 * Sanitize multi-line text fields while preserving line breaks.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function lbhotel_sanitize_multiline_text( $value ) {
    if ( is_array( $value ) ) {
        $value = implode( "\n", $value );
    }

    $value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
    $value = preg_replace( '/\n+/', "\n", $value );

    $lines = array_map( 'sanitize_text_field', explode( "\n", $value ) );
    $lines = array_filter( $lines, static function ( $line ) {
        return '' !== trim( $line );
    } );

    return implode( "\n", $lines );
}

/**
 * Sanitize datetime-local input values.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function lbhotel_sanitize_datetime_local( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    $normalized = str_replace( ' ', 'T', $value );

    if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/', $normalized ) ) {
        return $normalized;
    }

    return '';
}

/**
 * Bootstrap Gutenberg blocks (placeholder for future block registration).
 */
function lbhotel_bootstrap_blocks() {
    // Placeholder for block registration using block.json files.
}

/**
 * Retrieve the canonical vm_* meta key fallback map.
 *
 * @return array<string,string[]>
 */
function lbhotel_get_vm_meta_legacy_map() {
    return array(
        'vm_virtual_tour_url' => array( 'lbhotel_virtual_tour_url', 'virtual_tour_url' ),
        'vm_google_map_url'   => array( 'lbhotel_google_maps_url' ),
        'vm_latitude'         => array( 'lbhotel_latitude' ),
        'vm_longitude'        => array( 'lbhotel_longitude' ),
        'vm_street_address'   => array( 'lbhotel_address' ),
        'vm_city'             => array( 'lbhotel_city' ),
        'vm_region'           => array( 'lbhotel_region' ),
        'vm_postal_code'      => array( 'lbhotel_postal_code' ),
        'vm_country'          => array( 'lbhotel_country' ),
        'vm_contact_phone'    => array( 'lbhotel_contact_phone' ),
        'vm_gallery'          => array( 'lbhotel_gallery_images' ),
        'vm_rating'           => array( 'lbhotel_star_rating' ),
        'vm_booking_url'      => array( 'lbhotel_booking_url' ),
        'vm_hotel_type'       => array( 'lbhotel_hotel_type' ),
        'vm_menu_url'         => array( 'lbhotel_menu_url' ),
        'vm_specialties'      => array( 'lbhotel_specialties' ),
        'vm_opening_hours'    => array( 'lbhotel_opening_hours' ),
        'vm_event_datetime'   => array( 'lbhotel_event_datetime' ),
        'vm_ticket_url'       => array( 'lbhotel_ticket_url', 'lbhotel_event_schedule_url' ),
        'vm_event_type'       => array( 'lbhotel_event_type' ),
        'vm_activity_type'    => array( 'lbhotel_activity_type' ),
        'vm_product_categories' => array( 'lbhotel_product_categories' ),
        'vm_sales_url'        => array( 'lbhotel_ticket_price_url' ),
        'vm_sport_type'       => array( 'lbhotel_sport_type' ),
        'vm_equipment_rental_url' => array( 'lbhotel_equipment_rental_url' ),
        'vm_ticket_info_url'  => array( 'lbhotel_ticket_price_url' ),
    );
}

/**
 * Retrieve post meta with fallback to legacy keys.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 * @param mixed  $default  Default value.
 * @return mixed
 */
function lbhotel_get_place_meta( $post_id, $meta_key, $default = '' ) {
    $value = get_post_meta( $post_id, $meta_key, true );

    if ( '' !== $value && null !== $value && array() !== $value ) {
        return $value;
    }

    $map = lbhotel_get_vm_meta_legacy_map();

    if ( isset( $map[ $meta_key ] ) ) {
        foreach ( $map[ $meta_key ] as $legacy_key ) {
            $legacy_value = get_post_meta( $post_id, $legacy_key, true );

            if ( '' !== $legacy_value && null !== $legacy_value && array() !== $legacy_value ) {
                return $legacy_value;
            }
        }
    }

    return $default;
}

/**
 * Render rating stars markup for a given value.
 *
 * @param float $rating Rating value.
 * @return string
 */
function lbhotel_render_rating_stars( $rating ) {
    $rating     = lbhotel_sanitize_rating( $rating );
    $rounded    = round( $rating * 2 ) / 2;
    $full_stars = (int) floor( $rounded );
    $half_star  = ( $rounded - $full_stars ) >= 0.5;
    $empty      = 5 - $full_stars - ( $half_star ? 1 : 0 );

    $markup = '<div class="vm-rating" aria-label="' . esc_attr( sprintf( __( '%1$.1f out of 5 stars', 'lbhotel' ), $rating ) ) . '">';

    for ( $i = 0; $i < $full_stars; $i++ ) {
        $markup .= '<span class="vm-rating__star vm-rating__star--full" aria-hidden="true">★</span>';
    }

    if ( $half_star ) {
        $markup .= '<span class="vm-rating__star vm-rating__star--half" aria-hidden="true">☆</span>';
    }

    for ( $i = 0; $i < $empty; $i++ ) {
        $markup .= '<span class="vm-rating__star vm-rating__star--empty" aria-hidden="true">☆</span>';
    }

    $markup .= '<span class="vm-rating__value">' . esc_html( number_format_i18n( $rating, 1 ) ) . '</span>';
    $markup .= '</div>';

    return $markup;
}

