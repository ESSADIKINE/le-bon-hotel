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

get_header();

while ( have_posts() ) :
    the_post();

    $post_id      = get_the_ID();
    $categories   = wp_get_post_terms( $post_id, 'lbhotel_place_category' );
    $category_map = array();

    foreach ( $categories as $term ) {
        $category_map[ $term->slug ] = $term->name;
    }

    $location_meta = array(
        'lbhotel_address'         => get_post_meta( $post_id, 'lbhotel_address', true ),
        'lbhotel_city'            => get_post_meta( $post_id, 'lbhotel_city', true ),
        'lbhotel_region'          => get_post_meta( $post_id, 'lbhotel_region', true ),
        'lbhotel_postal_code'     => get_post_meta( $post_id, 'lbhotel_postal_code', true ),
        'lbhotel_country'         => get_post_meta( $post_id, 'lbhotel_country', true ),
        'lbhotel_google_maps_url' => get_post_meta( $post_id, 'lbhotel_google_maps_url', true ),
        'lbhotel_latitude'        => get_post_meta( $post_id, 'lbhotel_latitude', true ),
        'lbhotel_longitude'       => get_post_meta( $post_id, 'lbhotel_longitude', true ),
    );

    $contact_phone    = get_post_meta( $post_id, 'lbhotel_contact_phone', true );
    $virtual_tour_url = get_post_meta( $post_id, 'lbhotel_virtual_tour_url', true );

    $gallery = lbhotel_sanitize_gallery_images( get_post_meta( $post_id, 'lbhotel_gallery_images', true ) );
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single-place ' . $category_class ); ?>>
        <header class="lbhotel-single-place__header">
            <?php if ( $active_label ) : ?>
                <p class="lbhotel-single-place__category-label"><?php echo esc_html( $active_label ); ?></p>
            <?php endif; ?>
            <h1 class="lbhotel-single-place__title"><?php the_title(); ?></h1>
            <?php if ( ! empty( $category_map ) ) : ?>
                <p class="lbhotel-single-place__categories">
                    <?php echo esc_html( implode( ', ', $category_map ) ); ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="lbhotel-single-place__content">
            <div class="lbhotel-single-place__main">
                <div class="lbhotel-single-place__description">
                    <?php the_content(); ?>
                </div>

                <?php if ( $virtual_tour_url ) : ?>
                    <p class="lbhotel-single-place__virtual-tour">
                        <a class="button" href="<?php echo esc_url( $virtual_tour_url ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Explore the virtual tour', 'lbhotel' ); ?>
                        </a>
                    </p>
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
                        <?php if ( $location_meta['lbhotel_address'] ) : ?>
                            <li><?php echo esc_html( $location_meta['lbhotel_address'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( $location_meta['lbhotel_city'] ) : ?>
                            <li><?php echo esc_html( $location_meta['lbhotel_city'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( $location_meta['lbhotel_region'] ) : ?>
                            <li><?php echo esc_html( $location_meta['lbhotel_region'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( $location_meta['lbhotel_postal_code'] ) : ?>
                            <li><?php echo esc_html( $location_meta['lbhotel_postal_code'] ); ?></li>
                        <?php endif; ?>
                        <?php if ( $location_meta['lbhotel_country'] ) : ?>
                            <li><?php echo esc_html( $location_meta['lbhotel_country'] ); ?></li>
                        <?php endif; ?>
                    </ul>
                    <?php if ( $location_meta['lbhotel_google_maps_url'] ) : ?>
                        <p><a href="<?php echo esc_url( $location_meta['lbhotel_google_maps_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View on Google Maps', 'lbhotel' ); ?></a></p>
                    <?php endif; ?>
                    <?php if ( $location_meta['lbhotel_latitude'] || $location_meta['lbhotel_longitude'] ) : ?>
                        <dl class="lbhotel-single-place__coords">
                            <?php if ( $location_meta['lbhotel_latitude'] ) : ?>
                                <dt><?php esc_html_e( 'Latitude', 'lbhotel' ); ?></dt>
                                <dd><?php echo esc_html( $location_meta['lbhotel_latitude'] ); ?></dd>
                            <?php endif; ?>
                            <?php if ( $location_meta['lbhotel_longitude'] ) : ?>
                                <dt><?php esc_html_e( 'Longitude', 'lbhotel' ); ?></dt>
                                <dd><?php echo esc_html( $location_meta['lbhotel_longitude'] ); ?></dd>
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

                <?php foreach ( $category_map as $slug => $label ) :
                    $fields_for_category = lbhotel_get_fields_for_category( $slug );
                    $category_values     = array();

                    foreach ( $fields_for_category as $meta_key => $definition ) {
                        $value = get_post_meta( $post_id, $meta_key, true );

                        if ( '' === $value || empty( $value ) ) {
                            continue;
                        }

                        $category_values[] = array(
                            'label' => $definition['label'],
                            'value' => $value,
                            'input' => isset( $definition['input'] ) ? $definition['input'] : 'text',
                        );
                    }

                    if ( empty( $category_values ) ) {
                        continue;
                    }
                    ?>
                    <section class="lbhotel-single-place__panel">
                        <h2><?php echo esc_html( sprintf( __( '%s details', 'lbhotel' ), $label ) ); ?></h2>
                        <dl class="lbhotel-single-place__details">
                            <?php foreach ( $category_values as $detail ) : ?>
                                <dt><?php echo esc_html( $detail['label'] ); ?></dt>
                                <dd>
                                    <?php
                                    if ( 'url' === $detail['input'] ) {
                                        echo '<a href="' . esc_url( $detail['value'] ) . '" target="_blank" rel="noopener">' . esc_html( $detail['value'] ) . '</a>';
                                    } else {
                                        echo nl2br( esc_html( $detail['value'] ) );
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
    <?php
endwhile;

get_footer();
