<?php
/**
 * Taxonomy registration for Le Bon Hotel.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register hotel taxonomies.
 */
function lbhotel_register_taxonomies() {
    $hotel_type_labels = array(
        'name'              => _x( 'Hotel Types', 'taxonomy general name', 'lbhotel' ),
        'singular_name'     => _x( 'Hotel Type', 'taxonomy singular name', 'lbhotel' ),
        'search_items'      => __( 'Search Hotel Types', 'lbhotel' ),
        'all_items'         => __( 'All Hotel Types', 'lbhotel' ),
        'parent_item'       => __( 'Parent Hotel Type', 'lbhotel' ),
        'parent_item_colon' => __( 'Parent Hotel Type:', 'lbhotel' ),
        'edit_item'         => __( 'Edit Hotel Type', 'lbhotel' ),
        'update_item'       => __( 'Update Hotel Type', 'lbhotel' ),
        'add_new_item'      => __( 'Add New Hotel Type', 'lbhotel' ),
        'new_item_name'     => __( 'New Hotel Type Name', 'lbhotel' ),
        'menu_name'         => __( 'Hotel Types', 'lbhotel' ),
    );

    register_taxonomy(
        'lbhotel_hotel_type',
        array( 'lbhotel_hotel' ),
        array(
            'hierarchical'      => true,
            'labels'            => $hotel_type_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'hotel-type' ),
        )
    );

    $location_labels = array(
        'name'              => _x( 'Hotel Locations', 'taxonomy general name', 'lbhotel' ),
        'singular_name'     => _x( 'Hotel Location', 'taxonomy singular name', 'lbhotel' ),
        'search_items'      => __( 'Search Locations', 'lbhotel' ),
        'all_items'         => __( 'All Locations', 'lbhotel' ),
        'parent_item'       => __( 'Parent Location', 'lbhotel' ),
        'parent_item_colon' => __( 'Parent Location:', 'lbhotel' ),
        'edit_item'         => __( 'Edit Location', 'lbhotel' ),
        'update_item'       => __( 'Update Location', 'lbhotel' ),
        'add_new_item'      => __( 'Add New Location', 'lbhotel' ),
        'new_item_name'     => __( 'New Location Name', 'lbhotel' ),
        'menu_name'         => __( 'Hotel Locations', 'lbhotel' ),
    );

    register_taxonomy(
        'lbhotel_location',
        array( 'lbhotel_hotel' ),
        array(
            'hierarchical'      => true,
            'labels'            => $location_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'hotel-location' ),
        )
    );

    lbhotel_ensure_default_hotel_types();
}

/**
 * Ensure default hotel types exist.
 */
function lbhotel_ensure_default_hotel_types() {
    $defaults = array( 'Boutique', 'Business', 'Resort', 'Hostel', 'Apartment' );

    foreach ( $defaults as $type ) {
        if ( ! term_exists( $type, 'lbhotel_hotel_type' ) ) {
            wp_insert_term( $type, 'lbhotel_hotel_type' );
        }
    }
}
