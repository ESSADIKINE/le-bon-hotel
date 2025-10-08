<?php
/**
 * Custom post type registration.
 *
 * @package VirtualMaroc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Hotel custom post type.
 */
function lbhotel_register_post_type() {
    $labels = array(
        'name'                  => _x( 'Virtual Places', 'Post type general name', 'lbhotel' ),
        'singular_name'         => _x( 'Virtual Place', 'Post type singular name', 'lbhotel' ),
        'menu_name'             => _x( 'Virtual Places', 'Admin Menu text', 'lbhotel' ),
        'name_admin_bar'        => _x( 'Virtual Place', 'Add New on Toolbar', 'lbhotel' ),
        'add_new'               => __( 'Add New', 'lbhotel' ),
        'add_new_item'          => __( 'Add New Virtual Place', 'lbhotel' ),
        'new_item'              => __( 'New Virtual Place', 'lbhotel' ),
        'edit_item'             => __( 'Edit Virtual Place', 'lbhotel' ),
        'view_item'             => __( 'View Virtual Place', 'lbhotel' ),
        'all_items'             => __( 'All Virtual Places', 'lbhotel' ),
        'search_items'          => __( 'Search Virtual Places', 'lbhotel' ),
        'parent_item_colon'     => __( 'Parent Virtual Places:', 'lbhotel' ),
        'not_found'             => __( 'No places found.', 'lbhotel' ),
        'not_found_in_trash'    => __( 'No places found in Trash.', 'lbhotel' ),
        'featured_image'        => __( 'Place cover image', 'lbhotel' ),
        'set_featured_image'    => __( 'Set place cover image', 'lbhotel' ),
        'remove_featured_image' => __( 'Remove place cover image', 'lbhotel' ),
        'use_featured_image'    => __( 'Use as place cover image', 'lbhotel' ),
        'archives'              => __( 'Virtual place archives', 'lbhotel' ),
        'insert_into_item'      => __( 'Insert into virtual place', 'lbhotel' ),
        'uploaded_to_this_item' => __( 'Uploaded to this place', 'lbhotel' ),
        'filter_items_list'     => __( 'Filter virtual places list', 'lbhotel' ),
        'items_list_navigation' => __( 'Virtual places list navigation', 'lbhotel' ),
        'items_list'            => __( 'Virtual places list', 'lbhotel' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'rewrite'            => array(
            'slug'       => 'places',
            'with_front' => false,
        ),
        'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
        'menu_icon'          => 'dashicons-building',
        'menu_position'      => 5,
        'capability_type'    => 'post',
        'publicly_queryable' => true,
        'show_in_menu'       => true,
    );

    register_post_type( 'lbhotel_hotel', $args );
}

/**
 * Customize single hotel permalinks to /hotel-{slug}.
 *
 * @param string  $permalink Generated permalink.
 * @param WP_Post $post      Post object.
 * @return string
 */
function lbhotel_filter_hotel_permalink( $permalink, $post ) {
    if ( $post && 'lbhotel_hotel' === $post->post_type ) {
        $category_slug = lbhotel_get_primary_category_slug( $post->ID );
        $base_slug     = lbhotel_get_category_single_rewrite_slug( $category_slug );
        $base_slug     = trim( $base_slug, '/' );

        $path = $base_slug ? $base_slug . '/' . $post->post_name : $post->post_name;

        return home_url( user_trailingslashit( $path ) );
    }

    return $permalink;
}
add_filter( 'post_type_link', 'lbhotel_filter_hotel_permalink', 10, 2 );

/**
 * Add rewrite rules for custom single permalink /hotel-{slug}.
 */
function lbhotel_add_hotel_rewrite_rules() {
    $categories = lbhotel_get_category_template_map();

    foreach ( $categories as $slug => $config ) {
        $single_slug  = trim( lbhotel_get_category_single_rewrite_slug( $slug ), '/' );
        $archive_slug = trim( lbhotel_get_category_archive_rewrite_slug( $slug ), '/' );

        if ( $single_slug ) {
            add_rewrite_rule( '^' . $single_slug . '/([^/]+)/?$', 'index.php?post_type=lbhotel_hotel&name=$matches[1]', 'top' );
        }

        if ( $archive_slug ) {
            add_rewrite_rule( '^' . $archive_slug . '/?$', 'index.php?lbhotel_place_category=' . $slug, 'top' );
            add_rewrite_rule( '^' . $archive_slug . '/page/([0-9]+)/?$', 'index.php?lbhotel_place_category=' . $slug . '&paged=$matches[1]', 'top' );
        }
    }

    // Canonical fallback: /place/{slug}
    add_rewrite_rule( '^place/([^/]+)/?$', 'index.php?post_type=lbhotel_hotel&name=$matches[1]', 'top' );
    // Backward compatibility: /hotel-{slug} (maps to name={slug})
    add_rewrite_rule( '^hotel-([^/]+)/?$', 'index.php?post_type=lbhotel_hotel&name=$matches[1]', 'top' );

    $categories = lbhotel_get_place_category_labels();

    foreach ( $categories as $slug => $label ) {
        add_rewrite_rule( '^all-' . $slug . '/?$', 'index.php?lbhotel_place_category=' . $slug, 'top' );
        add_rewrite_rule( '^all-' . $slug . '/page/([0-9]+)/?$', 'index.php?lbhotel_place_category=' . $slug . '&paged=$matches[1]', 'top' );
    }
}
add_action( 'init', 'lbhotel_add_hotel_rewrite_rules', 9 );

/**
 * Add custom columns to hotel list table.
 *
 * @param array $columns Columns.
 * @return array
 */
function lbhotel_manage_hotel_columns( $columns ) {
    $insert = array(
        'lbhotel_primary_category' => __( 'Category', 'lbhotel' ),
        'lbhotel_city'             => __( 'City', 'lbhotel' ),
    );

    $position = array_search( 'date', array_keys( $columns ), true );

    if ( false !== $position ) {
        $before  = array_slice( $columns, 0, $position, true );
        $after   = array_slice( $columns, $position, null, true );
        $columns = $before + $insert + $after;
    } else {
        $columns = array_merge( $columns, $insert );
    }

    return $columns;
}
add_filter( 'manage_lbhotel_hotel_posts_columns', 'lbhotel_manage_hotel_columns' );

/**
 * Render custom column content.
 *
 * @param string $column Column name.
 * @param int    $post_id Post ID.
 */
function lbhotel_render_hotel_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'lbhotel_primary_category':
            $terms = wp_get_post_terms( $post_id, 'lbhotel_place_category' );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                echo '&mdash;';
                break;
            }

            $names = wp_list_pluck( $terms, 'name' );
            echo esc_html( implode( ', ', $names ) );
            break;
        case 'lbhotel_city':
            $city = get_post_meta( $post_id, 'lbhotel_city', true );
            echo $city ? esc_html( $city ) : '&mdash;';
            break;
    }
}
add_action( 'manage_lbhotel_hotel_posts_custom_column', 'lbhotel_render_hotel_columns', 10, 2 );

