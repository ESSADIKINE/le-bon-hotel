<?php
/**
 * Template for [lbhotel_list] shortcode.
 *
 * @var WP_Query $query Query with hotels.
 * @var array    $atts  Shortcode attributes.
 *
 * @package LeBonHotel
 */

if ( ! isset( $query ) || ! $query instanceof WP_Query ) {
    return;
}

$map_data = array();

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        $lat = get_post_meta( get_the_ID(), 'lbhotel_latitude', true );
        $lng = get_post_meta( get_the_ID(), 'lbhotel_longitude', true );

        if ( $lat && $lng ) {
            $map_data[] = array(
                'title'               => get_the_title(),
                'lat'                 => (float) $lat,
                'lng'                 => (float) $lng,
                'star_rating'         => (int) get_post_meta( get_the_ID(), 'lbhotel_star_rating', true ),
                'avg_price_per_night' => get_post_meta( get_the_ID(), 'lbhotel_avg_price_per_night', true ),
                'currency'            => lbhotel_get_option( 'default_currency' ),
                'booking_url'         => get_post_meta( get_the_ID(), 'lbhotel_booking_url', true ),
                'permalink'           => get_permalink(),
                'address'             => get_post_meta( get_the_ID(), 'lbhotel_address', true ),
            );
        }
    }

    // Reset pointer for actual rendering below.
    $query->rewind_posts();
}

$filters_action = esc_url( remove_query_arg( array( 'paged' ) ) );
?>
<div class="lbhotel-shortcode lbhotel-shortcode--list">
    <form class="lbhotel-filters" action="<?php echo $filters_action; ?>" method="get">
        <label>
            <?php esc_html_e( 'City', 'lbhotel' ); ?>
            <input type="text" name="lbhotel_city" value="<?php echo isset( $_GET['lbhotel_city'] ) ? esc_attr( wp_unslash( $_GET['lbhotel_city'] ) ) : esc_attr( $atts['city'] ); ?>" />
        </label>
        <label>
            <?php esc_html_e( 'Stars', 'lbhotel' ); ?>
            <select name="lbhotel_stars">
                <option value=""><?php esc_html_e( 'Any', 'lbhotel' ); ?></option>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( isset( $_GET['lbhotel_stars'] ) ? (int) $_GET['lbhotel_stars'] : (int) $atts['stars'], $i ); ?>><?php echo esc_html( sprintf( _n( '%d Star', '%d Stars', $i, 'lbhotel' ), $i ) ); ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>
            <?php esc_html_e( 'Hotel type', 'lbhotel' ); ?>
            <?php
            wp_dropdown_categories(
                array(
                    'show_option_all' => __( 'Any type', 'lbhotel' ),
                    'taxonomy'        => 'lbhotel_hotel_type',
                    'name'            => 'lbhotel_type',
                    'hide_empty'      => false,
                    'selected'        => isset( $_GET['lbhotel_type'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_type'] ) ) : $atts['hotel_type'],
                    'value_field'     => 'slug',
                )
            );
            ?>
        </label>
        <button type="submit" class="lbhotel-button lbhotel-button--primary"><?php esc_html_e( 'Filter', 'lbhotel' ); ?></button>
    </form>

    <?php if ( ! empty( $map_data ) ) : ?>
        <div class="lbhotel-map" data-hotels='<?php echo esc_attr( wp_json_encode( $map_data ) ); ?>'></div>
    <?php endif; ?>

    <?php if ( $query->have_posts() ) : ?>
        <div class="lbhotel-list">
            <?php
            while ( $query->have_posts() ) {
                $query->the_post();
                $meta = array(
                    'city'                => get_post_meta( get_the_ID(), 'lbhotel_city', true ),
                    'avg_price_per_night' => get_post_meta( get_the_ID(), 'lbhotel_avg_price_per_night', true ),
                    'star_rating'         => (int) get_post_meta( get_the_ID(), 'lbhotel_star_rating', true ),
                );
                $image = get_the_post_thumbnail_url( get_the_ID(), 'large' );
                ?>
                <article class="lbhotel-card">
                    <div class="lbhotel-card__image" style="<?php echo $image ? 'background-image:url(' . esc_url( $image ) . ');' : ''; ?>">
                        <span class="screen-reader-text"><?php the_title(); ?></span>
                    </div>
                    <div class="lbhotel-card__body">
                        <h3 class="lbhotel-card__title"><?php the_title(); ?></h3>
                        <div class="lbhotel-card__meta">
                            <span class="lbhotel-stars"><?php echo str_repeat( '★', $meta['star_rating'] ); ?></span>
                            <span class="lbhotel-price"><?php echo esc_html( lbhotel_get_option( 'default_currency' ) ); ?> <?php echo esc_html( $meta['avg_price_per_night'] ); ?></span>
                        </div>
                        <p><?php echo esc_html( $meta['city'] ); ?></p>
                        <div class="lbhotel-card__actions">
                            <a class="lbhotel-button lbhotel-button--primary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View hotel', 'lbhotel' ); ?></a>
                            <?php $booking = get_post_meta( get_the_ID(), 'lbhotel_booking_url', true ); ?>
                            <?php if ( $booking ) : ?>
                                <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $booking ); ?>" target="_blank" rel="noopener">
                                    <?php esc_html_e( 'Book', 'lbhotel' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php
            }
            ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e( 'No hotels found for your search.', 'lbhotel' ); ?></p>
    <?php endif; ?>
</div>
