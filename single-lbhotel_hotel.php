<?php
/**
 * Template for displaying a single Le Bon Hotel entry.
 *
 * Copy this file into your active theme to override the single view for the
 * `lbhotel_hotel` custom post type provided by the Le Bon Hotel plugin.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
wp_enqueue_script( 'esri-leaflet', 'https://unpkg.com/esri-leaflet@3.0.11/dist/esri-leaflet.js', array( 'leaflet' ), '3.0.11', true );

get_header();
?>

<div class="hotel-container">
    <?php
    while ( have_posts() ) :
        the_post();

        $hotel_id   = get_the_ID();
        $city       = get_post_meta( $hotel_id, 'lbhotel_city', true );
        $region     = get_post_meta( $hotel_id, 'lbhotel_region', true );
        $postal     = get_post_meta( $hotel_id, 'lbhotel_postal_code', true );
        $country    = get_post_meta( $hotel_id, 'lbhotel_country', true );
        $star_rating = (int) get_post_meta( $hotel_id, 'lbhotel_star_rating', true );
        $rooms_total = get_post_meta( $hotel_id, 'lbhotel_rooms_total', true );
        $checkin     = get_post_meta( $hotel_id, 'lbhotel_checkin_time', true );
        $checkout    = get_post_meta( $hotel_id, 'lbhotel_checkout_time', true );
        $avg_price   = get_post_meta( $hotel_id, 'lbhotel_avg_price_per_night', true );
        $gallery_ids = get_post_meta( $hotel_id, 'lbhotel_gallery_images', true );
        $virtual_tour = get_post_meta( $hotel_id, 'lbhotel_virtual_tour_url', true );
        $booking_url = get_post_meta( $hotel_id, 'lbhotel_booking_url', true );
        $latitude    = get_post_meta( $hotel_id, 'lbhotel_latitude', true );
        $longitude   = get_post_meta( $hotel_id, 'lbhotel_longitude', true );

        if ( ! is_array( $gallery_ids ) ) {
            $gallery_ids = array_filter( array_map( 'absint', (array) $gallery_ids ) );
        }

        $star_rating        = max( 0, min( 5, $star_rating ) );
        $formatted_price    = ( '' !== $avg_price && null !== $avg_price ) ? ( is_numeric( $avg_price ) ? number_format_i18n( (float) $avg_price, 2 ) : sanitize_text_field( $avg_price ) ) : '';
        $location_parts     = array_filter( array( $city, $region, $postal, $country ) );
        $current_has_coords = is_numeric( $latitude ) && is_numeric( $longitude );

        $all_hotels = get_posts(
            array(
                'post_type'      => 'lbhotel_hotel',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids',
            )
        );

        $hotels_data = array();

        foreach ( $all_hotels as $hotel_post_id ) {
            $lat = get_post_meta( $hotel_post_id, 'lbhotel_latitude', true );
            $lng = get_post_meta( $hotel_post_id, 'lbhotel_longitude', true );

            if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
                continue;
            }

            $hotels_data[] = array(
                'id'    => $hotel_post_id,
                'title' => esc_html( get_the_title( $hotel_post_id ) ),
                'lat'   => (float) $lat,
                'lng'   => (float) $lng,
                'stars' => (int) get_post_meta( $hotel_post_id, 'lbhotel_star_rating', true ),
            );
        }

        $map_payload = array(
            'defaultCenter' => array(
                'lat' => 31.7917,
                'lng' => -7.0926,
            ),
            'currentHotel'  => array(
                'id'    => $hotel_id,
                'lat'   => $current_has_coords ? (float) $latitude : null,
                'lng'   => $current_has_coords ? (float) $longitude : null,
            ),
            'hotels'        => $hotels_data,
        );

        $map_script = 'document.addEventListener("DOMContentLoaded", function() {
            if ( typeof L === "undefined" ) {
                return;
            }
            var data = ' . wp_json_encode( $map_payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) . ';
            var mapEl = document.getElementById("hotel-map");
            if ( ! mapEl ) {
                return;
            }
            var center = [data.defaultCenter.lat, data.defaultCenter.lng];
            var zoom = 6;
            if ( data.currentHotel && data.currentHotel.lat && data.currentHotel.lng ) {
                center = [data.currentHotel.lat, data.currentHotel.lng];
                zoom = 13;
            } else if ( data.hotels.length ) {
                var first = data.hotels[0];
                center = [first.lat, first.lng];
            }
            var map = L.map("hotel-map").setView(center, zoom);
            var osmLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors"
            });
            osmLayer.addTo(map);
            var baseLayers = {
                "OpenStreetMap": osmLayer
            };
            if ( L.esri && L.esri.basemapLayer ) {
                baseLayers["Satellite"] = L.esri.basemapLayer("Imagery");
            }
            L.control.layers(baseLayers, null, { position: "topright" }).addTo(map);
            data.hotels.forEach(function(hotel) {
                if ( !hotel.lat || !hotel.lng ) {
                    return;
                }
                var marker = L.marker([hotel.lat, hotel.lng]).addTo(map);
                var stars = "";
                if ( hotel.stars ) {
                    stars = "<span class=\"hotel-map-stars\">" + "★".repeat(Math.max(0, Math.min(5, hotel.stars))) + "</span>";
                }
                marker.bindPopup("<strong>" + hotel.title + "</strong><br>" + stars);
                if ( hotel.id === data.currentHotel.id ) {
                    marker.openPopup();
                }
            });
        });';

        static $map_inline_added = false;

        if ( ! $map_inline_added ) {
            wp_add_inline_script( 'leaflet', $map_script );
            $map_inline_added = true;
        }
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'hotel-single' ); ?>>
            <header class="hotel-hero">
                <h1 class="hotel-title"><?php the_title(); ?></h1>
                <?php if ( $star_rating > 0 ) : ?>
                    <div class="hotel-star-rating" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                        <?php echo wp_kses_post( str_repeat( '<span class="hotel-star">&#9733;</span>', $star_rating ) ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $location_parts ) : ?>
                    <p class="hotel-location"><?php echo esc_html( implode( ', ', $location_parts ) ); ?></p>
                <?php endif; ?>
            </header>

            <section class="hotel-meta-grid">
                <?php if ( $rooms_total ) : ?>
                    <div class="hotel-meta-card">
                        <span class="meta-label"><?php esc_html_e( 'Rooms Total', 'lbhotel' ); ?></span>
                        <span class="meta-value"><?php echo esc_html( number_format_i18n( (int) $rooms_total ) ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $checkin ) : ?>
                    <div class="hotel-meta-card">
                        <span class="meta-label"><?php esc_html_e( 'Check-in', 'lbhotel' ); ?></span>
                        <span class="meta-value"><?php echo esc_html( $checkin ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $checkout ) : ?>
                    <div class="hotel-meta-card">
                        <span class="meta-label"><?php esc_html_e( 'Check-out', 'lbhotel' ); ?></span>
                        <span class="meta-value"><?php echo esc_html( $checkout ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $formatted_price ) : ?>
                    <div class="hotel-meta-card">
                        <span class="meta-label"><?php esc_html_e( 'Avg. Price / Night', 'lbhotel' ); ?></span>
                        <span class="meta-value"><?php echo esc_html( $formatted_price ); ?></span>
                    </div>
                <?php endif; ?>
            </section>

            <div class="hotel-content entry-content">
                <?php the_content(); ?>
            </div>

            <?php if ( ! empty( $gallery_ids ) ) : ?>
                <section class="hotel-gallery" aria-label="<?php esc_attr_e( 'Hotel gallery', 'lbhotel' ); ?>">
                    <h2 class="section-title"><?php esc_html_e( 'Gallery', 'lbhotel' ); ?></h2>
                    <div class="hotel-gallery-grid">
                        <?php foreach ( $gallery_ids as $attachment_id ) :
                            $image_html = wp_get_attachment_image( $attachment_id, 'large', false, array( 'class' => 'hotel-gallery-image' ) );
                            if ( $image_html ) :
                                ?>
                                <figure class="hotel-gallery-item"><?php echo wp_kses_post( $image_html ); ?></figure>
                            <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $virtual_tour ) : ?>
                <section class="hotel-virtual-tour">
                    <h2 class="section-title"><?php esc_html_e( 'Virtual Tour', 'lbhotel' ); ?></h2>
                    <div class="virtual-tour-frame">
                        <iframe src="<?php echo esc_url( $virtual_tour ); ?>" loading="lazy" allowfullscreen title="<?php echo esc_attr( get_the_title() ); ?>"></iframe>
                    </div>
                </section>
            <?php endif; ?>

            <section class="hotel-map-section">
                <h2 class="section-title"><?php esc_html_e( 'Explore on the Map', 'lbhotel' ); ?></h2>
                <div id="hotel-map" class="hotel-map" role="application" aria-label="<?php esc_attr_e( 'Hotel locations map', 'lbhotel' ); ?>"></div>
            </section>

            <section class="hotel-booking">
                <h2 class="section-title"><?php esc_html_e( 'Reservations', 'lbhotel' ); ?></h2>
                <div class="booking-frame">
                    <?php if ( $booking_url ) : ?>
                        <iframe src="<?php echo esc_url( $booking_url ); ?>" loading="lazy" title="<?php esc_attr_e( 'Hotel booking form', 'lbhotel' ); ?>"></iframe>
                    <?php else : ?>
                        <div class="booking-placeholder"><?php esc_html_e( 'Reservation coming soon', 'lbhotel' ); ?></div>
                    <?php endif; ?>
                </div>
            </section>
        </article>

    <?php endwhile; ?>
</div>

<?php
get_footer();
