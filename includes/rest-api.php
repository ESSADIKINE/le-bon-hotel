<?php
/**
 * REST API endpoints for Le Bon Hotel.
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
            'lbhotel/v1',
            '/hotels',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'lbhotel_rest_get_hotels',
                'permission_callback' => '__return_true',
                'args'                => array(
                    'city'       => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'stars'      => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'hotel_type' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page'   => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'page'       => array(
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            'lbhotel/v1',
            '/hotels/(?P<id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'lbhotel_rest_get_hotel',
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
 * Get hotels collection.
 *
 * @param WP_REST_Request $request Request instance.
 * @return WP_REST_Response
 */
function lbhotel_rest_get_hotels( WP_REST_Request $request ) {
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

    if ( $request->get_param( 'stars' ) ) {
        $meta_query[] = array(
            'key'   => 'lbhotel_star_rating',
            'value' => absint( $request->get_param( 'stars' ) ),
        );
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    if ( $request->get_param( 'hotel_type' ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'lbhotel_hotel_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $request->get_param( 'hotel_type' ) ),
            ),
        );
    }

    $query = new WP_Query( $args );
    $hotels = array();

    foreach ( $query->posts as $post ) {
        $hotels[] = lbhotel_prepare_hotel_for_response( $post, $request );
    }

    $response = rest_ensure_response( $hotels );
    $response->header( 'X-WP-Total', (int) $query->found_posts );
    $response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

    return $response;
}

/**
 * Get single hotel.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function lbhotel_rest_get_hotel( WP_REST_Request $request ) {
    $post = get_post( (int) $request['id'] );

    if ( ! $post || 'lbhotel_hotel' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_Error( 'lbhotel_not_found', __( 'Hotel not found.', 'lbhotel' ), array( 'status' => 404 ) );
    }

    return rest_ensure_response( lbhotel_prepare_hotel_for_response( $post, $request ) );
}

/**
 * Prepare hotel for response.
 *
 * @param WP_Post         $post    Post object.
 * @param WP_REST_Request $request Request.
 * @return array
 */
function lbhotel_prepare_hotel_for_response( $post, WP_REST_Request $request ) {
    $meta = array(
        'address'             => get_post_meta( $post->ID, 'lbhotel_address', true ),
        'city'                => get_post_meta( $post->ID, 'lbhotel_city', true ),
        'region'              => get_post_meta( $post->ID, 'lbhotel_region', true ),
        'postal_code'         => get_post_meta( $post->ID, 'lbhotel_postal_code', true ),
        'country'             => get_post_meta( $post->ID, 'lbhotel_country', true ),
        'checkin_time'        => get_post_meta( $post->ID, 'lbhotel_checkin_time', true ),
        'checkout_time'       => get_post_meta( $post->ID, 'lbhotel_checkout_time', true ),
        'rooms_total'         => (int) get_post_meta( $post->ID, 'lbhotel_rooms_total', true ),
        'avg_price_per_night' => get_post_meta( $post->ID, 'lbhotel_avg_price_per_night', true ),
        'has_free_breakfast'  => (bool) get_post_meta( $post->ID, 'lbhotel_has_free_breakfast', true ),
        'has_parking'         => (bool) get_post_meta( $post->ID, 'lbhotel_has_parking', true ),
        'star_rating'         => (int) get_post_meta( $post->ID, 'lbhotel_star_rating', true ),
        'gallery_images'      => get_post_meta( $post->ID, 'lbhotel_gallery_images', true ),
        'virtual_tour_url'    => get_post_meta( $post->ID, 'lbhotel_virtual_tour_url', true ),
        'contact_phone'       => get_post_meta( $post->ID, 'lbhotel_contact_phone', true ),
        'booking_url'         => get_post_meta( $post->ID, 'lbhotel_booking_url', true ),
        'rooms'               => get_post_meta( $post->ID, 'lbhotel_rooms', true ),
    );

    $terms = wp_get_post_terms( $post->ID, array( 'lbhotel_hotel_type', 'lbhotel_location' ), array( 'fields' => 'all_with_object_id' ) );

    $hotel_types = array();
    $locations   = array();

    foreach ( $terms as $term ) {
        if ( 'lbhotel_hotel_type' === $term->taxonomy ) {
            $hotel_types[] = array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        } elseif ( 'lbhotel_location' === $term->taxonomy ) {
            $locations[] = array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }
    }

    $data = array(
        'id'          => $post->ID,
        'title'       => get_the_title( $post ),
        'permalink'   => get_permalink( $post ),
        'excerpt'     => wp_trim_words( $post->post_content, 30 ),
        'thumbnail'   => get_the_post_thumbnail_url( $post, 'large' ),
        'star_rating' => $meta['star_rating'],
        'meta'        => $meta,
        'hotel_types' => $hotel_types,
        'locations'   => $locations,
        'currency'    => lbhotel_get_option( 'default_currency' ),
    );

    if ( has_post_thumbnail( $post ) ) {
        $data['featured_image'] = array(
            'id'  => get_post_thumbnail_id( $post ),
            'url' => get_the_post_thumbnail_url( $post, 'large' ),
        );
    }

    if ( $request->get_param( 'context' ) === 'map' ) {
        $lat = get_post_meta( $post->ID, 'lbhotel_latitude', true );
        $lng = get_post_meta( $post->ID, 'lbhotel_longitude', true );

        if ( $lat && $lng ) {
            $data['lat'] = (float) $lat;
            $data['lng'] = (float) $lng;
        }
    }

    return $data;
}

/**
 * Provide schema example for documentation.
 *
 * @return array
 */
function lbhotel_rest_hotel_schema_example() {
    return array(
        'id'        => 42,
        'title'     => 'Le Bon Hotel Central',
        'permalink' => 'https://example.com/hotel/le-bon-hotel-central',
        'star_rating' => 4,
        'meta'      => array(
            'address'             => '123 Boulevard Hassan II',
            'city'                => 'Casablanca',
            'region'              => 'Casablanca-Settat',
            'postal_code'         => '20000',
            'country'             => 'Morocco',
            'checkin_time'        => '14:00',
            'checkout_time'       => '12:00',
            'rooms_total'         => 120,
            'avg_price_per_night' => 850.00,
            'has_free_breakfast'  => true,
            'has_parking'         => true,
            'star_rating'         => 4,
            'gallery_images'      => array( 54, 55, 56 ),
            'virtual_tour_url'    => 'https://example.com/virtual-tour',
            'contact_phone'       => '+212 5 22 33 44 55',
            'booking_url'         => 'https://bookings.example.com/le-bon-hotel-central',
            'rooms'               => array(
                array(
                    'name'       => 'Deluxe Suite',
                    'price'      => 1200.00,
                    'capacity'   => 3,
                    'images'     => array( 'https://example.com/suite.jpg' ),
                    'availability' => 'Available',
                ),
            ),
        ),
        'hotel_types' => array(
            array(
                'id'   => 3,
                'name' => 'Boutique',
                'slug' => 'boutique',
            ),
        ),
        'locations' => array(
            array(
                'id'   => 7,
                'name' => 'Casablanca',
                'slug' => 'casablanca',
            ),
        ),
        'currency' => 'MAD',
    );
}
