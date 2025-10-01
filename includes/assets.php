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

    wp_register_style( 'lbhotel-admin', LBHOTEL_PLUGIN_URL . 'admin/css/lbhotel-admin.css', array(), LBHOTEL_VERSION );
    wp_register_script( 'lbhotel-admin', LBHOTEL_PLUGIN_URL . 'admin/js/lbhotel-admin.js', array( 'jquery' ), LBHOTEL_VERSION, true );

    add_action( 'wp_enqueue_scripts', 'lbhotel_enqueue_public_assets' );
    add_action( 'admin_enqueue_scripts', 'lbhotel_enqueue_admin_assets' );
}

/**
 * Enqueue public assets.
 */
function lbhotel_enqueue_public_assets() {
    $post     = get_post();
    $content  = $post ? $post->post_content : '';

    if ( is_singular( 'lbhotel_hotel' ) || ( $content && ( has_shortcode( $content, 'lbhotel_list' ) || has_shortcode( $content, 'lbhotel_single' ) ) ) ) {
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

        wp_localize_script(
            'lbhotel-admin',
            'lbHotelRooms',
            array(
                'nonce' => wp_create_nonce( 'lbhotel_rooms_nonce' ),
            )
        );
    }
}
