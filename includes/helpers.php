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
 * Ensure rooms JSON is valid structure.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function lbhotel_sanitize_rooms( $value ) {
    if ( is_string( $value ) ) {
        $value = json_decode( wp_unslash( $value ), true );
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    $rooms = array();

    foreach ( $value as $room ) {
        if ( empty( $room['name'] ) ) {
            continue;
        }

        $rooms[] = array(
            'name'         => sanitize_text_field( $room['name'] ),
            'price'        => isset( $room['price'] ) ? lbhotel_sanitize_decimal( $room['price'] ) : 0,
            'capacity'     => isset( $room['capacity'] ) ? lbhotel_sanitize_int( $room['capacity'] ) : 0,
            'images'       => isset( $room['images'] ) && is_array( $room['images'] ) ? array_map( 'lbhotel_sanitize_room_image', $room['images'] ) : array(),
            'availability' => isset( $room['availability'] ) ? sanitize_text_field( $room['availability'] ) : '',
        );
    }

    return $rooms;
}

/**
 * Sanitize a room image value (ID or URL).
 *
 * @param mixed $value Image value.
 * @return mixed
 */
function lbhotel_sanitize_room_image( $value ) {
    if ( is_numeric( $value ) ) {
        return absint( $value );
    }

    return esc_url_raw( $value );
}

/**
 * Bootstrap Gutenberg blocks (placeholder for future block registration).
 */
function lbhotel_bootstrap_blocks() {
    // Placeholder for block registration using block.json files.
}
