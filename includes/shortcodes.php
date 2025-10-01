<?php
/**
 * Shortcode registration for hotel listings.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin shortcodes.
 */
function lbhotel_register_shortcodes() {
    add_shortcode( 'lbhotel_list', 'lbhotel_shortcode_list' );
    add_shortcode( 'lbhotel_single', 'lbhotel_shortcode_single' );
}

/**
 * Render hotel list shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function lbhotel_shortcode_list( $atts ) {
    $atts = shortcode_atts(
        array(
            'limit'      => 10,
            'city'       => '',
            'stars'      => '',
            'hotel_type' => '',
        ),
        $atts,
        'lbhotel_list'
    );

    if ( isset( $_GET['lbhotel_city'] ) ) {
        $atts['city'] = sanitize_text_field( wp_unslash( $_GET['lbhotel_city'] ) );
    }

    if ( isset( $_GET['lbhotel_stars'] ) ) {
        $atts['stars'] = lbhotel_sanitize_int( wp_unslash( $_GET['lbhotel_stars'] ) );
    }

    if ( isset( $_GET['lbhotel_type'] ) ) {
        $atts['hotel_type'] = sanitize_text_field( wp_unslash( $_GET['lbhotel_type'] ) );
    }

    $query_args = array(
        'post_type'      => 'lbhotel_hotel',
        'posts_per_page' => absint( $atts['limit'] ),
        'post_status'    => 'publish',
    );

    $meta_query = array();

    if ( ! empty( $atts['city'] ) ) {
        $meta_query[] = array(
            'key'     => 'lbhotel_city',
            'value'   => sanitize_text_field( $atts['city'] ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $atts['stars'] ) ) {
        $meta_query[] = array(
            'key'   => 'lbhotel_star_rating',
            'value' => lbhotel_sanitize_int( $atts['stars'] ),
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    if ( ! empty( $atts['hotel_type'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'lbhotel_hotel_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['hotel_type'] ),
            ),
        );
    }

    $hotels = new WP_Query( $query_args );

    ob_start();
    lbhotel_get_template( 'list', array( 'query' => $hotels, 'atts' => $atts ) );
    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Render single hotel shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function lbhotel_shortcode_single( $atts ) {
    $atts = shortcode_atts(
        array(
            'id' => 0,
        ),
        $atts,
        'lbhotel_single'
    );

    $post_id = absint( $atts['id'] );

    if ( ! $post_id ) {
        return '';
    }

    $post = get_post( $post_id );

    if ( ! $post || 'lbhotel_hotel' !== $post->post_type ) {
        return '';
    }

    ob_start();
    lbhotel_get_template( 'single', array( 'post' => $post ) );

    return ob_get_clean();
}

/**
 * Helper to load templates from /shortcodes directory.
 *
 * @param string $template Template slug.
 * @param array  $vars     Variables.
 */
function lbhotel_get_template( $template, $vars = array() ) {
    $file = trailingslashit( LBHOTEL_PLUGIN_DIR ) . 'shortcodes/' . $template . '.php';

    if ( ! file_exists( $file ) ) {
        return;
    }

    if ( ! empty( $vars ) ) {
        extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
    }

    include $file;
}