/**
 * Register admin list table filters.
 */
function lbhotel_register_admin_filters() {
    global $typenow;

    if ( 'lbhotel_hotel' !== $typenow ) {
        return;
    }

    $selected_city      = isset( $_GET['lbhotel_city'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_city'] ) ) : '';
    $selected_category  = isset( $_GET['lbhotel_place_category'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_place_category'] ) ) : '';

    // City filter.
    echo '<label for="lbhotel-filter-city" class="screen-reader-text">' . esc_html__( 'Filter by city', 'lbhotel' ) . '</label>';
    echo '<input type="text" id="lbhotel-filter-city" name="lbhotel_city" value="' . esc_attr( $selected_city ) . '" placeholder="' . esc_attr__( 'City', 'lbhotel' ) . '" />';

    // Category filter via taxonomy.
    $terms = get_terms( array(
        'taxonomy'   => 'lbhotel_place_category',
        'hide_empty' => false,
    ) );

    echo '<label for="lbhotel-filter-category" class="screen-reader-text">' . esc_html__( 'Filter by category', 'lbhotel' ) . '</label>';
    echo '<select id="lbhotel-filter-category" name="lbhotel_place_category">';
    echo '<option value="">' . esc_html__( 'All categories', 'lbhotel' ) . '</option>';
    foreach ( $terms as $term ) {
        echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $term->slug, $selected_category, false ) . '>' . esc_html( $term->name ) . '</option>';
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'lbhotel_register_admin_filters' );

/**
 * Apply custom filters to admin query.
 *
 * @param WP_Query $query Query.
 */
function lbhotel_filter_admin_query( $query ) {
    global $pagenow;

    if ( ! is_admin() || 'edit.php' !== $pagenow || 'lbhotel_hotel' !== $query->get( 'post_type' ) ) {
        return;
    }

    $meta_query = $query->get( 'meta_query' );

    if ( ! is_array( $meta_query ) ) {
        $meta_query = array();
    }

    if ( ! empty( $_GET['lbhotel_city'] ) ) {
        $meta_query[] = array(
            'key'     => 'lbhotel_city',
            'value'   => sanitize_text_field( wp_unslash( $_GET['lbhotel_city'] ) ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }

    if ( ! empty( $_GET['lbhotel_place_category'] ) ) {
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => 'lbhotel_place_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( wp_unslash( $_GET['lbhotel_place_category'] ) ),
            ),
        ) );
    }
}
add_action( 'pre_get_posts', 'lbhotel_filter_admin_query' );
