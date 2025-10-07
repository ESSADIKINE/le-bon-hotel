<?php
/**
 * Asset registration and enqueueing.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin assets.
 */
function lbhotel_register_assets() {
    wp_register_style( 'lbhotel-public', LBHOTEL_PLUGIN_URL . 'public/css/lbhotel.css', array(), LBHOTEL_VERSION );
    wp_register_script( 'lbhotel-public', LBHOTEL_PLUGIN_URL . 'public/js/lbhotel.js', array( 'jquery' ), LBHOTEL_VERSION, true );
    wp_register_script( 'lbhotel-single-base', LBHOTEL_PLUGIN_URL . 'single-hotel.js', array(), LBHOTEL_VERSION, true );
    wp_register_script( 'lbhotel-archive-base', LBHOTEL_PLUGIN_URL . 'all-hotel.js', array(), LBHOTEL_VERSION, true );

    wp_register_style( 'lbhotel-admin', LBHOTEL_PLUGIN_URL . 'admin/css/lbhotel-admin.css', array(), LBHOTEL_VERSION );
    wp_register_script( 'lbhotel-admin', LBHOTEL_PLUGIN_URL . 'admin/js/lbhotel-admin.js', array( 'jquery' ), LBHOTEL_VERSION, true );

    add_action( 'wp_enqueue_scripts', 'lbhotel_enqueue_public_assets' );
    add_action( 'admin_enqueue_scripts', 'lbhotel_enqueue_admin_assets' );
}

/**
 * Enqueue public assets.
 */
function lbhotel_enqueue_public_assets() {
    if ( is_singular( 'lbhotel_hotel' ) || is_post_type_archive( 'lbhotel_hotel' ) || is_tax( 'lbhotel_place_category' ) ) {
        wp_enqueue_style( 'lbhotel-public' );
        wp_enqueue_script( 'lbhotel-public' );

        wp_localize_script(
            'lbhotel-public',
            'lbhotelPublic',
            array(
                'i18n' => array(
                    'bookNow'  => __( 'Book', 'lbhotel' ),
                    'viewRooms'=> __( 'View rooms', 'lbhotel' ),
                ),
            )
        );

        $context = lbhotel_get_request_template_context();

        if ( $context ) {
            $category_slug = $context['category'];
            $type          = $context['type'];
            $handle_suffix = sanitize_key( $category_slug );
            $style_handle  = 'lbhotel-' . $type . '-' . $handle_suffix;

            $style_url = lbhotel_get_category_template_url( $category_slug, $type, 'style' );
            if ( $style_url ) {
                wp_enqueue_style( $style_handle, $style_url, array( 'lbhotel-public' ), LBHOTEL_VERSION );
            }

            $script_url   = lbhotel_get_category_template_url( $category_slug, $type, 'script' );
            $base_handle  = 'single' === $type ? 'lbhotel-single-base' : 'lbhotel-archive-base';
            $script_deps  = array();

            if ( wp_script_is( $base_handle, 'registered' ) ) {
                $script_deps[] = $base_handle;
                wp_enqueue_script( $base_handle );
            }

            if ( $script_url ) {
                $script_handle = 'lbhotel-' . $type . '-' . $handle_suffix;
                wp_enqueue_script( $script_handle, $script_url, $script_deps, LBHOTEL_VERSION, true );
            }
        }
    }
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook Hook suffix.
 */
function lbhotel_enqueue_admin_assets( $hook ) {
    global $typenow;

    if ( in_array( $hook, array( 'post-new.php', 'post.php' ), true ) && 'lbhotel_hotel' === $typenow ) {
        wp_enqueue_style( 'lbhotel-admin' );
        wp_enqueue_script( 'lbhotel-admin' );

        wp_enqueue_media();

    }

    if ( 'lbhotel_hotel_page_places-import-export' === $hook ) {
        wp_enqueue_style( 'lbhotel-admin' );
    }
}
