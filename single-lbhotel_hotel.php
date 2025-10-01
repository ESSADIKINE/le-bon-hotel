<?php
/**
 * Astra override template for displaying a single Le Bon Hotel entry.
 *
 * Copy this file into your active theme (or child theme) to override the
 * single view for the `lbhotel_hotel` custom post type provided by the
 * Le Bon Hotel plugin.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme_directory_uri  = get_stylesheet_directory_uri();
$theme_directory_path = function_exists( 'get_stylesheet_directory' ) ? trailingslashit( get_stylesheet_directory() ) : '';

// Styles
wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
if ( $theme_directory_path && file_exists( $theme_directory_path . 'hotel.css' ) ) {
    wp_enqueue_style( 'lbhotel-hotel', $theme_directory_uri . '/hotel.css', array( 'leaflet' ), '1.0.0' );
} else {
    wp_enqueue_style( 'lbhotel-hotel', LBHOTEL_PLUGIN_URL . 'hotel.css', array( 'leaflet' ), LBHOTEL_VERSION );
}

// Scripts
wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
wp_enqueue_script( 'esri-leaflet', 'https://unpkg.com/esri-leaflet@3.0.11/dist/esri-leaflet.js', array( 'leaflet' ), '3.0.11', true );
if ( $theme_directory_path && file_exists( $theme_directory_path . 'hotel.js' ) ) {
    wp_enqueue_script( 'lbhotel-hotel', $theme_directory_uri . '/hotel.js', array( 'leaflet', 'esri-leaflet' ), '1.0.0', true );
} else {
    wp_enqueue_script( 'lbhotel-hotel', LBHOTEL_PLUGIN_URL . 'hotel.js', array( 'leaflet', 'esri-leaflet' ), LBHOTEL_VERSION, true );
}

$current_id    = get_queried_object_id();
$current_lat   = $current_id ? get_post_meta( $current_id, 'lbhotel_latitude', true ) : null;
$current_lng   = $current_id ? get_post_meta( $current_id, 'lbhotel_longitude', true ) : null;
$hotels_query  = get_posts(
    array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    )
);

$hotels_payload = array();

foreach ( $hotels_query as $hotel_id ) {
    $lat = get_post_meta( $hotel_id, 'lbhotel_latitude', true );
    $lng = get_post_meta( $hotel_id, 'lbhotel_longitude', true );

    $hotels_payload[] = array(
        'id'    => $hotel_id,
        'title' => get_the_title( $hotel_id ),
        'url'   => get_permalink( $hotel_id ),
        'stars' => (int) get_post_meta( $hotel_id, 'lbhotel_star_rating', true ),
        'lat'   => is_numeric( $lat ) ? (float) $lat : null,
        'lng'   => is_numeric( $lng ) ? (float) $lng : null,
    );
}

$current_payload = array(
    'id'    => $current_id,
    'title' => $current_id ? get_the_title( $current_id ) : '',
    'lat'   => is_numeric( $current_lat ) ? (float) $current_lat : null,
    'lng'   => is_numeric( $current_lng ) ? (float) $current_lng : null,
);

wp_localize_script(
    'lbhotel-hotel',
    'lbHotelMapData',
    array(
        'context'       => 'single',
        'defaultCenter' => array(
            'lat' => 31.7917,
            'lng' => -7.0926,
        ),
        'currentHotel'  => $current_payload,
        'hotels'        => $hotels_payload,
    )
);

get_header();
?>

<div id="content" class="site-content">
    <div class="ast-container">
        <?php if ( function_exists( 'astra_primary_content_top' ) ) { astra_primary_content_top(); } ?>
        <div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area"'; } ?>>
            <main id="main" class="site-main">
                <?php if ( function_exists( 'astra_primary_content_before' ) ) { astra_primary_content_before(); } ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php if ( function_exists( 'astra_entry_before' ) ) { astra_entry_before(); } ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single moroccan-card' ); ?>>
                        <?php if ( function_exists( 'astra_entry_top' ) ) { astra_entry_top(); } ?>
                        <?php if ( function_exists( 'astra_entry_content_before' ) ) { astra_entry_content_before(); } ?>
                        <div class="entry-content moroccan-entry" data-lbhotel-context="single">
                            <?php
                            $hotel_id     = get_the_ID();
                            $city         = get_post_meta( $hotel_id, 'lbhotel_city', true );
                            $region       = get_post_meta( $hotel_id, 'lbhotel_region', true );
                            $postal       = get_post_meta( $hotel_id, 'lbhotel_postal_code', true );
                            $country      = get_post_meta( $hotel_id, 'lbhotel_country', true );
                            $star_rating  = (int) get_post_meta( $hotel_id, 'lbhotel_star_rating', true );
                            $rooms_total  = get_post_meta( $hotel_id, 'lbhotel_rooms_total', true );
                            $checkin      = get_post_meta( $hotel_id, 'lbhotel_checkin_time', true );
                            $checkout     = get_post_meta( $hotel_id, 'lbhotel_checkout_time', true );
                            $avg_price    = get_post_meta( $hotel_id, 'lbhotel_avg_price_per_night', true );
                            $gallery_meta = get_post_meta( $hotel_id, 'lbhotel_gallery_images', true );
                            $tour_url     = get_post_meta( $hotel_id, 'lbhotel_virtual_tour_url', true );
                            $booking_url  = get_post_meta( $hotel_id, 'lbhotel_booking_url', true );

                            $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : array_filter( array_map( 'absint', (array) $gallery_meta ) );

                            $price_display = '';
                            if ( '' !== $avg_price && null !== $avg_price ) {
                                $price_display = is_numeric( $avg_price ) ? number_format_i18n( (float) $avg_price, 2 ) : sanitize_text_field( $avg_price );
                            }

                            $location_bits = array_filter( array( $city, $region, $postal, $country ) );
                            ?>

                            <section class="moroccan-section moroccan-hero">
                                <header class="moroccan-hero__header">
                                    <h1 class="moroccan-hero__title"><?php the_title(); ?></h1>
                                    <?php if ( $star_rating > 0 ) : ?>
                                        <div class="moroccan-stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                                            <?php echo wp_kses_post( str_repeat( '<span aria-hidden="true">★</span>', min( 5, $star_rating ) ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $location_bits ) : ?>
                                        <p class="moroccan-hero__location"><?php echo esc_html( implode( ', ', $location_bits ) ); ?></p>
                                    <?php endif; ?>
                                </header>
                                <div class="moroccan-hero__details">
                                    <?php if ( $rooms_total ) : ?>
                                        <div class="moroccan-detail"><span class="label"><?php esc_html_e( 'Rooms', 'lbhotel' ); ?>:</span> <span class="value"><?php echo esc_html( $rooms_total ); ?></span></div>
                                    <?php endif; ?>
                                    <?php if ( $checkin || $checkout ) : ?>
                                        <div class="moroccan-detail"><span class="label"><?php esc_html_e( 'Check-in / Check-out', 'lbhotel' ); ?>:</span> <span class="value"><?php echo esc_html( trim( $checkin . ' / ' . $checkout, ' /' ) ); ?></span></div>
                                    <?php endif; ?>
                                    <?php if ( $price_display ) : ?>
                                        <div class="moroccan-detail moroccan-price"><span class="label"><?php esc_html_e( 'Average price per night', 'lbhotel' ); ?>:</span> <span class="value"><?php echo esc_html( $price_display ); ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <?php if ( $gallery_ids ) : ?>
                                <section class="moroccan-section moroccan-gallery" aria-label="<?php esc_attr_e( 'Hotel gallery', 'lbhotel' ); ?>">
                                    <h2 class="moroccan-section__title"><?php esc_html_e( 'Gallery', 'lbhotel' ); ?></h2>
                                    <div class="moroccan-gallery__grid" data-lightbox-gallery>
                                        <?php foreach ( $gallery_ids as $attachment_id ) :
                                            $image_url   = wp_get_attachment_image_url( $attachment_id, 'large' );
                                            $image_thumb = wp_get_attachment_image_url( $attachment_id, 'medium_large' );
                                            if ( ! $image_url ) {
                                                continue;
                                            }
                                            ?>
                                            <a href="<?php echo esc_url( $image_url ); ?>" class="moroccan-gallery__item" data-lightbox-item>
                                                <img src="<?php echo esc_url( $image_thumb ? $image_thumb : $image_url ); ?>" alt="<?php echo esc_attr( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ); ?>" />
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <?php if ( $tour_url ) : ?>
                                <section class="moroccan-section moroccan-tour">
                                    <h2 class="moroccan-section__title"><?php esc_html_e( 'Virtual Tour', 'lbhotel' ); ?></h2>
                                    <div class="moroccan-iframe-wrapper">
                                        <iframe src="<?php echo esc_url( $tour_url ); ?>" loading="lazy" allowfullscreen title="<?php esc_attr_e( 'Virtual tour', 'lbhotel' ); ?>"></iframe>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <section class="moroccan-section moroccan-map" aria-label="<?php esc_attr_e( 'Hotel map', 'lbhotel' ); ?>">
                                <h2 class="moroccan-section__title"><?php esc_html_e( 'Discover nearby delights', 'lbhotel' ); ?></h2>
                                <div id="lbhotel-map" class="moroccan-map__canvas" data-current-hotel="<?php echo esc_attr( get_the_ID() ); ?>"></div>
                            </section>

                            <section class="moroccan-section moroccan-booking">
                                <h2 class="moroccan-section__title"><?php esc_html_e( 'Book your stay', 'lbhotel' ); ?></h2>
                                <?php if ( $booking_url ) : ?>
                                    <div class="moroccan-iframe-wrapper">
                                        <iframe src="<?php echo esc_url( $booking_url ); ?>" loading="lazy" title="<?php esc_attr_e( 'Booking', 'lbhotel' ); ?>"></iframe>
                                    </div>
                                <?php else : ?>
                                    <p class="moroccan-placeholder"><?php esc_html_e( 'Booking information will be available soon. Contact the hotel directly for reservations.', 'lbhotel' ); ?></p>
                                <?php endif; ?>
                            </section>
                        </div>
                        <?php if ( function_exists( 'astra_entry_content_after' ) ) { astra_entry_content_after(); } ?>
                    </article>
                    <?php if ( function_exists( 'astra_entry_after' ) ) { astra_entry_after(); } ?>
                <?php endwhile; ?>
                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_primary_content_bottom' ) ) { astra_primary_content_bottom(); } ?>
    </div>
</div>

<?php get_footer(); ?>
