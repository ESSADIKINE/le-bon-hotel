<?php
/**
 * Plugin Name:       Le Bon Hotel
 * Plugin URI:        https://example.com/le-bon-hotel
 * Description:       Complete hotel directory toolkit for managing listings, booking information, and showcasing hotels on the front end.
 * Version:           1.0.0
 * Author:            Le Bon Plugins
 * Author URI:        https://example.com
 * Text Domain:       lbhotel
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LBHOTEL_VERSION', '1.0.0' );
define( 'LBHOTEL_PLUGIN_FILE', __FILE__ );
define( 'LBHOTEL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LBHOTEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LBHOTEL_PLUGIN_DIR . 'includes/helpers.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/post-types.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/taxonomies.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/admin-meta.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/settings.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/rest-api.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/shortcodes.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/assets.php';
require_once LBHOTEL_PLUGIN_DIR . 'includes/admin-notices.php';
require_once LBHOTEL_PLUGIN_DIR . 'migrations/migrate-restaurants.php';

/**
 * Load plugin text domain.
 */
function lbhotel_load_textdomain() {
    load_plugin_textdomain( 'lbhotel', false, dirname( plugin_basename( LBHOTEL_PLUGIN_FILE ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'lbhotel_load_textdomain' );

/**
 * Plugin activation callback.
 */
function lbhotel_activate() {
    lbhotel_register_post_type();
    lbhotel_register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook( LBHOTEL_PLUGIN_FILE, 'lbhotel_activate' );

/**
 * Plugin deactivation callback.
 */
function lbhotel_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( LBHOTEL_PLUGIN_FILE, 'lbhotel_deactivate' );

/**
 * Initialize components.
 */
function lbhotel_init() {
    lbhotel_register_post_type();
    lbhotel_register_taxonomies();
    lbhotel_register_meta_fields();
    lbhotel_register_shortcodes();
    lbhotel_register_assets();
    lbhotel_register_rest_routes();
}
add_action( 'init', 'lbhotel_init' );

/**
 * Admin specific bootstrapping.
 */
function lbhotel_admin_init() {
    lbhotel_register_settings_page();
    lbhotel_setup_admin_meta_boxes();
    lbhotel_setup_admin_notices();
}
add_action( 'admin_init', 'lbhotel_admin_init' );

/**
 * Register blocks or additional features after init.
 */
function lbhotel_init_late() {
    lbhotel_bootstrap_blocks();
}
add_action( 'init', 'lbhotel_init_late', 20 );

/**
 * Detect legacy restaurant post type and provide migration prompt.
 */
function lbhotel_check_for_restaurant_cpt() {
    if ( post_type_exists( 'restaurant' ) && ! get_option( 'lbhotel_restaurant_migrated' ) ) {
        add_action( 'admin_notices', 'lbhotel_render_migration_notice' );
    }
}
add_action( 'admin_init', 'lbhotel_check_for_restaurant_cpt', 5 );

if ( ! function_exists( 'lbhotel_enqueue_theme_stylesheet' ) ) {
    /**
     * Enqueue the theme-level hotel stylesheet when present.
     */
    function lbhotel_enqueue_theme_stylesheet() {
        if ( ! function_exists( 'get_stylesheet_directory' ) || ! function_exists( 'get_stylesheet_directory_uri' ) ) {
            return;
        }

        $stylesheet_dir = trailingslashit( get_stylesheet_directory() );
        $stylesheet_uri = trailingslashit( get_stylesheet_directory_uri() );
        $style_path     = $stylesheet_dir . 'hotel-styles.css';

        if ( file_exists( $style_path ) ) {
            $version = filemtime( $style_path );

            wp_enqueue_style( 'lbhotel-theme-hotel', $stylesheet_uri . 'hotel-styles.css', array(), $version );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'lbhotel_enqueue_theme_stylesheet' );
