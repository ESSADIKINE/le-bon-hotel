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

$default_map_center = array(
    'lat' => 31.7917,
    'lng' => -7.0926,
);

wp_enqueue_style( 'leaflet' );
wp_enqueue_script( 'leaflet' );

get_header();

$script_localized = false;
?>
<div id="primary" class="content-area lbhotel-single-wrapper">
    <main id="main" class="site-main" role="main">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php
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

            $rating_value = lbhotel_get_rating_value( $post_id );

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
                        'url'    => $url,
                        'label'  => isset( $action['label'] ) ? $action['label'] : __( 'Learn more', 'lbhotel' ),
                        'class'  => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button',
                        'target' => '_blank',
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
                    'url'    => $value,
                    'label'  => isset( $action['label'] ) ? $action['label'] : __( 'View', 'lbhotel' ),
                    'class'  => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button lbhotel-button--ghost',
                    'target' => '_blank',
                );
            }

            $info_card_actions = array_merge( $primary_buttons, $secondary_buttons );
            $details_url       = get_permalink();
            $has_details_link  = false;

            foreach ( $info_card_actions as $action ) {
                if ( $action['url'] === $details_url ) {
                    $has_details_link = true;
                    break;
                }
            }

            if ( ! $has_details_link ) {
                $info_card_actions[] = array(
                    'url'    => $details_url,
                    'label'  => __( 'View Details', 'lbhotel' ),
                    'class'  => 'lbhotel-button lbhotel-button--details',
                    'target' => '_self',
                );
            }

            $location_parts = array_filter( array( $address, $city, $region, $postal_code, $country ) );
            $location_line  = implode( ', ', array_filter( array( $city, $region, $country ) ) );

            $summary_text = '';
            if ( ! empty( $highlights ) ) {
                $summary_pieces = array();
                foreach ( array_slice( $highlights, 0, 2 ) as $highlight ) {
                    $value = $highlight['value'];
                    if ( ! empty( $highlight['multiline'] ) ) {
                        $value = preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( array("\r\n", "\r", "\n"), ' ', $value ) ) );
                    }
                    $value = trim( wp_strip_all_tags( $value ) );
                    if ( '' === $value ) {
                        continue;
                    }

                    if ( $highlight['label'] ) {
                        $summary_pieces[] = $highlight['label'] . ': ' . $value;
                    } else {
                        $summary_pieces[] = $value;
                    }
                }

                if ( ! empty( $summary_pieces ) ) {
                    $summary_text = implode( ' • ', $summary_pieces );
                }
            }

            $actions_payload = array_map(
                static function ( $action ) {
                    return array(
                        'label'  => isset( $action['label'] ) ? wp_strip_all_tags( $action['label'] ) : '',
                        'url'    => isset( $action['url'] ) ? esc_url_raw( $action['url'] ) : '',
                        'class'  => isset( $action['class'] ) ? preg_replace( '/[^A-Za-z0-9 _-]/', '', $action['class'] ) : 'lbhotel-button',
                        'target' => isset( $action['target'] ) ? $action['target'] : '_blank',
                    );
                },
                $info_card_actions
            );

            $info_card_payload = array(
                'id'             => $post_id,
                'name'           => get_the_title(),
                'category'       => $active_label,
                'category_slug'  => $category_slug,
                'city'           => $city,
                'region'         => $region,
                'country'        => $country,
                'location'       => $location_line,
                'address'        => $address,
                'summary'        => $summary_text,
                'rating'         => $rating_value,
                'rating_text'    => $rating_value ? number_format_i18n( $rating_value, 1 ) . '/5' : '',
                'mapUrl'         => $map_url,
                'virtualTourUrl' => $virtual_tour_url,
                'permalink'      => $details_url,
                'lat'            => is_numeric( $latitude ) ? (float) $latitude : null,
                'lng'            => is_numeric( $longitude ) ? (float) $longitude : null,
                'images'         => $gallery_urls,
                'actions'        => $actions_payload,
            );

            if ( ! $script_localized ) {
                wp_localize_script(
                    'lbhotel-single-base',
                    'lbHotelSingleData',
                    array(
                        'currentPlace'   => $info_card_payload,
                        'otherPlaces'    => array(),
                        'fallbackCenter' => $default_map_center,
                    )
                );
                $script_localized = true;
            }
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-single-article ' . $category_class ); ?>>
                <div class="lbhotel-single-page">
                    <div class="lbhotel-single-layout">
                        <div class="lbhotel-single-left">
                            <?php if ( $virtual_tour_url ) : ?>
                                <section class="lbhotel-virtual-tour" aria-label="<?php esc_attr_e( 'Virtual tour', 'lbhotel' ); ?>">
                                    <iframe src="<?php echo esc_url( $virtual_tour_url ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" allowfullscreen></iframe>
                                </section>
                            <?php endif; ?>

                            <section class="lbhotel-info-card" aria-label="<?php esc_attr_e( 'Place highlight', 'lbhotel' ); ?>" data-hotel='<?php echo esc_attr( wp_json_encode( $info_card_payload ) ); ?>'>
                                <div class="lbhotel-info-card__media">
                                    <?php if ( $virtual_tour_url || $map_url || ( is_numeric( $latitude ) && is_numeric( $longitude ) ) ) : ?>
                                        <div class="lbhotel-info-card__icons" role="group" aria-label="<?php esc_attr_e( 'Quick actions', 'lbhotel' ); ?>">
                                            <?php if ( $virtual_tour_url ) : ?>
                                                <div class="lbhotel-icon lbhotel-icon--tour" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Virtual tour', 'lbhotel' ); ?>" data-tour-url="<?php echo esc_url( $virtual_tour_url ); ?>">🎥</div>
                                            <?php endif; ?>
                                            <?php if ( $map_url || ( is_numeric( $latitude ) && is_numeric( $longitude ) ) ) : ?>
                                                <div class="lbhotel-icon lbhotel-icon--map" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Map view', 'lbhotel' ); ?>">🗺️</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $gallery_urls ) ) : ?>
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
                                                    <div class="lbhotel-slider__dot" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>"></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="lbhotel-slider lbhotel-slider--empty">
                                            <?php esc_html_e( 'Image gallery coming soon.', 'lbhotel' ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="lbhotel-info-card__details">
                                    <?php if ( $active_label ) : ?>
                                        <p class="lbhotel-info-card__category"><?php echo esc_html( $active_label ); ?></p>
                                    <?php endif; ?>
                                    <h1 class="lbhotel-info-card__title"><?php the_title(); ?></h1>
                                    <?php if ( $location_line ) : ?>
                                        <p class="lbhotel-info-card__location"><?php echo esc_html( $location_line ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( $rating_value > 0 ) : ?>
                                        <div class="lbhotel-info-card__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'lbhotel' ), number_format_i18n( $rating_value, 1 ) ) ); ?>">
                                            <?php
                                            $max_stars = 5;
                                            $filled    = (int) floor( min( $max_stars, max( 0, $rating_value ) ) );
                                            for ( $i = 0; $i < $filled; $i++ ) :
                                                ?>
                                                <span aria-hidden="true">★</span>
                                            <?php endfor; ?>
                                            <span class="lbhotel-info-card__stars-text"><?php echo esc_html( number_format_i18n( $rating_value, 1 ) . '/5' ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $summary_text ) : ?>
                                        <p class="lbhotel-info-card__meta"><?php echo esc_html( $summary_text ); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ( ! empty( $info_card_actions ) ) : ?>
                                    <div class="lbhotel-info-card__actions">
                                        <?php foreach ( $info_card_actions as $button ) : ?>
                                            <?php
                                            $target = isset( $button['target'] ) ? $button['target'] : '_blank';
                                            $rel    = '_self' === $target ? '' : 'noopener noreferrer';
                                            ?>
                                            <a class="<?php echo esc_attr( $button['class'] ); ?>" href="<?php echo esc_url( $button['url'] ); ?>"<?php echo '_self' === $target ? '' : ' target="_blank" rel="' . esc_attr( $rel ) . '"'; ?>>
                                                <?php echo esc_html( $button['label'] ); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>
                        <div class="lbhotel-single-right">
                            <section class="lbhotel-map-section" aria-label="<?php esc_attr_e( 'Interactive map', 'lbhotel' ); ?>">
                                <div id="lbhotel-map" class="lbhotel-map" role="region" aria-label="<?php esc_attr_e( 'Place map', 'lbhotel' ); ?>"></div>
                            </section>
                        </div>
                    </div>

                </div>
            </article>
        <?php endwhile; ?>
    </main>
</div>
<?php
get_footer();
