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
 * Sanitize rating values ensuring they are between 0 and 5 with one decimal precision.
 *
 * @param mixed $value Raw rating value.
 * @return float
 */
function lbhotel_sanitize_rating( $value ) {
    $numeric = lbhotel_sanitize_decimal( $value );

    if ( $numeric < 0 ) {
        $numeric = 0;
    }

    if ( $numeric > 5 ) {
        $numeric = 5;
    }

    return round( $numeric, 1 );
}

/**
 * Provide a mapping between Virtual Maroc meta keys and legacy meta keys.
 *
 * @return array<string,string>
 */
function lbhotel_get_vm_meta_key_map() {
    return array(
        'vm_booking_url'          => 'lbhotel_booking_url',
        'vm_room_types'           => 'lbhotel_room_types',
        'vm_hotel_type'           => 'lbhotel_hotel_type',
        'vm_menu_url'             => 'lbhotel_menu_url',
        'vm_specialties'          => 'lbhotel_specialties',
        'vm_opening_hours'        => 'lbhotel_opening_hours',
        'vm_event_datetime'       => 'lbhotel_event_date_time',
        'vm_ticket_url'           => 'lbhotel_ticket_url',
        'vm_event_type'           => 'lbhotel_event_type',
        'vm_activity_type'        => 'lbhotel_activity_type',
        'vm_seasonality'          => 'lbhotel_seasonality',
        'vm_product_categories'   => 'lbhotel_product_categories',
        'vm_sales_url'            => 'lbhotel_sales_url',
        'vm_sport_type'           => 'lbhotel_sport_type',
        'vm_equipment_rental_url' => 'lbhotel_equipment_rental_url',
        'vm_ticket_info_url'      => 'lbhotel_ticket_price_url',
        'vm_virtual_tour_url'     => 'lbhotel_virtual_tour_url',
        'vm_google_map_url'       => 'lbhotel_google_maps_url',
        'vm_contact_phone'        => 'lbhotel_contact_phone',
        'vm_street_address'       => 'lbhotel_address',
        'vm_city'                 => 'lbhotel_city',
        'vm_region'               => 'lbhotel_region',
        'vm_country'              => 'lbhotel_country',
        'vm_postal_code'          => 'lbhotel_postal_code',
        'vm_latitude'             => 'lbhotel_latitude',
        'vm_longitude'            => 'lbhotel_longitude',
        'vm_gallery'              => 'lbhotel_gallery_images',
        'vm_rating'               => 'lbhotel_star_rating',
    );
}

/**
 * Retrieve meta value prioritising Virtual Maroc keys with fallback to legacy keys.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Primary meta key.
 * @param mixed  $default  Default value if none stored.
 * @return mixed
 */
function lbhotel_get_meta_with_fallback( $post_id, $meta_key, $default = '' ) {
    $value = get_post_meta( $post_id, $meta_key, true );

    if ( '' !== $value && null !== $value ) {
        return $value;
    }

    $map = lbhotel_get_vm_meta_key_map();

    if ( isset( $map[ $meta_key ] ) ) {
        $value = get_post_meta( $post_id, $map[ $meta_key ], true );
    } elseif ( 0 === strpos( $meta_key, 'vm_' ) ) {
        $legacy_key = 'lbhotel_' . substr( $meta_key, 3 );
        $value      = get_post_meta( $post_id, $legacy_key, true );
    }

    if ( '' === $value || null === $value ) {
        return $default;
    }

    return $value;
}

/**
 * Retrieve a numeric meta value with fallback handling.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 * @param float  $default  Default value.
 * @return float
 */
function lbhotel_get_numeric_meta_with_fallback( $post_id, $meta_key, $default = 0.0 ) {
    $value = lbhotel_get_meta_with_fallback( $post_id, $meta_key, $default );

    if ( '' === $value || null === $value ) {
        return (float) $default;
    }

    return lbhotel_sanitize_decimal( $value );
}

/**
 * Retrieve the rating value for a post.
 *
 * @param int $post_id Post ID.
 * @return float
 */
function lbhotel_get_rating_value( $post_id ) {
    $rating = lbhotel_get_numeric_meta_with_fallback( $post_id, 'vm_rating', 0 );

    if ( $rating <= 0 ) {
        $rating = lbhotel_get_numeric_meta_with_fallback( $post_id, 'lbhotel_star_rating', 0 );
    }

    if ( $rating <= 0 ) {
        $default = lbhotel_get_option( 'default_star_rating' );
        if ( null !== $default ) {
            $rating = (float) $default;
        }
    }

    return max( 0, min( 5, round( $rating, 1 ) ) );
}

/**
 * Build accessible rating markup from a numeric rating value.
 *
 * @param float $rating Numeric rating between 0 and 5.
 * @param array $args   Optional arguments.
 * @return string
 */
function lbhotel_get_rating_markup( $rating, $args = array() ) {
    $defaults = array(
        'show_value' => true,
        'class'      => 'lbhotel-rating',
    );

    $args   = wp_parse_args( $args, $defaults );
    $rating = max( 0, min( 5, (float) $rating ) );

    if ( $rating <= 0 ) {
        return '';
    }

    $rounded_half = round( $rating * 2 ) / 2;
    $full_stars   = floor( $rounded_half );
    $has_half     = $rounded_half - $full_stars >= 0.5;

    $stars_markup = '';

    for ( $i = 1; $i <= 5; $i++ ) {
        if ( $i <= $full_stars ) {
            $stars_markup .= '<span class="lbhotel-rating__star is-full" aria-hidden="true">★</span>';
        } elseif ( $has_half && $i === $full_stars + 1 ) {
            $stars_markup .= '<span class="lbhotel-rating__star is-half" aria-hidden="true">☆</span>';
        } else {
            $stars_markup .= '<span class="lbhotel-rating__star is-empty" aria-hidden="true">☆</span>';
        }
    }

    $output  = '<div class="' . esc_attr( $args['class'] ) . '" data-lbhotel-rating="' . esc_attr( $rounded_half ) . '">';
    $output .= '<span class="lbhotel-rating__stars">' . $stars_markup . '</span>';
    $output .= '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Rated %s out of 5', 'lbhotel' ), number_format_i18n( $rounded_half, 1 ) ) ) . '</span>';

    if ( $args['show_value'] ) {
        $output .= '<span class="lbhotel-rating__value">' . esc_html( number_format_i18n( $rounded_half, 1 ) ) . '</span>';
    }

    $output .= '</div>';

    return $output;
}

