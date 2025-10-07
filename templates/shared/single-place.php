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

$category_slug        = $context['category'];
$category_labels      = lbhotel_get_place_category_labels();
$display_config_map   = lbhotel_get_category_display_config();
$active_label         = isset( $category_labels[ $category_slug ] ) ? $category_labels[ $category_slug ] : '';
$category_class       = 'lbhotel-single-place--' . sanitize_html_class( $category_slug );
$category_display     = isset( $display_config_map[ $category_slug ] ) ? $display_config_map[ $category_slug ] : array();
$global_secondary_map = array(
    array(
        'meta'  => 'vm_virtual_tour_url',
        'label' => __( 'Virtual tour', 'lbhotel' ),
        'class' => 'lbhotel-button lbhotel-button--ghost',
    ),
    array(
        'meta'  => 'vm_google_map_url',
        'label' => __( 'Map', 'lbhotel' ),
        'class' => 'lbhotel-button lbhotel-button--ghost',
    ),
);

get_header();

while ( have_posts() ) :
    the_post();

    $post_id      = get_the_ID();
    $categories   = wp_get_post_terms( $post_id, 'lbhotel_place_category' );
    $category_map = array();

    foreach ( $categories as $term ) {
        $category_map[ $term->slug ] = $term->name;
    }

    $address     = lbhotel_get_meta_with_fallback( $post_id, 'vm_street_address', '' );
    $city        = lbhotel_get_meta_with_fallback( $post_id, 'vm_city', '' );
    $region      = lbhotel_get_meta_with_fallback( $post_id, 'vm_region', '' );
    $postal_code = lbhotel_get_meta_with_fallback( $post_id, 'vm_postal_code', '' );
    $country     = lbhotel_get_meta_with_fallback( $post_id, 'vm_country', '' );
    $latitude    = lbhotel_get_meta_with_fallback( $post_id, 'vm_latitude', '' );
    $longitude   = lbhotel_get_meta_with_fallback( $post_id, 'vm_longitude', '' );

    $virtual_tour_url = lbhotel_get_meta_with_fallback( $post_id, 'vm_virtual_tour_url', '' );
    $map_url          = lbhotel_get_meta_with_fallback( $post_id, 'vm_google_map_url', '' );

    if ( ! $map_url && $latitude && $longitude ) {
        $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $latitude . ',' . $longitude ) );
    }

    $contact_phone = lbhotel_get_meta_with_fallback( $post_id, 'vm_contact_phone', '' );

    $gallery_ids = lbhotel_sanitize_gallery_images( get_post_meta( $post_id, 'lbhotel_gallery_images', true ) );

    if ( empty( $gallery_ids ) ) {
        $vm_gallery = get_post_meta( $post_id, 'vm_gallery', true );

        if ( is_array( $vm_gallery ) ) {
            $gallery_ids = array_filter( array_map( 'absint', $vm_gallery ) );
        }
    }

    $gallery_urls = array();

    foreach ( $gallery_ids as $attachment_id ) {
        $image_url = wp_get_attachment_image_url( $attachment_id, 'large' );
        if ( $image_url ) {
            $gallery_urls[] = $image_url;
        }
    }

    if ( empty( $gallery_urls ) && has_post_thumbnail( $post_id ) ) {
        $featured_url = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( $featured_url ) {
            $gallery_urls[] = $featured_url;
        }
    }

    $rating_value  = lbhotel_get_rating_value( $post_id );
    $rating_markup = lbhotel_get_rating_markup( $rating_value );

    $highlights = array();
    if ( ! empty( $category_display['highlights'] ) && is_array( $category_display['highlights'] ) ) {
        foreach ( $category_display['highlights'] as $meta_key => $definition ) {
            $value = lbhotel_get_meta_with_fallback( $post_id, $meta_key, '' );

            if ( '' === $value ) {
                continue;
            }

            $highlights[] = array(
                'label'     => isset( $definition['label'] ) ? $definition['label'] : '',
                'value'     => $value,
                'multiline' => ! empty( $definition['multiline'] ),
            );
        }
    }

    $details = array();
    if ( ! empty( $category_display['details'] ) && is_array( $category_display['details'] ) ) {
        foreach ( $category_display['details'] as $meta_key => $definition ) {
            $value = lbhotel_get_meta_with_fallback( $post_id, $meta_key, '' );

            if ( '' === $value ) {
                continue;
            }

            if ( 'vm_event_datetime' === $meta_key ) {
                $timestamp = strtotime( $value );
                if ( $timestamp ) {
                    $value = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
                }
            }

            $details[] = array(
                'label'     => isset( $definition['label'] ) ? $definition['label'] : '',
                'value'     => $value,
                'multiline' => ! empty( $definition['multiline'] ),
            );
        }
    }

    $primary_buttons   = array();
    $secondary_buttons = array();

    if ( ! empty( $category_display['actions'] ) ) {
        foreach ( $category_display['actions'] as $action ) {
            if ( empty( $action['meta'] ) ) {
                continue;
            }

            $url = lbhotel_get_meta_with_fallback( $post_id, $action['meta'], '' );

            if ( ! $url ) {
                continue;
            }

            $primary_buttons[] = array(
                'url'   => esc_url( $url ),
                'label' => isset( $action['label'] ) ? $action['label'] : __( 'Learn more', 'lbhotel' ),
                'class' => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button',
            );
        }
    }

    $configured_secondary = array();
    if ( ! empty( $category_display['secondary_actions'] ) ) {
        $configured_secondary = (array) $category_display['secondary_actions'];
    }

    foreach ( array_merge( $configured_secondary, $global_secondary_map ) as $action ) {
        if ( empty( $action['meta'] ) ) {
            continue;
        }

        $value = lbhotel_get_meta_with_fallback( $post_id, $action['meta'], '' );

        if ( ! $value ) {
            continue;
        }

        $secondary_buttons[] = array(
            'url'   => esc_url( $value ),
            'label' => isset( $action['label'] ) ? $action['label'] : __( 'View', 'lbhotel' ),
            'class' => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button lbhotel-button--ghost',
        );
    }

    $location_parts = array_filter( array( $address, $city, $region, $postal_code, $country ) );
    $location_line  = implode( ', ', array_filter( array( $city, $region, $country ) ) );

    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single-place ' . $category_class ); ?>>
        <header class="lbhotel-single-place__header">
            <?php if ( $active_label ) : ?>
                <p class="lbhotel-single-place__category-label"><?php echo esc_html( $active_label ); ?></p>
            <?php endif; ?>
            <h1 class="lbhotel-single-place__title"><?php the_title(); ?></h1>
            <?php if ( ! empty( $category_map ) ) : ?>
                <p class="lbhotel-single-place__categories"><?php echo esc_html( implode( ', ', $category_map ) ); ?></p>
            <?php endif; ?>
        </header>

        <div class="lbhotel-single-place__layout">
            <section class="lbhotel-single-place__hero" aria-label="<?php esc_attr_e( 'Place highlights', 'lbhotel' ); ?>">
                <div class="lbhotel-single-place__media" data-lbhotel-slider>
                    <?php if ( ! empty( $gallery_urls ) ) : ?>
                        <div class="lbhotel-single-place__slides">
                            <?php foreach ( $gallery_urls as $index => $image_url ) : ?>
                                <figure class="lbhotel-single-place__slide">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
                                </figure>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="lbhotel-single-place__nav lbhotel-single-place__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'lbhotel' ); ?>">&#10094;</button>
                        <button type="button" class="lbhotel-single-place__nav lbhotel-single-place__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'lbhotel' ); ?>">&#10095;</button>
                        <div class="lbhotel-single-place__dots" role="tablist">
                            <?php foreach ( $gallery_urls as $index => $unused ) : ?>
                                <button type="button" class="lbhotel-single-place__dot" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="lbhotel-single-place__placeholder" aria-hidden="true"></div>
                    <?php endif; ?>
                </div>

                <div class="lbhotel-single-place__info">
                    <?php if ( $rating_markup ) : ?>
                        <div class="lbhotel-single-place__rating">
                            <?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $location_line ) : ?>
                        <p class="lbhotel-single-place__location"><?php echo esc_html( $location_line ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $highlights ) ) : ?>
                        <ul class="lbhotel-single-place__highlights">
                            <?php foreach ( $highlights as $highlight ) : ?>
                                <li>
                                    <?php if ( $highlight['label'] ) : ?>
                                        <span class="lbhotel-single-place__highlight-label"><?php echo esc_html( $highlight['label'] ); ?>:</span>
                                    <?php endif; ?>
                                    <span class="lbhotel-single-place__highlight-value">
                                        <?php
                                        if ( ! empty( $highlight['multiline'] ) ) {
                                            echo wp_kses_post( nl2br( esc_html( $highlight['value'] ) ) );
                                        } else {
                                            echo esc_html( $highlight['value'] );
                                        }
                                        ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( ! empty( $primary_buttons ) || ! empty( $secondary_buttons ) ) : ?>
                        <div class="lbhotel-single-place__actions">
                            <?php foreach ( $primary_buttons as $button ) : ?>
                                <a class="<?php echo esc_attr( $button['class'] ); ?>" href="<?php echo esc_url( $button['url'] ); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html( $button['label'] ); ?>
                                </a>
                            <?php endforeach; ?>
                            <?php foreach ( $secondary_buttons as $button ) : ?>
                                <a class="<?php echo esc_attr( $button['class'] ); ?>" href="<?php echo esc_url( $button['url'] ); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html( $button['label'] ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <div class="lbhotel-single-place__main-content">
                <div class="lbhotel-single-place__description">
                    <?php the_content(); ?>
                </div>
            </div>

            <aside class="lbhotel-single-place__sidebar" aria-label="<?php esc_attr_e( 'Place details', 'lbhotel' ); ?>">
                <section class="lbhotel-single-place__panel">
                    <h2><?php esc_html_e( 'Location details', 'lbhotel' ); ?></h2>
                    <?php if ( ! empty( $location_parts ) ) : ?>
                        <ul class="lbhotel-single-place__list">
                            <?php foreach ( $location_parts as $part ) : ?>
                                <li><?php echo esc_html( $part ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ( $map_url ) : ?>
                        <p><a class="lbhotel-button lbhotel-button--ghost" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View on map', 'lbhotel' ); ?></a></p>
                    <?php endif; ?>
                </section>

                <?php if ( $contact_phone ) : ?>
                    <section class="lbhotel-single-place__panel">
                        <h2><?php esc_html_e( 'Contact', 'lbhotel' ); ?></h2>
                        <p class="lbhotel-single-place__contact"><?php echo esc_html( $contact_phone ); ?></p>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $details ) ) : ?>
                    <section class="lbhotel-single-place__panel">
                        <h2><?php esc_html_e( 'Key details', 'lbhotel' ); ?></h2>
                        <dl class="lbhotel-single-place__details">
                            <?php foreach ( $details as $detail ) : ?>
                                <dt><?php echo esc_html( $detail['label'] ); ?></dt>
                                <dd>
                                    <?php
                                    if ( ! empty( $detail['multiline'] ) ) {
                                        echo wp_kses_post( nl2br( esc_html( $detail['value'] ) ) );
                                    } else {
                                        echo esc_html( $detail['value'] );
                                    }
                                    ?>
                                </dd>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </article>
    <?php
endwhile;

get_footer();
