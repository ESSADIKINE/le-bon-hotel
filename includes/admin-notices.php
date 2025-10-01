<?php
/**
 * Admin notice helpers.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Setup admin notices.
 */
function lbhotel_setup_admin_notices() {
    add_action( 'admin_notices', 'lbhotel_settings_notices' );
}

/**
 * Output notices for settings actions.
 */
function lbhotel_settings_notices() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['lbhotel_migrated'] ) ) {
        $count = isset( $_GET['lbhotel_count'] ) ? absint( $_GET['lbhotel_count'] ) : 0;
        $type  = 'notice-success';
        $message = $count ? sprintf( _n( 'Migrated %d restaurant into a hotel.', 'Migrated %d restaurants into hotels.', $count, 'lbhotel' ), $count ) : __( 'Migration completed but no restaurants were found.', 'lbhotel' );

        if ( '0' === $_GET['lbhotel_migrated'] && isset( $_GET['lbhotel_error'] ) ) {
            $type    = 'notice-error';
            $message = sanitize_text_field( wp_unslash( $_GET['lbhotel_error'] ) );
        }

        printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $message ) );
    }
}

/**
 * Render migration reminder notice.
 */
function lbhotel_render_migration_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $url = add_query_arg(
        array(
            'post_type' => 'lbhotel_hotel',
            'page'      => 'lbhotel-settings',
        ),
        admin_url( 'edit.php' )
    );

    echo '<div class="notice notice-warning"><p>' . wp_kses_post( sprintf( __( 'Legacy restaurant listings detected. <a href="%s">Run the migration</a> to convert them into hotels.', 'lbhotel' ), esc_url( $url ) ) ) . '</p></div>';
}
