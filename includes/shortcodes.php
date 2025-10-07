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

    // Ensure front-end assets are available when rendering the shortcode outside plugin templates.
    if ( wp_style_is( 'lbhotel-public', 'registered' ) ) {
        wp_enqueue_style( 'lbhotel-public' );
    }

    if ( wp_script_is( 'lbhotel-public', 'registered' ) ) {
        wp_enqueue_script( 'lbhotel-public' );
    }

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
            'key'     => 'vm_city',
            'value'   => sanitize_text_field( $atts['city'] ),
            'compare' => 'LIKE',
        );
    }

    if ( '' !== $atts['stars'] && null !== $atts['stars'] ) {
        $meta_query[] = array(
            'key'   => 'vm_rating',
            'value' => lbhotel_sanitize_decimal( $atts['stars'] ),
            'compare' => '>=',
            'type'  => 'NUMERIC',
        );
    }

    if ( ! empty( $atts['hotel_type'] ) ) {
        $meta_query[] = array(
            'key'   => 'vm_hotel_type',
            'value' => sanitize_text_field( $atts['hotel_type'] ),
        );
    }

    if ( ! empty( $meta_query ) ) {
        if ( count( $meta_query ) > 1 ) {
            $meta_query['relation'] = 'AND';
        }
        $query_args['meta_query'] = $meta_query;
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

    if ( wp_style_is( 'lbhotel-public', 'registered' ) ) {
        wp_enqueue_style( 'lbhotel-public' );
    }

    if ( wp_script_is( 'lbhotel-public', 'registered' ) ) {
        wp_enqueue_script( 'lbhotel-public' );
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
