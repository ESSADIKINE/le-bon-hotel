<?php
/**
 * Template for [lbhotel_single] shortcode.
 *
 * @var WP_Post $post The hotel post.
 *
 * @package LeBonHotel
 */

if ( ! isset( $post ) || ! $post instanceof WP_Post ) {
    return;
}

$meta = array(
    'address'             => get_post_meta( $post->ID, 'lbhotel_address', true ),
    'city'                => get_post_meta( $post->ID, 'lbhotel_city', true ),
    'region'              => get_post_meta( $post->ID, 'lbhotel_region', true ),
    'postal_code'         => get_post_meta( $post->ID, 'lbhotel_postal_code', true ),
    'country'             => get_post_meta( $post->ID, 'lbhotel_country', true ),
    'checkin_time'        => get_post_meta( $post->ID, 'lbhotel_checkin_time', true ),
    'checkout_time'       => get_post_meta( $post->ID, 'lbhotel_checkout_time', true ),
    'rooms_total'         => get_post_meta( $post->ID, 'lbhotel_rooms_total', true ),
    'avg_price_per_night' => get_post_meta( $post->ID, 'lbhotel_avg_price_per_night', true ),
    'has_free_breakfast'  => (bool) get_post_meta( $post->ID, 'lbhotel_has_free_breakfast', true ),
    'has_parking'         => (bool) get_post_meta( $post->ID, 'lbhotel_has_parking', true ),
    'star_rating'         => (int) get_post_meta( $post->ID, 'lbhotel_star_rating', true ),
    'virtual_tour_url'    => get_post_meta( $post->ID, 'lbhotel_virtual_tour_url', true ),
    'contact_phone'       => get_post_meta( $post->ID, 'lbhotel_contact_phone', true ),
    'booking_url'         => get_post_meta( $post->ID, 'lbhotel_booking_url', true ),
);

$rooms = get_post_meta( $post->ID, 'lbhotel_rooms', true );
$rooms = is_array( $rooms ) ? $rooms : array();

$gallery = get_post_meta( $post->ID, 'lbhotel_gallery_images', true );
$gallery = is_array( $gallery ) ? $gallery : array();

$currency = lbhotel_get_option( 'default_currency' );
?>
<section class="lbhotel-single" id="hotel-<?php echo esc_attr( $post->ID ); ?>">
    <header>
        <h2><?php echo esc_html( get_the_title( $post ) ); ?></h2>
        <p class="lbhotel-card__meta">
            <span class="lbhotel-stars"><?php echo str_repeat( '★', $meta['star_rating'] ); ?></span>
            <span class="lbhotel-price"><?php echo esc_html( $currency ); ?> <?php echo esc_html( $meta['avg_price_per_night'] ); ?> / <?php esc_html_e( 'night', 'lbhotel' ); ?></span>
        </p>
        <p class="lbhotel-address"><?php echo esc_html( trim( implode( ', ', array_filter( array( $meta['address'], $meta['city'], $meta['region'], $meta['postal_code'], $meta['country'] ) ) ) ) ); ?></p>
    </header>

    <?php if ( has_post_thumbnail( $post ) ) : ?>
        <div class="lbhotel-hero">
            <?php echo get_the_post_thumbnail( $post, 'large' ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $gallery ) ) : ?>
        <div class="lbhotel-single-gallery">
            <?php foreach ( $gallery as $image_id ) : ?>
                <?php echo wp_get_attachment_image( $image_id, 'medium_large' ); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="lbhotel-amenities">
        <?php if ( $meta['has_free_breakfast'] ) : ?>
            <span class="lbhotel-amenity"><span class="lbhotel-icon-breakfast" aria-hidden="true"></span><?php esc_html_e( 'Free breakfast', 'lbhotel' ); ?></span>
        <?php endif; ?>
        <?php if ( $meta['has_parking'] ) : ?>
            <span class="lbhotel-amenity"><span class="lbhotel-icon-parking" aria-hidden="true"></span><?php esc_html_e( 'Parking available', 'lbhotel' ); ?></span>
        <?php endif; ?>
        <?php if ( $meta['rooms_total'] ) : ?>
            <span class="lbhotel-amenity"><span class="lbhotel-icon-bed" aria-hidden="true"></span><?php echo esc_html( sprintf( _n( '%d room', '%d rooms', (int) $meta['rooms_total'], 'lbhotel' ), (int) $meta['rooms_total'] ) ); ?></span>
        <?php endif; ?>
    </div>

    <div class="lbhotel-overview">
        <?php echo wpautop( wp_kses_post( $post->post_content ) ); ?>
    </div>

    <div class="lbhotel-info-grid">
        <div>
            <h3><?php esc_html_e( 'Check-in / Check-out', 'lbhotel' ); ?></h3>
            <p><?php esc_html_e( 'Check-in', 'lbhotel' ); ?>: <?php echo esc_html( $meta['checkin_time'] ?: lbhotel_get_option( 'default_checkin_time' ) ); ?><br />
            <?php esc_html_e( 'Check-out', 'lbhotel' ); ?>: <?php echo esc_html( $meta['checkout_time'] ?: lbhotel_get_option( 'default_checkout_time' ) ); ?></p>
        </div>
        <div>
            <h3><?php esc_html_e( 'Contact', 'lbhotel' ); ?></h3>
            <?php if ( $meta['contact_phone'] ) : ?>
                <p><?php esc_html_e( 'Phone', 'lbhotel' ); ?>: <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $meta['contact_phone'] ) ); ?>"><?php echo esc_html( $meta['contact_phone'] ); ?></a></p>
            <?php endif; ?>
            <?php if ( $meta['virtual_tour_url'] ) : ?>
                <p><a href="<?php echo esc_url( $meta['virtual_tour_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Virtual tour', 'lbhotel' ); ?></a></p>
            <?php endif; ?>
        </div>
        <?php if ( $meta['booking_url'] ) : ?>
            <div>
                <h3><?php esc_html_e( 'Booking', 'lbhotel' ); ?></h3>
                <a class="lbhotel-button lbhotel-button--primary" href="<?php echo esc_url( $meta['booking_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Book this hotel', 'lbhotel' ); ?></a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $rooms ) ) : ?>
        <table class="lbhotel-room-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Room', 'lbhotel' ); ?></th>
                    <th><?php esc_html_e( 'Capacity', 'lbhotel' ); ?></th>
                    <th><?php esc_html_e( 'Price per night', 'lbhotel' ); ?></th>
                    <th><?php esc_html_e( 'Availability', 'lbhotel' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rooms as $room ) : ?>
                    <tr>
                        <td><?php echo esc_html( $room['name'] ); ?></td>
                        <td><?php echo esc_html( $room['capacity'] ); ?></td>
                        <td><?php echo esc_html( $currency ); ?> <?php echo esc_html( $room['price'] ); ?></td>
                        <td><?php echo esc_html( $room['availability'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
