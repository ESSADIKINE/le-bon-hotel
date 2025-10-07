<?php
/**
 * Shared single template for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$context = lbhotel_get_template_context();
if ( ! $context || 'single' !== $context['type'] ) {
    $context = lbhotel_set_template_context(
        lbhotel_get_request_template_context() ?: array(
            'category' => lbhotel_get_default_category_slug(),
            'type'     => 'single',
        )
    );
}

$category_slug   = $context['category'];
$category_labels = lbhotel_get_place_category_labels();
$active_label    = isset( $category_labels[ $category_slug ] ) ? $category_labels[ $category_slug ] : '';
$category_class  = 'lbhotel-single-place--' . sanitize_html_class( $category_slug );

$schema_types = array(
    'hotels'                  => 'LodgingBusiness',
    'restaurants'             => 'Restaurant',
    'cultural-events'         => 'Event',
    'recreational-activities' => 'LocalBusiness',
    'shopping'                => 'Store',
    'sports-activities'       => 'SportsActivityLocation',
    'tourist-sites'           => 'TouristAttraction',
);

get_header();

while ( have_posts() ) :
    the_post();

    $post_id    = get_the_ID();
    $categories = wp_get_post_terms( $post_id, 'lbhotel_place_category' );

    $category_map = array();
    foreach ( $categories as $term ) {
        $category_map[ $term->slug ] = $term->name;
    }

    $location_meta = array(
        'street'  => lbhotel_get_place_meta( $post_id, 'vm_street_address' ),
        'city'    => lbhotel_get_place_meta( $post_id, 'vm_city' ),
        'region'  => lbhotel_get_place_meta( $post_id, 'vm_region' ),
        'postal'  => lbhotel_get_place_meta( $post_id, 'vm_postal_code' ),
        'country' => lbhotel_get_place_meta( $post_id, 'vm_country' ),
        'lat'     => lbhotel_get_place_meta( $post_id, 'vm_latitude' ),
        'lng'     => lbhotel_get_place_meta( $post_id, 'vm_longitude' ),
    );

    $map_url         = lbhotel_get_place_meta( $post_id, 'vm_google_map_url' );
    $virtual_tour    = lbhotel_get_place_meta( $post_id, 'vm_virtual_tour_url' );
    $contact_phone   = lbhotel_get_place_meta( $post_id, 'vm_contact_phone' );
    $raw_gallery     = lbhotel_get_place_meta( $post_id, 'vm_gallery', array() );
    $gallery         = lbhotel_sanitize_gallery_images( $raw_gallery );
    $rating_value    = lbhotel_get_place_meta( $post_id, 'vm_rating' );
    $has_rating      = '' !== $rating_value && null !== $rating_value;
    $rating_markup   = $has_rating ? lbhotel_render_rating_stars( (float) $rating_value ) : '';
    $permalink       = get_permalink( $post_id );
    $primary_excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 55 );

    if ( ! $map_url && $location_meta['lat'] && $location_meta['lng'] ) {
        $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $location_meta['lat'] . ',' . $location_meta['lng'] ) );
    }

    $quick_actions = array();
    if ( $virtual_tour ) {
        $quick_actions['tour'] = array(
            'label' => __( 'Virtual tour', 'lbhotel' ),
            'url'   => $virtual_tour,
            'icon'  => '🎥',
        );
    }
    if ( $map_url ) {
        $quick_actions['map'] = array(
            'label' => __( 'Open map', 'lbhotel' ),
            'url'   => $map_url,
            'icon'  => '🗺️',
        );
    }

    $category_details = array();
    foreach ( $category_map as $slug => $label ) {
        $fields_for_category = lbhotel_get_fields_for_category( $slug );
        foreach ( $fields_for_category as $meta_key => $definition ) {
            $value = lbhotel_get_place_meta( $post_id, $meta_key );

            if ( '' === $value || null === $value || ( is_array( $value ) && empty( $value ) ) ) {
                continue;
            }

            if ( 'vm_booking_url' === $meta_key ) {
                $quick_actions['booking'] = array(
                    'label' => __( 'Book now', 'lbhotel' ),
                    'url'   => $value,
                    'icon'  => '🛎️',
                );
                continue;
            }

            $category_details[ $slug ]['label'] = $label;
            $category_details[ $slug ]['items'][] = array(
                'label' => $definition['label'],
                'value' => $value,
                'input' => isset( $definition['input'] ) ? $definition['input'] : 'text',
            );
        }
    }

    $schema_type = isset( $schema_types[ $category_slug ] ) ? $schema_types[ $category_slug ] : 'Place';

    $json_ld = array(
        '@context' => 'https://schema.org',
        '@type'    => $schema_type,
        'name'     => get_the_title(),
        'url'      => $permalink,
    );

    if ( $primary_excerpt ) {
        $json_ld['description'] = wp_strip_all_tags( $primary_excerpt );
    }

    if ( $contact_phone ) {
        $json_ld['telephone'] = $contact_phone;
    }

    if ( $gallery ) {
        $images = array();
        foreach ( $gallery as $image_id ) {
            $image_url = wp_get_attachment_url( $image_id );
            if ( $image_url ) {
                $images[] = $image_url;
            }
        }
        if ( $images ) {
            $json_ld['image'] = $images;
        }
    }

    if ( $has_rating ) {
        $json_ld['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format( lbhotel_sanitize_rating( $rating_value ), 1 ),
            'reviewCount' => 1,
        );
    }

    if ( $location_meta['lat'] && $location_meta['lng'] ) {
        $json_ld['geo'] = array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $location_meta['lat'],
            'longitude' => (float) $location_meta['lng'],
        );
    }

    $address_parts = array_filter(
        array(
            'streetAddress'   => $location_meta['street'],
            'addressLocality' => $location_meta['city'],
            'addressRegion'   => $location_meta['region'],
            'postalCode'      => $location_meta['postal'],
            'addressCountry'  => $location_meta['country'],
        )
    );

    if ( $address_parts ) {
        $json_ld['address'] = array_merge( array( '@type' => 'PostalAddress' ), $address_parts );
    }

    if ( $map_url ) {
        $json_ld['hasMap'] = $map_url;
    }

    if ( 'Event' === $schema_type ) {
        $event_date = lbhotel_get_place_meta( $post_id, 'vm_event_datetime' );
        if ( $event_date ) {
            $json_ld['startDate'] = $event_date;
        }
    }
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single-place ' . $category_class ); ?>>
        <header class="lbhotel-single-place__header">
            <?php if ( $active_label ) : ?>
                <p class="lbhotel-single-place__category-label"><?php echo esc_html( $active_label ); ?></p>
            <?php endif; ?>
            <h1 class="lbhotel-single-place__title"><?php the_title(); ?></h1>
            <?php if ( $rating_markup ) : ?>
                <div class="lbhotel-single-place__rating"><?php echo wp_kses_post( $rating_markup ); ?></div>
            <?php endif; ?>
            <?php if ( ! empty( $category_map ) ) : ?>
                <p class="lbhotel-single-place__categories"><?php echo esc_html( implode( ', ', $category_map ) ); ?></p>
            <?php endif; ?>
            <?php if ( $quick_actions ) : ?>
                <div class="lbhotel-single-place__actions" role="group" aria-label="<?php esc_attr_e( 'Quick actions', 'lbhotel' ); ?>">
                    <?php foreach ( $quick_actions as $action ) : ?>
                        <a class="lbhotel-single-place__action" href="<?php echo esc_url( $action['url'] ); ?>" target="_blank" rel="noopener">
                            <span aria-hidden="true"><?php echo esc_html( $action['icon'] ); ?></span>
                            <span class="screen-reader-text"><?php echo esc_html( $action['label'] ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="lbhotel-single-place__content">
            <div class="lbhotel-single-place__main">
                <?php if ( has_post_thumbnail() ) : ?>
                    <figure class="lbhotel-single-place__hero">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </figure>
                <?php endif; ?>

                <div class="lbhotel-single-place__description">
                    <?php the_content(); ?>
                </div>

                <?php if ( $virtual_tour ) : ?>
                    <section class="lbhotel-single-place__panel lbhotel-single-place__panel--tour" aria-label="<?php esc_attr_e( 'Virtual tour', 'lbhotel' ); ?>">
                        <h2><?php esc_html_e( 'Virtual tour', 'lbhotel' ); ?></h2>
                        <p><a class="button" href="<?php echo esc_url( $virtual_tour ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open virtual tour', 'lbhotel' ); ?></a></p>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $gallery ) ) : ?>
                    <section class="lbhotel-single-place__gallery" aria-label="<?php esc_attr_e( 'Gallery images', 'lbhotel' ); ?>">
                        <h2><?php esc_html_e( 'Gallery', 'lbhotel' ); ?></h2>
                        <div class="lbhotel-gallery-grid">
                            <?php foreach ( $gallery as $image_id ) : ?>
                                <figure class="lbhotel-gallery-grid__item">
                                    <?php echo wp_get_attachment_image( $image_id, 'large' ); ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="lbhotel-single-place__sidebar" aria-label="<?php esc_attr_e( 'Place details', 'lbhotel' ); ?>">
                <section class="lbhotel-single-place__panel">
                    <h2><?php esc_html_e( 'Location', 'lbhotel' ); ?></h2>
                    <ul class="lbhotel-single-place__list">
                        <?php foreach ( array( 'street', 'city', 'region', 'postal', 'country' ) as $field ) :
                            if ( empty( $location_meta[ $field ] ) ) {
                                continue;
                            }
                            ?>
                            <li><?php echo esc_html( $location_meta[ $field ] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ( $map_url ) : ?>
                        <p><a href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View on Google Maps', 'lbhotel' ); ?></a></p>
                    <?php endif; ?>
                    <?php if ( $location_meta['lat'] || $location_meta['lng'] ) : ?>
                        <dl class="lbhotel-single-place__coords">
                            <?php if ( $location_meta['lat'] ) : ?>
                                <dt><?php esc_html_e( 'Latitude', 'lbhotel' ); ?></dt>
                                <dd><?php echo esc_html( $location_meta['lat'] ); ?></dd>
                            <?php endif; ?>
                            <?php if ( $location_meta['lng'] ) : ?>
                                <dt><?php esc_html_e( 'Longitude', 'lbhotel' ); ?></dt>
                                <dd><?php echo esc_html( $location_meta['lng'] ); ?></dd>
                            <?php endif; ?>
                        </dl>
                    <?php endif; ?>
                </section>

                <?php if ( $contact_phone ) : ?>
                    <section class="lbhotel-single-place__panel">
                        <h2><?php esc_html_e( 'Contact', 'lbhotel' ); ?></h2>
                        <p class="lbhotel-single-place__contact"><?php echo esc_html( $contact_phone ); ?></p>
                    </section>
                <?php endif; ?>

                <?php foreach ( $category_details as $detail ) :
                    if ( empty( $detail['items'] ) ) {
                        continue;
                    }
                    ?>
                    <section class="lbhotel-single-place__panel">
                        <h2><?php echo esc_html( sprintf( __( '%s details', 'lbhotel' ), $detail['label'] ) ); ?></h2>
                        <dl class="lbhotel-single-place__details">
                            <?php foreach ( $detail['items'] as $item ) : ?>
                                <dt><?php echo esc_html( $item['label'] ); ?></dt>
                                <dd>
                                    <?php
                                    if ( 'url' === $item['input'] ) {
                                        echo '<a href="' . esc_url( $item['value'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['value'] ) . '</a>';
                                    } else {
                                        echo nl2br( esc_html( $item['value'] ) );
                                    }
                                    ?>
                                </dd>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endforeach; ?>
            </aside>
        </div>
    </article>

    <script type="application/ld+json">
        <?php echo wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
    </script>
    <?php
endwhile;

get_footer();
