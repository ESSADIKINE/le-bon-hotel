<?php
/**
 * Custom post type registration.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Hotel custom post type.
 */
function lbhotel_register_post_type() {
    $labels = array(
        'name'                  => _x( 'Hotels', 'Post type general name', 'lbhotel' ),
        'singular_name'         => _x( 'Hotel', 'Post type singular name', 'lbhotel' ),
        'menu_name'             => _x( 'Hotels', 'Admin Menu text', 'lbhotel' ),
        'name_admin_bar'        => _x( 'Hotel', 'Add New on Toolbar', 'lbhotel' ),
        'add_new'               => __( 'Add New', 'lbhotel' ),
        'add_new_item'          => __( 'Add New Hotel', 'lbhotel' ),
        'new_item'              => __( 'New Hotel', 'lbhotel' ),
        'edit_item'             => __( 'Edit Hotel', 'lbhotel' ),
        'view_item'             => __( 'View Hotel', 'lbhotel' ),
        'all_items'             => __( 'All Hotels', 'lbhotel' ),
        'search_items'          => __( 'Search Hotels', 'lbhotel' ),
        'parent_item_colon'     => __( 'Parent Hotels:', 'lbhotel' ),
        'not_found'             => __( 'No hotels found.', 'lbhotel' ),
        'not_found_in_trash'    => __( 'No hotels found in Trash.', 'lbhotel' ),
        'featured_image'        => __( 'Hotel cover image', 'lbhotel' ),
        'set_featured_image'    => __( 'Set hotel cover image', 'lbhotel' ),
        'remove_featured_image' => __( 'Remove hotel cover image', 'lbhotel' ),
        'use_featured_image'    => __( 'Use as hotel cover image', 'lbhotel' ),
        'archives'              => __( 'Hotel archives', 'lbhotel' ),
        'insert_into_item'      => __( 'Insert into hotel', 'lbhotel' ),
        'uploaded_to_this_item' => __( 'Uploaded to this hotel', 'lbhotel' ),
        'filter_items_list'     => __( 'Filter hotels list', 'lbhotel' ),
        'items_list_navigation' => __( 'Hotels list navigation', 'lbhotel' ),
        'items_list'            => __( 'Hotels list', 'lbhotel' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'rewrite'            => array(
            'slug'       => 'all',
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
 * Add custom columns to hotel list table.
 *
 * @param array $columns Columns.
 * @return array
 */
function lbhotel_manage_hotel_columns( $columns ) {
    $insert = array(
        'lbhotel_star_rating' => __( 'Stars', 'lbhotel' ),
        'lbhotel_city'        => __( 'City', 'lbhotel' ),
        'lbhotel_rooms_total' => __( 'Rooms', 'lbhotel' ),
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
        case 'lbhotel_star_rating':
            $rating = get_post_meta( $post_id, 'lbhotel_star_rating', true );
            echo $rating ? esc_html( $rating ) . '★' : '&mdash;';
            break;
        case 'lbhotel_city':
            $city = get_post_meta( $post_id, 'lbhotel_city', true );
            echo $city ? esc_html( $city ) : '&mdash;';
            break;
        case 'lbhotel_rooms_total':
            $rooms = get_post_meta( $post_id, 'lbhotel_rooms_total', true );
            echo $rooms ? esc_html( $rooms ) : '&mdash;';
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

    $selected_city = isset( $_GET['lbhotel_city'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_city'] ) ) : '';
    $selected_star = isset( $_GET['lbhotel_star_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_star_rating'] ) ) : '';
    $selected_type = isset( $_GET['lbhotel_hotel_type'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_hotel_type'] ) ) : '';

    // City filter.
    echo '<label for="lbhotel-filter-city" class="screen-reader-text">' . esc_html__( 'Filter by city', 'lbhotel' ) . '</label>';
    echo '<input type="text" id="lbhotel-filter-city" name="lbhotel_city" value="' . esc_attr( $selected_city ) . '" placeholder="' . esc_attr__( 'City', 'lbhotel' ) . '" />';

    // Star rating filter.
    echo '<label for="lbhotel-filter-star" class="screen-reader-text">' . esc_html__( 'Filter by star rating', 'lbhotel' ) . '</label>';
    echo '<select id="lbhotel-filter-star" name="lbhotel_star_rating">';
    echo '<option value="">' . esc_html__( 'All star ratings', 'lbhotel' ) . '</option>';
    for ( $i = 1; $i <= 5; $i++ ) {
        echo '<option value="' . esc_attr( $i ) . '" ' . selected( (string) $i, $selected_star, false ) . '>' . esc_html( sprintf( _n( '%d star', '%d stars', $i, 'lbhotel' ), $i ) ) . '</option>';
    }
    echo '</select>';

    // Hotel type filter via taxonomy.
    $terms = get_terms( array(
        'taxonomy'   => 'lbhotel_hotel_type',
        'hide_empty' => false,
    ) );
    echo '<label for="lbhotel-filter-type" class="screen-reader-text">' . esc_html__( 'Filter by hotel type', 'lbhotel' ) . '</label>';
    echo '<select id="lbhotel-filter-type" name="lbhotel_hotel_type">';
    echo '<option value="">' . esc_html__( 'All hotel types', 'lbhotel' ) . '</option>';
    foreach ( $terms as $term ) {
        echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $term->slug, $selected_type, false ) . '>' . esc_html( $term->name ) . '</option>';
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

    if ( ! empty( $_GET['lbhotel_star_rating'] ) ) {
        $meta_query[] = array(
            'key'   => 'lbhotel_star_rating',
            'value' => lbhotel_sanitize_int( $_GET['lbhotel_star_rating'] ),
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }

    if ( ! empty( $_GET['lbhotel_hotel_type'] ) ) {
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => 'lbhotel_hotel_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( wp_unslash( $_GET['lbhotel_hotel_type'] ) ),
            ),
        ) );
    }
}
add_action( 'pre_get_posts', 'lbhotel_filter_admin_query' );
