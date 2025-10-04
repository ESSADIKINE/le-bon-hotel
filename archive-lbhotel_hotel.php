<?php
/**
 * Archive template for the `lbhotel_hotel` custom post type.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme_directory_uri  = get_stylesheet_directory_uri();
$theme_directory_path = function_exists( 'get_stylesheet_directory' ) ? trailingslashit( get_stylesheet_directory() ) : '';
$theme_style_path     = $theme_directory_path ? $theme_directory_path . 'all-hotel.css' : '';
$theme_script_path    = $theme_directory_path ? $theme_directory_path . 'all-hotel.js' : '';

$plugin_style_path  = trailingslashit( LBHOTEL_PLUGIN_DIR ) . 'all-hotel.css';
$plugin_script_path = trailingslashit( LBHOTEL_PLUGIN_DIR ) . 'all-hotel.js';

wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );

if ( $theme_style_path && file_exists( $theme_style_path ) ) {
    $style_version = (string) ( filemtime( $theme_style_path ) ?: time() );
    wp_enqueue_style( 'lbhotel-all-hotels', $theme_directory_uri . '/all-hotel.css', array(), $style_version );
} else {
    $style_version = file_exists( $plugin_style_path ) ? (string) filemtime( $plugin_style_path ) : LBHOTEL_VERSION;
    wp_enqueue_style( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.css', array(), $style_version );
}

wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

if ( $theme_script_path && file_exists( $theme_script_path ) ) {
    $script_version = (string) ( filemtime( $theme_script_path ) ?: time() );
    wp_enqueue_script( 'lbhotel-all-hotels', $theme_directory_uri . '/all-hotel.js', array( 'leaflet' ), $script_version, true );
} else {
    $script_version = file_exists( $plugin_script_path ) ? (string) filemtime( $plugin_script_path ) : LBHOTEL_VERSION;
    wp_enqueue_script( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.js', array( 'leaflet' ), $script_version, true );
}

$currency_code = function_exists( 'lbhotel_get_option' ) ? lbhotel_get_option( 'default_currency' ) : '';

$per_page_options = array( 5, 10, 20, 50 );
$default_per_page = 10;
$requested_per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page          = in_array( $requested_per_page, $per_page_options, true ) ? $requested_per_page : $default_per_page;

$paged = max( 1, get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 ) );

$hotels_query = new WP_Query(
    array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);

global $post;

$hotels_payload = array();

get_header();
?>

<div id="content" class="site-content">
    <div class="ast-container">
        <?php if ( function_exists( 'astra_primary_content_top' ) ) { astra_primary_content_top(); } ?>
        <div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area"'; } ?>>
            <main id="main" class="site-main lbhotel-all-hotels" data-all-hotels-page role="main">
                <?php if ( function_exists( 'astra_primary_content_before' ) ) { astra_primary_content_before(); } ?>

                <header class="lbhotel-archive__header">
                    <h1 class="lbhotel-archive__title"><?php post_type_archive_title(); ?></h1>
                    <p class="lbhotel-archive__intro"><?php esc_html_e( 'Discover authentic Moroccan stays and plan your next escape.', 'lbhotel' ); ?></p>
                </header>

                <?php $hotels_count = $hotels_query->found_posts; ?>

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
                            <span id="hotel-count" data-hotel-count="<?php echo esc_attr( $hotels_count ); ?>"><?php echo esc_html( number_format_i18n( $hotels_count ) ); ?></span>
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

                    <?php if ( $hotels_query->have_posts() ) : ?>
                        <section class="all-hotels__list" id="hotel-list" aria-live="polite" aria-label="<?php esc_attr_e( 'Hotel results', 'lbhotel' ); ?>">
                            <?php
                            while ( $hotels_query->have_posts() ) :
                                $hotels_query->the_post();

                                $post_id = get_the_ID();

                                $city        = get_post_meta( $post_id, 'lbhotel_city', true );
                                $region      = get_post_meta( $post_id, 'lbhotel_region', true );
                                $country     = get_post_meta( $post_id, 'lbhotel_country', true );
                                $star_rating = (int) get_post_meta( $post_id, 'lbhotel_star_rating', true );
                                $avg_price   = get_post_meta( $post_id, 'lbhotel_avg_price_per_night', true );
                                $booking_url = get_post_meta( $post_id, 'lbhotel_booking_url', true );
                                $latitude    = get_post_meta( $post_id, 'lbhotel_latitude', true );
                                $longitude   = get_post_meta( $post_id, 'lbhotel_longitude', true );

                                $virtual_tour_url = get_post_meta( $post_id, 'virtual_tour_url', true );
                                if ( ! $virtual_tour_url ) {
                                    $virtual_tour_url = get_post_meta( $post_id, 'lbhotel_virtual_tour_url', true );
                                }

                                $gallery_raw = get_post_meta( $post_id, 'lbhotel_gallery_images', true );
                                $gallery_ids = is_array( $gallery_raw ) ? $gallery_raw : array_filter( array_map( 'absint', (array) $gallery_raw ) );

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

                                $gallery_urls = array_slice( $gallery_urls, 0, 6 );

                                $price_display = '';
                                if ( '' !== $avg_price && null !== $avg_price ) {
                                    $price_display = is_numeric( $avg_price ) ? number_format_i18n( (float) $avg_price, 2 ) : sanitize_text_field( $avg_price );
                                }

                                $price_text = $price_display;
                                if ( $price_display && $currency_code ) {
                                    $price_text = sprintf( '%s %s', $currency_code, $price_display );
                                }

                                $map_url = '';
                                if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
                                    $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $latitude . ',' . $longitude ) );
                                }

                                $location_parts = array_filter( array( $city, $region, $country ) );

                                $hotel_payload = array(
                                    'id'             => $post_id,
                                    'title'          => get_the_title(),
                                    'lat'            => is_numeric( $latitude ) ? (float) $latitude : null,
                                    'lng'            => is_numeric( $longitude ) ? (float) $longitude : null,
                                    'city'           => $city,
                                    'region'         => $region,
                                    'country'        => $country,
                                    'price'          => $price_text,
                                    'stars'          => $star_rating,
                                    'bookingUrl'     => $booking_url ? esc_url_raw( $booking_url ) : '',
                                    'mapUrl'         => $map_url ? esc_url_raw( $map_url ) : '',
                                    'permalink'      => get_permalink(),
                                    'images'         => $gallery_urls,
                                    'virtualTourUrl' => $virtual_tour_url ? esc_url_raw( $virtual_tour_url ) : '',
                                );

                                $hotels_payload[] = $hotel_payload;
                                ?>

                                <section class="lbhotel-info-card" aria-labelledby="hotel-<?php the_ID(); ?>-title"
                                    data-hotel='<?php echo esc_attr( wp_json_encode( $hotel_payload ) ); ?>'>
                                    <div class="lbhotel-info-card__media">
                                        <div class="lbhotel-info-card__icons">
                                            <button type="button" class="lbhotel-icon lbhotel-icon--tour" aria-label="<?php esc_attr_e( 'Virtual Tour', 'lbhotel' ); ?>" data-tour-url="<?php echo esc_url( $virtual_tour_url ); ?>">🎥</button>
                                            <button type="button" class="lbhotel-icon lbhotel-icon--map" aria-label="<?php esc_attr_e( 'Map View', 'lbhotel' ); ?>">🗺️</button>
                                        </div>
                                        <?php if ( $gallery_urls ) : ?>
                                            <div class="lbhotel-slider" data-lbhotel-slider>
                                                <div class="lbhotel-slider__track">
                                                    <?php foreach ( $gallery_urls as $image_url ) : ?>
                                                        <div class="lbhotel-slider__slide">
                                                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if ( count( $gallery_urls ) > 1 ) : ?>
                                                    <div class="lbhotel-slider__nav lbhotel-slider__nav--prev" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Previous image', 'lbhotel' ); ?>">&#10094;</div>
                                                    <div class="lbhotel-slider__nav lbhotel-slider__nav--next" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Next image', 'lbhotel' ); ?>">&#10095;</div>
                                                    <div class="lbhotel-slider__dots" role="tablist">
                                                        <?php foreach ( $gallery_urls as $index => $unused ) : ?>
                                                            <div class="lbhotel-slider__dot" role="tab" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>"></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else : ?>
                                            <div class="lbhotel-slider lbhotel-slider--empty">
                                                <div class="lbhotel-slider__placeholder"><?php esc_html_e( 'Image gallery coming soon.', 'lbhotel' ); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lbhotel-info-card__details">
                                        <h2 id="hotel-<?php the_ID(); ?>-title" class="lbhotel-info-card__title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        <?php if ( $location_parts ) : ?>
                                            <p class="lbhotel-info-card__location"><?php echo esc_html( implode( ', ', $location_parts ) ); ?></p>
                                        <?php endif; ?>

                                        <?php if ( $star_rating > 0 ) : ?>
                                            <div class="lbhotel-info-card__stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                                                <?php echo wp_kses_post( str_repeat( '<span aria-hidden="true">★</span>', min( 5, $star_rating ) ) ); ?>
                                                <span class="lbhotel-info-card__stars-text"><?php echo esc_html( sprintf( '%d/5', min( 5, max( 0, (int) $star_rating ) ) ) ); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ( $price_text ) : ?>
                                            <p class="lbhotel-info-card__price"><?php echo esc_html( sprintf( __( 'Average price per night: %s', 'lbhotel' ), $price_text ) ); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lbhotel-info-card__actions">
                                        <?php if ( $booking_url ) : ?>
                                            <a class="lbhotel-button lbhotel-button--reserve" href="<?php echo esc_url( $booking_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reserve Booking', 'lbhotel' ); ?></a>
                                        <?php endif; ?>

                                        <?php if ( $map_url ) : ?>
                                            <a class="lbhotel-button lbhotel-button--map" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Map', 'lbhotel' ); ?></a>
                                        <?php endif; ?>

                                        <a class="lbhotel-button lbhotel-button--details" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Details', 'lbhotel' ); ?></a>
                                    </div>
                                </section>
                            <?php endwhile; ?>
                        </section>
                    <?php else : ?>
                        <p class="lbhotel-archive__empty"><?php esc_html_e( 'No hotels found at this time. Please check back soon.', 'lbhotel' ); ?></p>
                    <?php endif; ?>
                </div>

                <?php
                wp_reset_postdata();

                if ( ! empty( $hotels_payload ) ) {
                    wp_localize_script(
                        'lbhotel-all-hotels',
                        'lbHotelArchiveData',
                        array(
                            'hotels'          => array_values( $hotels_payload ),
                            'fallbackCenter'  => array(
                                'lat' => 31.7917,
                                'lng' => -7.0926,
                            ),
                        )
                    );
                }
                ?>

                <?php
                $pagination_add_args = array();
                if ( isset( $_GET['per_page'] ) && $per_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $pagination_add_args['per_page'] = $per_page;
                }

                $pagination_links = paginate_links(
                    array(
                        'total'     => max( 1, (int) $hotels_query->max_num_pages ),
                        'current'   => $paged,
                        'mid_size'  => 2,
                        'prev_text' => __( 'Previous', 'lbhotel' ),
                        'next_text' => __( 'Next', 'lbhotel' ),
                        'type'      => 'list',
                        'add_args'  => $pagination_add_args ? $pagination_add_args : false,
                    )
                );

                $pagination_markup = $pagination_links;
                if ( empty( $pagination_markup ) ) {
                    /* translators: Pagination fallback when there is only one page of results. */
                    $pagination_markup = sprintf(
                        '<ul class="page-numbers"><li><span class="page-numbers current">%s</span></li></ul>',
                        esc_html__( '1', 'lbhotel' )
                    );
                }
                ?>

                <section class="all-hotels__pagination" aria-label="<?php esc_attr_e( 'Hotels pagination', 'lbhotel' ); ?>">
                    <form class="all-hotels__pagination-form" method="get">
                        <label for="hotels-per-page" class="all-hotels__pagination-label">
                            <span><?php esc_html_e( 'Hotels per page', 'lbhotel' ); ?></span>
                            <select id="hotels-per-page" name="per_page">
                                <?php foreach ( $per_page_options as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $per_page, $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php
                        if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                if ( 'per_page' === $key || 'paged' === $key ) {
                                    continue;
                                }

                                $sanitized_key = sanitize_key( $key );

                                if ( is_string( $value ) ) {
                                    echo '<input type="hidden" name="' . esc_attr( $sanitized_key ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '" />';
                                }
                            }
                        }
                        ?>
                        <noscript>
                            <button type="submit" class="all-hotels__pagination-apply"><?php esc_html_e( 'Apply', 'lbhotel' ); ?></button>
                        </noscript>
                    </form>
                    <nav class="all-hotels__pagination-links" aria-label="<?php esc_attr_e( 'Pagination links', 'lbhotel' ); ?>">
                        <?php echo wp_kses_post( $pagination_markup ); ?>
                    </nav>
                </section>

                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_sidebar_primary' ) ) { astra_sidebar_primary(); } ?>
    </div>
</div>

<?php
get_footer();
