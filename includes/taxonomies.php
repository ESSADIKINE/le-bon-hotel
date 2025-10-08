<?php
/**
 * Taxonomy registration for Virtual Maroc.
 *
 * @package VirtualMaroc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register hotel taxonomies.
 */
function lbhotel_register_taxonomies() {
    $category_labels = array(
        'name'              => _x( 'Virtual Place Categories', 'taxonomy general name', 'lbhotel' ),
        'singular_name'     => _x( 'Virtual Place Category', 'taxonomy singular name', 'lbhotel' ),
        'search_items'      => __( 'Search Categories', 'lbhotel' ),
        'all_items'         => __( 'All Categories', 'lbhotel' ),
        'parent_item'       => __( 'Parent Category', 'lbhotel' ),
        'parent_item_colon' => __( 'Parent Category:', 'lbhotel' ),
        'edit_item'         => __( 'Edit Category', 'lbhotel' ),
        'update_item'       => __( 'Update Category', 'lbhotel' ),
        'add_new_item'      => __( 'Add New Category', 'lbhotel' ),
        'new_item_name'     => __( 'New Category Name', 'lbhotel' ),
        'menu_name'         => __( 'Categories', 'lbhotel' ),
    );

    register_taxonomy(
        'lbhotel_place_category',
        array( 'lbhotel_hotel' ),
        array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'virtual-place-category' ),
        )
    );

    lbhotel_ensure_default_place_categories();
}

/**
 * Ensure default place categories exist.
 */
function lbhotel_ensure_default_place_categories() {
    $categories = lbhotel_get_place_category_labels();

    foreach ( $categories as $slug => $label ) {
        if ( ! term_exists( $slug, 'lbhotel_place_category' ) ) {
            wp_insert_term( $label, 'lbhotel_place_category', array( 'slug' => $slug ) );
        }
    }
}
