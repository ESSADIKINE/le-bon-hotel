<?php
/**
 * Settings page for Le Bon Hotel.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings page and options.
 */
function lbhotel_register_settings_page() {
    static $registered = false;

    if ( $registered ) {
        return;
    }

    $registered = true;

    register_setting( 'lbhotel_settings_group', 'lbhotel_settings', 'lbhotel_sanitize_settings' );

    add_settings_section(
        'lbhotel_settings_general',
        __( 'General Hotel Defaults', 'lbhotel' ),
        'lbhotel_settings_section_callback',
        'lbhotel-settings'
    );

    add_settings_field(
        'lbhotel_default_checkin_time',
        __( 'Default check-in time', 'lbhotel' ),
        'lbhotel_field_checkin_time',
        'lbhotel-settings',
        'lbhotel_settings_general'
    );

    add_settings_field(
        'lbhotel_default_checkout_time',
        __( 'Default check-out time', 'lbhotel' ),
        'lbhotel_field_checkout_time',
        'lbhotel-settings',
        'lbhotel_settings_general'
    );

    add_settings_field(
        'lbhotel_default_currency',
        __( 'Default currency', 'lbhotel' ),
        'lbhotel_field_currency',
        'lbhotel-settings',
        'lbhotel_settings_general'
    );

    add_settings_field(
        'lbhotel_enable_booking_widget',
        __( 'Booking widget', 'lbhotel' ),
        'lbhotel_field_booking_widget',
        'lbhotel-settings',
        'lbhotel_settings_general'
    );

    add_settings_field(
        'lbhotel_default_star_rating',
        __( 'Default star rating', 'lbhotel' ),
        'lbhotel_field_star_rating',
        'lbhotel-settings',
        'lbhotel_settings_general'
    );

    add_action( 'admin_menu', 'lbhotel_add_settings_menu' );

    add_action( 'admin_post_lbhotel_migrate_restaurants', 'lbhotel_handle_migration_request' );
}

/**
 * Add settings page to the admin menu.
 */
function lbhotel_add_settings_menu() {
    add_submenu_page(
        'edit.php?post_type=lbhotel_hotel',
        __( 'Hotel Settings', 'lbhotel' ),
        __( 'Settings', 'lbhotel' ),
        'manage_options',
        'lbhotel-settings',
        'lbhotel_render_settings_page'
    );
}

/**
 * Section intro text.
 */
function lbhotel_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure hotel defaults used across listings, booking widgets, and API responses.', 'lbhotel' ) . '</p>';
}

/**
 * Render check-in field.
 */
function lbhotel_field_checkin_time() {
    $value = lbhotel_get_option( 'default_checkin_time' );
    echo '<input type="time" id="lbhotel_default_checkin_time" name="lbhotel_settings[default_checkin_time]" value="' . esc_attr( $value ) . '" />';
}

/**
 * Render check-out field.
 */
function lbhotel_field_checkout_time() {
    $value = lbhotel_get_option( 'default_checkout_time' );
    echo '<input type="time" id="lbhotel_default_checkout_time" name="lbhotel_settings[default_checkout_time]" value="' . esc_attr( $value ) . '" />';
}

/**
 * Render currency field.
 */
function lbhotel_field_currency() {
    $value = lbhotel_get_option( 'default_currency' );
    echo '<input type="text" id="lbhotel_default_currency" name="lbhotel_settings[default_currency]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__( 'Use ISO currency code such as MAD, EUR, USD.', 'lbhotel' ) . '</p>';
}

/**
 * Render booking widget toggle.
 */
function lbhotel_field_booking_widget() {
    $value = lbhotel_get_option( 'enable_booking_widget' );
    echo '<label><input type="checkbox" name="lbhotel_settings[enable_booking_widget]" value="1" ' . checked( true, $value, false ) . ' /> ' . esc_html__( 'Enable booking widget by default', 'lbhotel' ) . '</label>';
}

/**
 * Render default star rating field.
 */
function lbhotel_field_star_rating() {
    $value = lbhotel_get_option( 'default_star_rating' );
    echo '<select name="lbhotel_settings[default_star_rating]" id="lbhotel_default_star_rating">';
    for ( $i = 1; $i <= 5; $i++ ) {
        echo '<option value="' . esc_attr( $i ) . '" ' . selected( $value, $i, false ) . '>' . esc_html( $i ) . '</option>';
    }
    echo '</select>';
}

/**
 * Sanitize settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function lbhotel_sanitize_settings( $input ) {
    $defaults = lbhotel_get_default_settings();
    $sanitized = array();

    $sanitized['default_checkin_time']  = isset( $input['default_checkin_time'] ) ? lbhotel_sanitize_time( $input['default_checkin_time'] ) : $defaults['default_checkin_time'];
    $sanitized['default_checkout_time'] = isset( $input['default_checkout_time'] ) ? lbhotel_sanitize_time( $input['default_checkout_time'] ) : $defaults['default_checkout_time'];
    $sanitized['default_currency']      = isset( $input['default_currency'] ) ? sanitize_text_field( $input['default_currency'] ) : $defaults['default_currency'];
    $sanitized['enable_booking_widget'] = isset( $input['enable_booking_widget'] );
    $sanitized['default_star_rating']   = isset( $input['default_star_rating'] ) ? min( 5, max( 1, (int) $input['default_star_rating'] ) ) : $defaults['default_star_rating'];

    return $sanitized;
}

/**
 * Render settings page.
 */
function lbhotel_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Le Bon Hotel Settings', 'lbhotel' ) . '</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields( 'lbhotel_settings_group' );
    do_settings_sections( 'lbhotel-settings' );
    submit_button();
    echo '</form>';

    echo '<hr />';
    echo '<h2>' . esc_html__( 'Migrate existing restaurants', 'lbhotel' ) . '</h2>';

    if ( post_type_exists( 'restaurant' ) ) {
        echo '<p>' . esc_html__( 'Detected legacy restaurant listings. Migrate them to hotels to keep your data.', 'lbhotel' ) . '</p>';
        echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
        wp_nonce_field( 'lbhotel_migrate_restaurants' );
        echo '<input type="hidden" name="action" value="lbhotel_migrate_restaurants" />';
        submit_button( __( 'Run migration', 'lbhotel' ), 'secondary' );
        echo '</form>';
    } else {
        echo '<p>' . esc_html__( 'No legacy restaurant data detected. You are all set!', 'lbhotel' ) . '</p>';
    }

    echo '</div>';
}

/**
 * Handle migration request from admin.
 */
function lbhotel_handle_migration_request() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to run migrations.', 'lbhotel' ) );
    }

    check_admin_referer( 'lbhotel_migrate_restaurants' );

    $migrated = lbhotel_migrate_from_restaurant();

    if ( is_wp_error( $migrated ) ) {
        wp_safe_redirect( add_query_arg( array( 'post_type' => 'lbhotel_hotel', 'page' => 'lbhotel-settings', 'lbhotel_migrated' => '0', 'lbhotel_error' => rawurlencode( $migrated->get_error_message() ) ), admin_url( 'edit.php' ) ) );
        exit;
    }

    wp_safe_redirect( add_query_arg( array( 'post_type' => 'lbhotel_hotel', 'page' => 'lbhotel-settings', 'lbhotel_migrated' => '1', 'lbhotel_count' => (int) $migrated ), admin_url( 'edit.php' ) ) );
    exit;
}
