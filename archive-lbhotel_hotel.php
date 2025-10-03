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

if ( $theme_style_path && file_exists( $theme_style_path ) ) {
    $style_version = (string) ( filemtime( $theme_style_path ) ?: time() );
    wp_enqueue_style( 'lbhotel-all-hotels', $theme_directory_uri . '/all-hotel.css', array(), $style_version );
} else {
    $style_version = file_exists( $plugin_style_path ) ? (string) filemtime( $plugin_style_path ) : LBHOTEL_VERSION;
    wp_enqueue_style( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.css', array(), $style_version );
}

if ( $theme_script_path && file_exists( $theme_script_path ) ) {
    $script_version = (string) ( filemtime( $theme_script_path ) ?: time() );
    wp_enqueue_script( 'lbhotel-all-hotels', $theme_directory_uri . '/all-hotel.js', array(), $script_version, true );
} else {
    $script_version = file_exists( $plugin_script_path ) ? (string) filemtime( $plugin_script_path ) : LBHOTEL_VERSION;
    wp_enqueue_script( 'lbhotel-all-hotels', LBHOTEL_PLUGIN_URL . 'all-hotel.js', array(), $script_version, true );
}

$currency_code = function_exists( 'lbhotel_get_option' ) ? lbhotel_get_option( 'default_currency' ) : '';

$hotels_query = new WP_Query(
    array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);

global $post;

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

                <?php if ( $hotels_query->have_posts() ) : ?>
                    <div class="lbhotel-archive__list">
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

                            $gallery_urls = array_slice( $gallery_urls, 0, 5 );

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
                            ?>

                            <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-archive-card' ); ?>>
                                <div class="lbhotel-archive-card__media">
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
                                                <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'lbhotel' ); ?>">&#10094;</button>
                                                <button type="button" class="lbhotel-slider__nav lbhotel-slider__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'lbhotel' ); ?>">&#10095;</button>
                                                <div class="lbhotel-slider__dots" role="tablist">
                                                    <?php foreach ( $gallery_urls as $index => $unused ) : ?>
                                                        <button type="button" class="lbhotel-slider__dot" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>"></button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="lbhotel-slider lbhotel-slider--empty">
                                            <span><?php esc_html_e( 'No images available', 'lbhotel' ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="lbhotel-archive-card__content">
                                    <h2 class="lbhotel-archive-card__title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    <?php if ( $location_parts ) : ?>
                                        <p class="lbhotel-archive-card__location"><?php echo esc_html( implode( ', ', $location_parts ) ); ?></p>
                                    <?php endif; ?>

                                    <?php if ( $star_rating > 0 ) : ?>
                                        <div class="lbhotel-archive-card__stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                                            <?php echo wp_kses_post( str_repeat( '<span aria-hidden="true">★</span>', min( 5, $star_rating ) ) ); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( $price_text ) : ?>
                                        <p class="lbhotel-archive-card__price"><?php echo esc_html( sprintf( __( 'Average price per night: %s', 'lbhotel' ), $price_text ) ); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="lbhotel-archive-card__actions">
                                    <?php if ( $booking_url ) : ?>
                                        <a class="lbhotel-button lbhotel-button--reserve" href="<?php echo esc_url( $booking_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reserve Booking', 'lbhotel' ); ?></a>
                                    <?php endif; ?>

                                    <?php if ( $map_url ) : ?>
                                        <a class="lbhotel-button lbhotel-button--map" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Map', 'lbhotel' ); ?></a>
                                    <?php endif; ?>

                                    <a class="lbhotel-button lbhotel-button--details" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Details', 'lbhotel' ); ?></a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <p class="lbhotel-archive__empty"><?php esc_html_e( 'No hotels found at this time. Please check back soon.', 'lbhotel' ); ?></p>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>

                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_sidebar_primary' ) ) { astra_sidebar_primary(); } ?>
    </div>
</div>

<?php
get_footer();
