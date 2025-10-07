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
 * Ensure plugin templates are used for hotel archive and single views.
 *
 * @param string $template Current template path.
 * @return string
 */
function lbhotel_template_include( $template ) {
    if ( is_post_type_archive( 'lbhotel_hotel' ) ) {
        $plugin_template = trailingslashit( LBHOTEL_PLUGIN_DIR ) . 'archive-lbhotel_hotel.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    if ( is_singular( 'lbhotel_hotel' ) ) {
        $plugin_template = trailingslashit( LBHOTEL_PLUGIN_DIR ) . 'single-lbhotel_hotel.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'lbhotel_template_include', 20 );
