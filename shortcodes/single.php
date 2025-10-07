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

$post_id = $post->ID;

$meta = array(
    'street_address'   => lbhotel_get_meta_value( $post_id, 'vm_street_address', '' ),
    'city'             => lbhotel_get_meta_value( $post_id, 'vm_city', '' ),
    'region'           => lbhotel_get_meta_value( $post_id, 'vm_region', '' ),
    'postal_code'      => lbhotel_get_meta_value( $post_id, 'vm_postal_code', '' ),
    'country'          => lbhotel_get_meta_value( $post_id, 'vm_country', '' ),
    'virtual_tour_url' => lbhotel_get_meta_value( $post_id, 'vm_virtual_tour_url', '' ),
    'google_map_url'   => lbhotel_get_meta_value( $post_id, 'vm_google_map_url', '' ),
    'contact_phone'    => lbhotel_get_meta_value( $post_id, 'vm_contact_phone', '' ),
    'booking_url'      => lbhotel_get_meta_value( $post_id, 'vm_booking_url', '' ),
    'hotel_type'       => lbhotel_get_meta_value( $post_id, 'vm_hotel_type', '' ),
    'rating'           => lbhotel_get_meta_value( $post_id, 'vm_rating', '' ),
);

$gallery = lbhotel_sanitize_gallery_images( lbhotel_get_meta_value( $post_id, 'vm_gallery', array() ) );

$location_parts = array_filter( array(
    $meta['street_address'],
    $meta['city'],
    $meta['region'],
    $meta['postal_code'],
    $meta['country'],
) );

$location = implode( ', ', $location_parts );

$rating_markup = '';

if ( '' !== $meta['rating'] ) {
    $numeric_rating = max( 0, min( 5, (float) $meta['rating'] ) );
    $rounded_rating = round( $numeric_rating * 2 ) / 2;
    $rating_label   = sprintf( __( 'Rated %s out of 5', 'lbhotel' ), number_format_i18n( $rounded_rating, 1 ) );

    $rating_markup = sprintf(
        '<div class="lbhotel-rating" data-lbhotel-rating="%1$s"><span class="lbhotel-rating__stars" aria-hidden="true"></span><span class="screen-reader-text">%2$s</span><span class="lbhotel-rating__value">%3$s</span></div>',
        esc_attr( $rounded_rating ),
        esc_html( $rating_label ),
        esc_html( number_format_i18n( $rounded_rating, 1 ) )
    );
}
?>
<section class="lbhotel-single" id="hotel-<?php echo esc_attr( $post->ID ); ?>">
    <header class="lbhotel-single__header">
        <h2><?php echo esc_html( get_the_title( $post ) ); ?></h2>
        <?php if ( $rating_markup ) : ?>
            <div class="lbhotel-single__rating">
                <?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
        <?php if ( $meta['hotel_type'] ) : ?>
            <p class="lbhotel-single__tag"><?php echo esc_html( $meta['hotel_type'] ); ?></p>
        <?php endif; ?>
        <?php if ( $location ) : ?>
            <p class="lbhotel-address"><?php echo esc_html( $location ); ?></p>
        <?php endif; ?>
        <?php if ( $post->post_excerpt ) : ?>
            <p class="lbhotel-single__excerpt"><?php echo esc_html( wp_trim_words( $post->post_excerpt, 40 ) ); ?></p>
        <?php endif; ?>
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

    <div class="lbhotel-single__actions">
        <?php if ( $meta['virtual_tour_url'] ) : ?>
            <a class="lbhotel-button lbhotel-button--primary" href="<?php echo esc_url( $meta['virtual_tour_url'] ); ?>" target="_blank" rel="noopener">
                <?php esc_html_e( 'Virtual tour', 'lbhotel' ); ?>
            </a>
        <?php endif; ?>
        <?php if ( $meta['google_map_url'] ) : ?>
            <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $meta['google_map_url'] ); ?>" target="_blank" rel="noopener">
                <?php esc_html_e( 'View on map', 'lbhotel' ); ?>
            </a>
        <?php endif; ?>
        <?php if ( $meta['booking_url'] ) : ?>
            <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $meta['booking_url'] ); ?>" target="_blank" rel="noopener">
                <?php esc_html_e( 'Book now', 'lbhotel' ); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="lbhotel-overview">
        <?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>

    <div class="lbhotel-info-grid">
        <?php if ( $meta['contact_phone'] ) : ?>
            <div>
                <h3><?php esc_html_e( 'Contact', 'lbhotel' ); ?></h3>
                <p><?php esc_html_e( 'Phone', 'lbhotel' ); ?>: <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $meta['contact_phone'] ) ); ?>"><?php echo esc_html( $meta['contact_phone'] ); ?></a></p>
            </div>
        <?php endif; ?>
        <?php if ( $meta['google_map_url'] ) : ?>
            <div>
                <h3><?php esc_html_e( 'Location', 'lbhotel' ); ?></h3>
                <p><a href="<?php echo esc_url( $meta['google_map_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Google Maps', 'lbhotel' ); ?></a></p>
            </div>
        <?php endif; ?>
        <?php if ( $meta['virtual_tour_url'] ) : ?>
            <div>
                <h3><?php esc_html_e( 'Experience', 'lbhotel' ); ?></h3>
                <p><a href="<?php echo esc_url( $meta['virtual_tour_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Launch virtual tour', 'lbhotel' ); ?></a></p>
            </div>
        <?php endif; ?>
    </div>
</section>
