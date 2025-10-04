<?php
/**
 * Single template for the `lbhotel_hotel` custom post type.
 *
 * This template builds a Moroccan-inspired two-column layout showcasing
 * the virtual tour, hotel highlights, and an interactive Leaflet map with
 * neighbouring properties.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme_directory_uri  = get_stylesheet_directory_uri();
$theme_directory_path = function_exists( 'get_stylesheet_directory' ) ? trailingslashit( get_stylesheet_directory() ) : '';

// Styles.
wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
if ( $theme_directory_path && file_exists( $theme_directory_path . 'single-hotel.css' ) ) {
    wp_enqueue_style( 'lbhotel-single-hotel', $theme_directory_uri . '/single-hotel.css', array( 'leaflet' ), '1.0.0' );
} else {
    wp_enqueue_style( 'lbhotel-single-hotel', LBHOTEL_PLUGIN_URL . 'single-hotel.css', array( 'leaflet' ), LBHOTEL_VERSION );
}

// Scripts.
wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
if ( $theme_directory_path && file_exists( $theme_directory_path . 'single-hotel.js' ) ) {
    wp_enqueue_script( 'lbhotel-single-hotel', $theme_directory_uri . '/single-hotel.js', array( 'leaflet' ), '1.0.0', true );
} else {
    wp_enqueue_script( 'lbhotel-single-hotel', LBHOTEL_PLUGIN_URL . 'single-hotel.js', array( 'leaflet' ), LBHOTEL_VERSION, true );
}

$current_id = get_queried_object_id();

$city        = $current_id ? get_post_meta( $current_id, 'lbhotel_city', true ) : '';
$region      = $current_id ? get_post_meta( $current_id, 'lbhotel_region', true ) : '';
$country     = $current_id ? get_post_meta( $current_id, 'lbhotel_country', true ) : '';
$star_rating = (int) ( $current_id ? get_post_meta( $current_id, 'lbhotel_star_rating', true ) : 0 );
$avg_price   = $current_id ? get_post_meta( $current_id, 'lbhotel_avg_price_per_night', true ) : '';
$booking_url = $current_id ? get_post_meta( $current_id, 'lbhotel_booking_url', true ) : '';
$latitude    = $current_id ? get_post_meta( $current_id, 'lbhotel_latitude', true ) : '';
$longitude   = $current_id ? get_post_meta( $current_id, 'lbhotel_longitude', true ) : '';
$gallery_raw = $current_id ? get_post_meta( $current_id, 'lbhotel_gallery_images', true ) : array();

$virtual_tour_url = $current_id ? get_post_meta( $current_id, 'virtual_tour_url', true ) : '';
if ( ! $virtual_tour_url && $current_id ) {
    $virtual_tour_url = get_post_meta( $current_id, 'lbhotel_virtual_tour_url', true );
}

$gallery_ids  = is_array( $gallery_raw ) ? $gallery_raw : array_filter( array_map( 'absint', (array) $gallery_raw ) );
$gallery_urls = array();

foreach ( $gallery_ids as $attachment_id ) {
    $image_url = wp_get_attachment_image_url( $attachment_id, 'large' );
    if ( $image_url ) {
        $gallery_urls[] = $image_url;
    }
}

$price_display = '';
if ( '' !== $avg_price && null !== $avg_price ) {
    $price_display = is_numeric( $avg_price ) ? number_format_i18n( (float) $avg_price, 2 ) : sanitize_text_field( $avg_price );
}

$map_url = '';
if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
    $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $latitude . ',' . $longitude ) );
}

$current_payload = array(
    'id'         => $current_id,
    'title'      => $current_id ? get_the_title( $current_id ) : '',
    'lat'        => is_numeric( $latitude ) ? (float) $latitude : null,
    'lng'        => is_numeric( $longitude ) ? (float) $longitude : null,
    'city'       => $city,
    'region'     => $region,
    'country'    => $country,
    'price'      => $price_display,
    'stars'      => $star_rating,
    'bookingUrl' => $booking_url ? esc_url_raw( $booking_url ) : '',
    'mapUrl'     => $map_url ? esc_url_raw( $map_url ) : '',
    'permalink'  => $current_id ? get_permalink( $current_id ) : '',
    'images'     => $gallery_urls,
    'virtualTourUrl' => $virtual_tour_url ? esc_url_raw( $virtual_tour_url ) : '',
);

wp_localize_script(
    'lbhotel-single-hotel',
    'lbHotelSingleData',
    array(
        'currentHotel'   => $current_payload,
        'fallbackCenter' => array(
            'lat' => 31.7917,
            'lng' => -7.0926,
        ),
    )
);

get_header();
?>

<div id="primary" class="content-area lbhotel-single-wrapper">
    <main id="main" class="site-main" role="main">
        <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single-article' ); ?>>
                <div class="lbhotel-single-page">
                    <div class="lbhotel-single-layout">
                        <div class="lbhotel-single-left">
                            <?php if ( $virtual_tour_url ) : ?>
                                <section class="lbhotel-virtual-tour" aria-label="<?php esc_attr_e( 'Virtual tour', 'lbhotel' ); ?>">
                                    <iframe src="<?php echo esc_url( $virtual_tour_url ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"
                                        loading="lazy" allowfullscreen></iframe>
                                </section>
                            <?php endif; ?>

                            <?php $info_card_payload = $current_payload; ?>
                            <section class="lbhotel-info-card" aria-label="<?php esc_attr_e( 'Hotel highlight', 'lbhotel' ); ?>"
                                data-hotel='<?php echo esc_attr( wp_json_encode( $info_card_payload ) ); ?>'>
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
                                                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'lbhotel' ); ?>" role="button" tabindex="0">&#10094;</div>
                                            <div class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'lbhotel' ); ?>" role="button" tabindex="0">&#10095;</div>
                                            <div class="lbhotel-slider__dots" role="tablist">
                                                <?php foreach ( $gallery_urls as $index => $unused ) : ?>
                                                    <div class="lbhotel-slider__dot" role="tab" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>" tabindex="0"></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="lbhotel-slider lbhotel-slider--empty">
                                            <div class="lbhotel-slider__placeholder"><?php esc_html_e( 'Image gallery coming soon.', 'lbhotel' ); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="lbhotel-info-card__details">
                                    <h1 class="lbhotel-info-card__title"><?php the_title(); ?></h1>
                                    <p class="lbhotel-info-card__location">
                                        <?php
                                        $location_bits = array_filter( array( $city, $region, $country ) );
                                        echo esc_html( implode( ', ', $location_bits ) );
                                        ?>
                                    </p>
                                    <?php if ( $star_rating > 0 ) : ?>
                                        <div class="lbhotel-info-card__stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                                            <?php echo wp_kses_post( str_repeat( '<span aria-hidden="true">★</span>', min( 5, $star_rating ) ) ); ?>
                                            <span class="lbhotel-info-card__stars-text"><?php echo esc_html( sprintf( '%d/5', min( 5, max( 0, (int) $star_rating ) ) ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $price_display ) : ?>
                                        <p class="lbhotel-info-card__price"><?php echo esc_html( sprintf( __( 'Average price per night: %s', 'lbhotel' ), $price_display ) ); ?></p>
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
                        </div>
                        <div class="lbhotel-single-right">
                            <section class="lbhotel-map-section" aria-label="<?php esc_attr_e( 'Hotel map', 'lbhotel' ); ?>">
                                <div id="lbhotel-map" class="lbhotel-map" role="region" aria-label="<?php esc_attr_e( 'Interactive hotel map', 'lbhotel' ); ?>"></div>
                            </section>
                        </div>
                    </div>
                    <div class="lbhotel-single-content">
                        <?php the_content(); ?>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
    </main>
</div>

<?php
wp_footer();