<?php
/**
 * Modern Moroccan-inspired archive template for Le Bon Hotel listings.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.css', array(), LBHOTEL_VERSION );
wp_enqueue_script( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.js', array(), LBHOTEL_VERSION, true );

$hotels_for_script = array();

$hotels_query = new WP_Query(
    array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);

if ( $hotels_query->have_posts() ) {
    while ( $hotels_query->have_posts() ) {
        $hotels_query->the_post();

        $post_id = get_the_ID();

        $gallery_ids  = (array) get_post_meta( $post_id, 'lbhotel_gallery_images', true );
        $gallery_urls = array();

        foreach ( $gallery_ids as $attachment_id ) {
            $image_url = wp_get_attachment_image_url( $attachment_id, 'large' );

            if ( $image_url ) {
                $gallery_urls[] = $image_url;
            }
        }

        if ( empty( $gallery_urls ) ) {
            $featured_image = get_the_post_thumbnail_url( $post_id, 'large' );

            if ( $featured_image ) {
                $gallery_urls[] = $featured_image;
            }
        }

        $latitude  = get_post_meta( $post_id, 'lbhotel_latitude', true );
        $longitude = get_post_meta( $post_id, 'lbhotel_longitude', true );

        $coordinates = null;

        if ( '' !== $latitude && '' !== $longitude ) {
            $coordinates = array(
                'lat' => (float) $latitude,
                'lng' => (float) $longitude,
            );
        }

        $price_meta = get_post_meta( $post_id, 'lbhotel_avg_price_per_night', true );
        $price      = '' !== $price_meta ? (float) $price_meta : null;

        $distance_meta = get_post_meta( $post_id, 'lbhotel_distance_km', true );
        $distance      = '' !== $distance_meta ? (float) $distance_meta : null;

        $description = get_the_excerpt();

        if ( ! $description ) {
            $description = wp_trim_words( wp_strip_all_tags( get_the_content() ), 30 );
        }

        $hotels_for_script[] = array(
            'id'             => $post_id,
            'name'           => get_the_title(),
            'city'           => get_post_meta( $post_id, 'lbhotel_city', true ),
            'region'         => get_post_meta( $post_id, 'lbhotel_region', true ),
            'country'        => get_post_meta( $post_id, 'lbhotel_country', true ),
            'rating'         => (int) get_post_meta( $post_id, 'lbhotel_star_rating', true ),
            'price'          => $price,
            'distance'       => $distance,
            'coordinates'    => $coordinates,
            'description'    => wp_strip_all_tags( $description ),
            'images'         => $gallery_urls,
            'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
            'booking_url'    => get_post_meta( $post_id, 'lbhotel_booking_url', true ),
            'details_url'    => get_permalink(),
            'available_from' => get_post_time( DATE_ATOM, true ),
        );
    }

    wp_reset_postdata();
}

wp_localize_script(
    'lbhotel-all-hotels',
    'lbhotelAllHotels',
    array(
        'hotels'   => $hotels_for_script,
        'currency' => lbhotel_get_option( 'default_currency' ),
        'perPage'  => 4,
        'strings'  => array(
            'empty'      => __( 'No hotels match your search. Try adjusting filters.', 'lbhotel' ),
            'emptyPage'  => __( 'No more hotels on this page.', 'lbhotel' ),
            'reserve'    => __( 'Reserve Booking', 'lbhotel' ),
            'map'        => __( 'Show on Map', 'lbhotel' ),
            'details'    => __( 'View Details', 'lbhotel' ),
            'priceLabel' => __( '/ night', 'lbhotel' ),
            'noImage'    => __( 'Image coming soon', 'lbhotel' ),
            'imageAlt'   => __( 'Hotel gallery image', 'lbhotel' ),
        ),
    )
);

get_header();
?>

<div id="content" class="site-content">
    <div class="ast-container">
        <?php if ( function_exists( 'astra_primary_content_top' ) ) { astra_primary_content_top(); } ?>
        <div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area"'; } ?>>
            <main id="main" class="site-main" data-all-hotels-page>
                <?php if ( function_exists( 'astra_primary_content_before' ) ) { astra_primary_content_before(); } ?>

                <div class="all-hotels" data-hotels-container>
                    <header class="all-hotels__filters" role="banner">
                        <form class="all-hotels__filters-form" aria-label="<?php esc_attr_e( 'Filter hotels', 'lbhotel' ); ?>">
                            <label class="all-hotels__field" for="hotel-search">
                                <span class="screen-reader-text"><?php esc_html_e( 'Search by hotel name or city', 'lbhotel' ); ?></span>
                                <input type="search" id="hotel-search" name="hotel_search" placeholder="<?php esc_attr_e( 'Search hotels or cities', 'lbhotel' ); ?>" autocomplete="off" />
                            </label>
                            <label class="all-hotels__field" for="hotel-distance">
                                <span class="screen-reader-text"><?php esc_html_e( 'Filter by distance', 'lbhotel' ); ?></span>
                                <select id="hotel-distance" name="hotel_distance">
                                    <option value="all"><?php esc_html_e( 'Any distance', 'lbhotel' ); ?></option>
                                    <option value="5"><?php esc_html_e( 'Near me · 5 km', 'lbhotel' ); ?></option>
                                    <option value="10"><?php esc_html_e( 'Near me · 10 km', 'lbhotel' ); ?></option>
                                    <option value="20"><?php esc_html_e( 'Near me · 20 km', 'lbhotel' ); ?></option>
                                </select>
                            </label>
                            <label class="all-hotels__field" for="hotel-rating">
                                <span class="screen-reader-text"><?php esc_html_e( 'Filter by star rating', 'lbhotel' ); ?></span>
                                <select id="hotel-rating" name="hotel_rating">
                                    <option value="all"><?php esc_html_e( 'Any rating', 'lbhotel' ); ?></option>
                                    <option value="5">5 ★</option>
                                    <option value="4">4 ★ &amp; up</option>
                                    <option value="3">3 ★ &amp; up</option>
                                    <option value="2">2 ★ &amp; up</option>
                                    <option value="1">1 ★ &amp; up</option>
                                </select>
                            </label>
                        </form>
                    </header>

                    <section class="all-hotels__sorting" role="region" aria-live="polite">
                        <div class="all-hotels__results">
                            <span id="hotel-count" data-hotel-count>0</span>
                            <span class="all-hotels__results-label"><?php esc_html_e( 'hotels found', 'lbhotel' ); ?></span>
                        </div>
                        <label class="all-hotels__sort" for="hotel-sort">
                            <span class="all-hotels__sort-label"><?php esc_html_e( 'Sort by', 'lbhotel' ); ?></span>
                            <select id="hotel-sort" name="hotel_sort">
                                <option value="date-asc"><?php esc_html_e( 'Date ASC', 'lbhotel' ); ?></option>
                                <option value="date-desc" selected><?php esc_html_e( 'Date DESC', 'lbhotel' ); ?></option>
                                <option value="distance-asc"><?php esc_html_e( 'Distance ASC', 'lbhotel' ); ?></option>
                                <option value="distance-desc"><?php esc_html_e( 'Distance DESC', 'lbhotel' ); ?></option>
                                <option value="rating-asc"><?php esc_html_e( 'Rating ASC', 'lbhotel' ); ?></option>
                                <option value="rating-desc"><?php esc_html_e( 'Rating DESC', 'lbhotel' ); ?></option>
                            </select>
                        </label>
                    </section>

                    <section class="all-hotels__list" id="hotel-list" aria-live="polite" aria-label="<?php esc_attr_e( 'Hotel results', 'lbhotel' ); ?>"></section>

                    <nav class="all-hotels__pagination" aria-label="<?php esc_attr_e( 'Hotel pagination', 'lbhotel' ); ?>">
                        <button type="button" class="all-hotels__pagination-button" data-pagination="prev"><?php esc_html_e( 'Prev', 'lbhotel' ); ?></button>
                        <button type="button" class="all-hotels__pagination-button" data-pagination="next"><?php esc_html_e( 'Next', 'lbhotel' ); ?></button>
                    </nav>
                </div>

                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_sidebar_primary' ) ) { astra_sidebar_primary(); } ?>
    </div>
</div>

<?php
get_footer();
