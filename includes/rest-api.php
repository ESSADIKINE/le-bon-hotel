<?php
/**
 * REST API endpoints for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST routes.
 */
function lbhotel_register_rest_routes() {
    add_action( 'rest_api_init', function () {
        register_rest_route(
            'virtualmaroc/v1',
            '/places',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'lbhotel_rest_get_places',
                'permission_callback' => '__return_true',
                'args'                => array(
                    'city'     => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'category' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'search'   => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'page'     => array(
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            'virtualmaroc/v1',
            '/places/(?P<id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'lbhotel_rest_get_place',
                'permission_callback' => '__return_true',
                'args'                => array(
                    'context' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );
    } );
}

/**
 * Get a paginated collection of virtual places.
 *
 * @param WP_REST_Request $request Request instance.
 * @return WP_REST_Response
 */
function lbhotel_rest_get_places( WP_REST_Request $request ) {
    $args = array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'posts_per_page' => $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 10,
    );

    if ( $request->get_param( 'page' ) ) {
        $args['paged'] = max( 1, absint( $request->get_param( 'page' ) ) );
    }

    $meta_query = array();

    if ( $request->get_param( 'city' ) ) {
        $meta_query[] = array(
            'key'     => 'lbhotel_city',
            'value'   => sanitize_text_field( $request->get_param( 'city' ) ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    if ( $request->get_param( 'category' ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'lbhotel_place_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $request->get_param( 'category' ) ),
            ),
        );
    }

    if ( $request->get_param( 'search' ) ) {
        $args['s'] = sanitize_text_field( $request->get_param( 'search' ) );
    }

    $query  = new WP_Query( $args );
    $places = array();

    foreach ( $query->posts as $post ) {
        $places[] = lbhotel_prepare_place_for_response( $post, $request );
    }

    $response = rest_ensure_response( $places );
    $response->header( 'X-WP-Total', (int) $query->found_posts );
    $response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

    return $response;
}

/**
 * Get a single virtual place.
 *
 * @param WP_REST_Request $request Request instance.
 * @return WP_REST_Response|WP_Error
 */
function lbhotel_rest_get_place( WP_REST_Request $request ) {
    $post = get_post( (int) $request['id'] );

    if ( ! $post || 'lbhotel_hotel' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_Error( 'lbhotel_not_found', __( 'Place not found.', 'lbhotel' ), array( 'status' => 404 ) );
    }

    return rest_ensure_response( lbhotel_prepare_place_for_response( $post, $request ) );
}

/**
 * Prepare a single place payload for the REST API.
 *
 * @param WP_Post         $post    Post object.
 * @param WP_REST_Request $request Request instance.
 * @return array
 */
function lbhotel_prepare_place_for_response( $post, WP_REST_Request $request ) {
    $definitions = lbhotel_get_all_field_definitions();
    $meta        = array();

    foreach ( $definitions as $meta_key => $definition ) {
        $value    = get_post_meta( $post->ID, $meta_key, true );
        $type     = isset( $definition['type'] ) ? $definition['type'] : 'string';
        $input    = isset( $definition['input'] ) ? $definition['input'] : 'text';
        $rest_key = lbhotel_rest_format_meta_key( $meta_key );

        if ( 'gallery' === $input ) {
            $images  = lbhotel_sanitize_gallery_images( $value );
            $payload = array();

            foreach ( $images as $image_id ) {
                $url = wp_get_attachment_image_url( $image_id, 'large' );

                if ( $url ) {
                    $payload[] = array(
                        'id'  => $image_id,
                        'url' => $url,
                    );
                }
            }

            $meta[ $rest_key ] = $payload;
            continue;
        }

        if ( '' === $value || null === $value ) {
            $meta[ $rest_key ] = '';
            continue;
        }

        if ( 'number' === $type ) {
            $meta[ $rest_key ] = (float) $value;
        } elseif ( 'integer' === $type ) {
            $meta[ $rest_key ] = (int) $value;
        } else {
            $meta[ $rest_key ] = $value;
        }
    }

    $terms = wp_get_post_terms( $post->ID, 'lbhotel_place_category' );
    $categories = array();

    foreach ( $terms as $term ) {
        $categories[] = array(
            'id'   => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );
    }

    $data = array(
        'id'         => $post->ID,
        'title'      => get_the_title( $post ),
        'permalink'  => get_permalink( $post ),
        'excerpt'    => wp_trim_words( $post->post_content, 30 ),
        'thumbnail'  => get_the_post_thumbnail_url( $post, 'large' ),
        'categories' => $categories,
        'meta'       => $meta,
    );

    if ( has_post_thumbnail( $post ) ) {
        $data['featured_image'] = array(
            'id'  => get_post_thumbnail_id( $post ),
            'url' => get_the_post_thumbnail_url( $post, 'large' ),
        );
    }

    return $data;
}

/**
 * Normalize a meta key for REST output.
 *
 * @param string $meta_key Raw meta key.
 * @return string
 */
function lbhotel_rest_format_meta_key( $meta_key ) {
    $meta_key = preg_replace( '/^lbhotel_/', '', $meta_key );

    return $meta_key;
}
