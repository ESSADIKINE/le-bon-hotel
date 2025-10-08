<?php
/**
 * Template for [lbhotel_list] shortcode.
 *
 * @var WP_Query $query Query with hotels.
 * @var array    $atts  Shortcode attributes.
 *
 * @package VirtualMaroc
 */

if ( ! isset( $query ) || ! $query instanceof WP_Query ) {
    return;
}

$map_data           = array();
$hotel_type_options = array();

$category_fields = lbhotel_get_category_field_definitions();
if ( isset( $category_fields['vm_hotel_type']['options'] ) && is_array( $category_fields['vm_hotel_type']['options'] ) ) {
    $hotel_type_options = $category_fields['vm_hotel_type']['options'];
}

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        $post_id   = get_the_ID();
        $latitude  = lbhotel_get_meta_value( $post_id, 'vm_latitude', '' );
        $longitude = lbhotel_get_meta_value( $post_id, 'vm_longitude', '' );

        if ( '' !== $latitude && '' !== $longitude ) {
            $map_data[] = array(
                'title'         => get_the_title(),
                'lat'           => (float) $latitude,
                'lng'           => (float) $longitude,
                'rating'        => (float) lbhotel_get_meta_value( $post_id, 'vm_rating', 0 ),
                'booking_url'   => lbhotel_get_meta_value( $post_id, 'vm_booking_url', '' ),
                'permalink'     => get_permalink(),
                'virtual_tour'  => lbhotel_get_meta_value( $post_id, 'vm_virtual_tour_url', '' ),
                'map_url'       => lbhotel_get_meta_value( $post_id, 'vm_google_map_url', '' ),
                'city'          => lbhotel_get_meta_value( $post_id, 'vm_city', '' ),
                'region'        => lbhotel_get_meta_value( $post_id, 'vm_region', '' ),
                'country'       => lbhotel_get_meta_value( $post_id, 'vm_country', '' ),
                'streetAddress' => lbhotel_get_meta_value( $post_id, 'vm_street_address', '' ),
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
            <?php esc_html_e( 'Rating', 'lbhotel' ); ?>
            <select name="lbhotel_stars">
                <option value=""><?php esc_html_e( 'Any', 'lbhotel' ); ?></option>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( isset( $_GET['lbhotel_stars'] ) ? (int) $_GET['lbhotel_stars'] : (int) $atts['stars'], $i ); ?>><?php echo esc_html( sprintf( _n( '%d star & up', '%d stars & up', $i, 'lbhotel' ), $i ) ); ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <?php if ( ! empty( $hotel_type_options ) ) : ?>
            <label>
                <?php esc_html_e( 'Hotel type', 'lbhotel' ); ?>
                <select name="lbhotel_type">
                    <option value=""><?php esc_html_e( 'Any type', 'lbhotel' ); ?></option>
                    <?php
                    $selected_type = isset( $_GET['lbhotel_type'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_type'] ) ) : $atts['hotel_type'];
                    foreach ( $hotel_type_options as $option_value => $option_label ) :
                        ?>
                        <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $selected_type, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
                        <?php
                    endforeach;
                    ?>
                </select>
            </label>
        <?php endif; ?>
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
                $post_id      = get_the_ID();
                $city         = lbhotel_get_meta_value( $post_id, 'vm_city', '' );
                $region       = lbhotel_get_meta_value( $post_id, 'vm_region', '' );
                $country      = lbhotel_get_meta_value( $post_id, 'vm_country', '' );
                $hotel_type   = lbhotel_get_meta_value( $post_id, 'vm_hotel_type', '' );
                $rating_value = lbhotel_get_meta_value( $post_id, 'vm_rating', '' );
                $virtual_tour = lbhotel_get_meta_value( $post_id, 'vm_virtual_tour_url', '' );
                $map_url      = lbhotel_get_meta_value( $post_id, 'vm_google_map_url', '' );
                $booking_url  = lbhotel_get_meta_value( $post_id, 'vm_booking_url', '' );
                $image        = get_the_post_thumbnail_url( $post_id, 'large' );
                $excerpt      = get_the_excerpt();

                $location_parts = array_filter( array( $city, $region, $country ) );
                $location_line  = implode( ', ', $location_parts );

                $rating_markup = '';

                if ( '' !== $rating_value ) {
                    $numeric_rating  = max( 0, min( 5, (float) $rating_value ) );
                    $rounded_rating  = round( $numeric_rating * 2 ) / 2;
                    $rating_label    = sprintf( __( 'Rated %s out of 5', 'lbhotel' ), number_format_i18n( $rounded_rating, 1 ) );
                    $rating_markup   = sprintf(
                        '<div class="lbhotel-rating" data-lbhotel-rating="%1$s"><span class="lbhotel-rating__stars" aria-hidden="true"></span><span class="screen-reader-text">%2$s</span><span class="lbhotel-rating__value">%3$s</span></div>',
                        esc_attr( $rounded_rating ),
                        esc_html( $rating_label ),
                        esc_html( number_format_i18n( $rounded_rating, 1 ) )
                    );
                }
                ?>
                <article class="lbhotel-card">
                    <div class="lbhotel-card__image" style="<?php echo $image ? 'background-image:url(' . esc_url( $image ) . ');' : ''; ?>">
                        <span class="screen-reader-text"><?php the_title(); ?></span>
                    </div>
                    <div class="lbhotel-card__body">
                        <h3 class="lbhotel-card__title"><?php the_title(); ?></h3>
                        <div class="lbhotel-card__meta">
                            <?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php if ( $hotel_type ) : ?>
                                <span class="lbhotel-card__tag"><?php echo esc_html( $hotel_type ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $location_line ) : ?>
                            <p class="lbhotel-card__location"><?php echo esc_html( $location_line ); ?></p>
                        <?php endif; ?>
                        <?php if ( $excerpt ) : ?>
                            <p class="lbhotel-card__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 30 ) ); ?></p>
                        <?php endif; ?>
                        <div class="lbhotel-card__actions">
                            <a class="lbhotel-button lbhotel-button--primary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View details', 'lbhotel' ); ?></a>
                            <?php if ( $virtual_tour ) : ?>
                                <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $virtual_tour ); ?>" target="_blank" rel="noopener">
                                    <?php esc_html_e( 'Virtual tour', 'lbhotel' ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( $map_url ) : ?>
                                <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener">
                                    <?php esc_html_e( 'Map', 'lbhotel' ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( $booking_url ) : ?>
                                <a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $booking_url ); ?>" target="_blank" rel="noopener">
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
