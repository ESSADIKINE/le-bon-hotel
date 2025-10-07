<?php
/**
 * Archive template for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$context = lbhotel_get_template_context();
if ( ! $context || 'archive' !== $context['type'] ) {
    $context = lbhotel_set_template_context(
        lbhotel_get_request_template_context() ?: array(
            'category' => lbhotel_get_default_category_slug(),
            'type'     => 'archive',
        )
    );
}

$category_slug    = $context['category'];
$category_labels  = lbhotel_get_place_category_labels();
$category_config  = lbhotel_get_category_display_config();
$category_display = isset( $category_config[ $category_slug ] ) ? $category_config[ $category_slug ] : array();
$descriptions     = lbhotel_get_place_category_descriptions();
$default_title    = isset( $category_labels[ $category_slug ] ) ? $category_labels[ $category_slug ] : post_type_archive_title( '', false );
$default_message  = __( 'Browse immersive experiences and essential details for destinations across Morocco.', 'lbhotel' );
$archive_title    = $default_title;
$archive_intro    = $default_message;
$category_term    = is_tax( 'lbhotel_place_category' ) ? get_queried_object() : null;

if ( $category_term instanceof WP_Term ) {
    $archive_title = $category_term->name;

    $term_description = term_description( $category_term->term_id, 'lbhotel_place_category' );
    if ( ! empty( $term_description ) ) {
        $archive_intro = $term_description;
    } elseif ( isset( $descriptions[ $category_term->slug ] ) ) {
        $archive_intro = $descriptions[ $category_term->slug ];
    }
} elseif ( isset( $descriptions[ $category_slug ] ) ) {
    $archive_intro = $descriptions[ $category_slug ];
}

$archive_intro_markup = wpautop( $archive_intro );

wp_enqueue_style( 'leaflet' );

$per_page_options    = array( 5, 10, 20, 50 );
$default_per_page    = 10;
$requested_per_page  = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page            = in_array( $requested_per_page, $per_page_options, true ) ? $requested_per_page : $default_per_page;
$paged               = max(
    1,
    get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 )
);

$query_args = array(
    'post_type'      => 'lbhotel_hotel',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

if ( $category_slug ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'lbhotel_place_category',
            'field'    => 'slug',
            'terms'    => $category_slug,
        ),
    );
}

$places_query   = new WP_Query( $query_args );
$places_count   = (int) $places_query->found_posts;
$places_payload = array();

$results_label = isset( $category_labels[ $category_slug ] ) ? $category_labels[ $category_slug ] : __( 'Places', 'lbhotel' );
$results_label = trim( wp_strip_all_tags( $results_label ) );

if ( '' === $results_label ) {
    $results_label = __( 'Places', 'lbhotel' );
}

$results_label_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $results_label, 'UTF-8' ) : strtolower( $results_label );

get_header();

?>
<div id="content" class="site-content">
    <div class="ast-container">
        <?php if ( function_exists( 'astra_primary_content_top' ) ) { astra_primary_content_top(); } ?>
        <div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area"'; } ?>>
            <main id="main" class="site-main lbhotel-all-hotels" data-all-hotels-page role="main">
                <?php if ( function_exists( 'astra_primary_content_before' ) ) { astra_primary_content_before(); } ?>

                <header class="lbhotel-archive__header">
                    <h1 class="lbhotel-archive__title"><?php echo esc_html( $archive_title ); ?></h1>
                    <?php if ( $archive_intro_markup ) : ?>
                        <div class="lbhotel-archive__intro"><?php echo wp_kses_post( $archive_intro_markup ); ?></div>
                    <?php endif; ?>
                </header>

                <div class="all-hotels" data-hotels-container>
                    <header class="all-hotels__filters" role="banner">
                        <form class="all-hotels__filters-form" aria-label="<?php esc_attr_e( 'Filter places', 'lbhotel' ); ?>">
                            <label class="all-hotels__field" for="hotel-search">
                                <span class="screen-reader-text"><?php esc_html_e( 'Search by name or city', 'lbhotel' ); ?></span>
                                <input type="search" id="hotel-search" name="hotel_search" placeholder="<?php esc_attr_e( 'Search listings or cities', 'lbhotel' ); ?>" autocomplete="off" />
                            </label>
                            <label class="all-hotels__field" for="hotel-distance">
                                <span class="screen-reader-text"><?php esc_html_e( 'Filter by distance', 'lbhotel' ); ?></span>
                                <select id="hotel-distance" name="hotel_distance">
                                    <option value="all"><?php esc_html_e( 'Any distance', 'lbhotel' ); ?></option>
                                    <option value="5"><?php esc_html_e( 'Near me · 5 km', 'lbhotel' ); ?></option>
                                    <option value="10"><?php esc_html_e( 'Near me · 10 km', 'lbhotel' ); ?></option>
                                    <option value="20"><?php esc_html_e( 'Near me · 20 km', 'lbhotel' ); ?></option>
                                </select>
                            </label>
                            <label class="all-hotels__field" for="hotel-rating">
                                <span class="screen-reader-text"><?php esc_html_e( 'Filter by star rating', 'lbhotel' ); ?></span>
                                <select id="hotel-rating" name="hotel_rating">
                                    <option value="all"><?php esc_html_e( 'Any rating', 'lbhotel' ); ?></option>
                                    <option value="5">5 ★</option>
                                    <option value="4">4 ★ &amp; up</option>
                                    <option value="3">3 ★ &amp; up</option>
                                    <option value="2">2 ★ &amp; up</option>
                                    <option value="1">1 ★ &amp; up</option>
                                </select>
                            </label>
                        </form>
                    </header>

                    <section class="all-hotels__sorting" role="region" aria-live="polite">
                        <div class="all-hotels__results">
                            <span id="hotel-count" data-hotel-count="<?php echo esc_attr( $places_count ); ?>"><?php echo esc_html( number_format_i18n( $places_count ) ); ?></span>
                            <span class="all-hotels__results-label"><?php echo esc_html( sprintf( __( '%s found', 'lbhotel' ), $results_label_lower ) ); ?></span>
                        </div>
                        <label class="all-hotels__sort" for="hotel-sort">
                            <span class="all-hotels__sort-label"><?php esc_html_e( 'Sort by', 'lbhotel' ); ?></span>
                            <select id="hotel-sort" name="hotel_sort">
                                <option value="date-asc"><?php esc_html_e( 'Date ASC', 'lbhotel' ); ?></option>
                                <option value="date-desc" selected><?php esc_html_e( 'Date DESC', 'lbhotel' ); ?></option>
                                <option value="distance-asc"><?php esc_html_e( 'Distance ASC', 'lbhotel' ); ?></option>
                                <option value="distance-desc"><?php esc_html_e( 'Distance DESC', 'lbhotel' ); ?></option>
                                <option value="rating-asc"><?php esc_html_e( 'Rating ASC', 'lbhotel' ); ?></option>
                                <option value="rating-desc"><?php esc_html_e( 'Rating DESC', 'lbhotel' ); ?></option>
                            </select>
                        </label>
                    </section>

                    <?php if ( $places_query->have_posts() ) : ?>
                        <section class="all-hotels__list" id="hotel-list" aria-live="polite" aria-label="<?php esc_attr_e( 'Listing results', 'lbhotel' ); ?>">
                            <?php
                            while ( $places_query->have_posts() ) :
                                $places_query->the_post();

                                $post_id = get_the_ID();

                                $city    = lbhotel_get_meta_with_fallback( $post_id, 'vm_city', '' );
                                $region  = lbhotel_get_meta_with_fallback( $post_id, 'vm_region', '' );
                                $country = lbhotel_get_meta_with_fallback( $post_id, 'vm_country', '' );

                                $location_parts = array_filter( array( $city, $region, $country ) );

                                $latitude_raw  = lbhotel_get_meta_with_fallback( $post_id, 'vm_latitude', '' );
                                $longitude_raw = lbhotel_get_meta_with_fallback( $post_id, 'vm_longitude', '' );
                                $latitude      = is_numeric( $latitude_raw ) ? (float) $latitude_raw : null;
                                $longitude     = is_numeric( $longitude_raw ) ? (float) $longitude_raw : null;

                                $virtual_tour_url = lbhotel_get_meta_with_fallback( $post_id, 'vm_virtual_tour_url', '' );
                                $map_url          = lbhotel_get_meta_with_fallback( $post_id, 'vm_google_map_url', '' );
                                $booking_url      = lbhotel_get_meta_with_fallback( $post_id, 'vm_booking_url', '' );

                                if ( ! $map_url && null !== $latitude && null !== $longitude ) {
                                    $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $latitude . ',' . $longitude ) );
                                }

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
                                $rating_markup = '';

                                if ( $rating_value > 0 ) {
                                    $rating_rounded = max( 0, min( 5, (int) round( $rating_value ) ) );
                                    $stars_markup   = '';

                                    for ( $i = 0; $i < $rating_rounded; $i++ ) {
                                        $stars_markup .= '<span aria-hidden="true">★</span>';
                                    }

                                    $rating_markup  = '<div class="lbhotel-info-card__stars" aria-label="' . esc_attr( sprintf( _n( '%d star', '%d stars', $rating_rounded, 'lbhotel' ), $rating_rounded ) ) . '">';
                                    $rating_markup .= $stars_markup;
                                    $rating_markup .= '<span class="lbhotel-info-card__stars-text">' . esc_html( sprintf( '%d/5', $rating_rounded ) ) . '</span>';
                                    $rating_markup .= '</div>';
                                }

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

                                $meta_rows = array();

                                $price_text = '';

                                if ( 'hotels' === $category_slug ) {
                                    $avg_price = lbhotel_get_meta_with_fallback( $post_id, 'vm_avg_price_per_night', '' );

                                    if ( '' === $avg_price ) {
                                        $avg_price = lbhotel_get_meta_with_fallback( $post_id, 'lbhotel_avg_price_per_night', '' );
                                    }

                                    if ( '' !== $avg_price && null !== $avg_price ) {
                                        $currency_code = lbhotel_get_option( 'default_currency' );
                                        if ( $currency_code ) {
                                            $currency_code = sanitize_text_field( $currency_code );
                                        }
                                        $price_display = is_numeric( $avg_price ) ? number_format_i18n( (float) $avg_price, 2 ) : sanitize_text_field( $avg_price );

                                        if ( $price_display ) {
                                            $price_text = $currency_code ? sprintf( '%s %s', $currency_code, $price_display ) : $price_display;
                                            $meta_rows[] = array(
                                                'type'  => 'price',
                                                'label' => __( 'Average price per night', 'lbhotel' ),
                                                'value' => $price_text,
                                            );
                                        }
                                    }
                                }

                                if ( ! empty( $highlights ) ) {
                                    foreach ( $highlights as $highlight ) {
                                        $meta_rows[] = array_merge(
                                            $highlight,
                                            array( 'type' => 'meta' )
                                        );
                                    }
                                }

                                if ( ! empty( $details ) ) {
                                    foreach ( $details as $detail ) {
                                        $meta_rows[] = array_merge(
                                            $detail,
                                            array( 'type' => 'meta' )
                                        );
                                    }
                                }

                                $action_buttons = array();

                                if ( ! empty( $category_display['actions'] ) ) {
                                    foreach ( $category_display['actions'] as $action ) {
                                        if ( empty( $action['meta'] ) ) {
                                            continue;
                                        }

                                        $url = lbhotel_get_meta_with_fallback( $post_id, $action['meta'], '' );
                                        if ( ! $url ) {
                                            continue;
                                        }

                                        $action_buttons[] = array(
                                            'url'   => esc_url( $url ),
                                            'label' => isset( $action['label'] ) ? $action['label'] : __( 'Learn more', 'lbhotel' ),
                                            'class' => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button lbhotel-button--primary',
                                            'blank' => true,
                                        );
                                    }
                                }

                                $configured_secondary = array();
                                if ( ! empty( $category_display['secondary_actions'] ) ) {
                                    $configured_secondary = (array) $category_display['secondary_actions'];
                                }

                                foreach ( $configured_secondary as $action ) {
                                    if ( empty( $action['meta'] ) ) {
                                        continue;
                                    }

                                    $value = lbhotel_get_meta_with_fallback( $post_id, $action['meta'], '' );
                                    if ( ! $value ) {
                                        continue;
                                    }

                                    $action_buttons[] = array(
                                        'url'   => esc_url( $value ),
                                        'label' => isset( $action['label'] ) ? $action['label'] : __( 'View', 'lbhotel' ),
                                        'class' => isset( $action['class'] ) ? $action['class'] : 'lbhotel-button lbhotel-button--ghost',
                                        'blank' => true,
                                    );
                                }

                                if ( $map_url ) {
                                    $action_buttons[] = array(
                                        'url'   => esc_url( $map_url ),
                                        'label' => __( 'Google Map', 'lbhotel' ),
                                        'class' => 'lbhotel-button lbhotel-button--map',
                                        'blank' => true,
                                    );
                                }

                                $action_buttons[] = array(
                                    'url'   => get_permalink(),
                                    'label' => __( 'View Details', 'lbhotel' ),
                                    'class' => 'lbhotel-button lbhotel-button--details',
                                    'blank' => false,
                                );

                                $place_payload = array(
                                    'id'             => $post_id,
                                    'title'          => get_the_title(),
                                    'lat'            => ( null !== $latitude ) ? $latitude : null,
                                    'lng'            => ( null !== $longitude ) ? $longitude : null,
                                    'city'           => $city,
                                    'region'         => $region,
                                    'country'        => $country,
                                    'stars'          => $rating_value,
                                    'price'          => $price_text,
                                    'bookingUrl'     => $booking_url ? esc_url_raw( $booking_url ) : '',
                                    'permalink'      => get_permalink(),
                                    'images'         => array_slice( $gallery_urls, 0, 6 ),
                                    'virtualTourUrl' => $virtual_tour_url ? esc_url_raw( $virtual_tour_url ) : '',
                                    'mapUrl'         => $map_url ? esc_url_raw( $map_url ) : '',
                                );

                                $places_payload[] = $place_payload;
                                ?>
                                <section class="lbhotel-info-card" aria-labelledby="hotel-<?php the_ID(); ?>-title" data-hotel='<?php echo esc_attr( wp_json_encode( $place_payload ) ); ?>'>
                                    <div class="lbhotel-info-card__media">
                                        <div class="lbhotel-info-card__icons" role="group" aria-label="<?php esc_attr_e( 'Quick actions', 'lbhotel' ); ?>">
                                            <?php if ( $virtual_tour_url ) : ?>
                                                <div class="lbhotel-icon lbhotel-icon--tour" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Virtual Tour', 'lbhotel' ); ?>" data-tour-url="<?php echo esc_url( $virtual_tour_url ); ?>">🎥</div>
                                            <?php endif; ?>
                                            <div class="lbhotel-icon lbhotel-icon--map" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Map View', 'lbhotel' ); ?>">🗺️</div>
                                        </div>
                                        <?php if ( ! empty( $gallery_urls ) ) : ?>
                                            <div class="lbhotel-slider" data-lbhotel-slider>
                                                <div class="lbhotel-slider__track">
                                                    <?php foreach ( array_slice( $gallery_urls, 0, 6 ) as $image_url ) : ?>
                                                        <div class="lbhotel-slider__slide">
                                                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if ( count( $gallery_urls ) > 1 ) : ?>
                                                    <div class="lbhotel-slider__nav lbhotel-slider__nav--prev" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Previous image', 'lbhotel' ); ?>">&#10094;</div>
                                                    <div class="lbhotel-slider__nav lbhotel-slider__nav--next" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Next image', 'lbhotel' ); ?>">&#10095;</div>
                                                    <div class="lbhotel-slider__dots" role="tablist">
                                                        <?php foreach ( array_slice( $gallery_urls, 0, 6 ) as $index => $unused ) : ?>
                                                            <div class="lbhotel-slider__dot" role="tab" tabindex="0" aria-label="<?php echo esc_attr( sprintf( __( 'Go to image %d', 'lbhotel' ), $index + 1 ) ); ?>"></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else : ?>
                                            <div class="lbhotel-slider lbhotel-slider--empty">
                                                <div class="lbhotel-slider__placeholder"><?php esc_html_e( 'Image gallery coming soon.', 'lbhotel' ); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lbhotel-info-card__details">
                                        <h2 id="hotel-<?php the_ID(); ?>-title" class="lbhotel-info-card__title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        <?php if ( $location_parts ) : ?>
                                            <p class="lbhotel-info-card__location"><?php echo esc_html( implode( ', ', $location_parts ) ); ?></p>
                                        <?php endif; ?>

                                        <?php if ( $rating_markup ) : ?>
                                            <?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <?php endif; ?>

                                        <?php foreach ( $meta_rows as $row ) : ?>
                                            <?php if ( 'price' === $row['type'] ) : ?>
                                                <p class="lbhotel-info-card__price"><?php echo esc_html( sprintf( __( '%1$s: %2$s', 'lbhotel' ), $row['label'], $row['value'] ) ); ?></p>
                                            <?php else : ?>
                                                <p class="lbhotel-info-card__meta">
                                                    <?php if ( ! empty( $row['label'] ) ) : ?>
                                                        <span class="lbhotel-info-card__meta-label"><?php echo esc_html( $row['label'] ); ?>:</span>
                                                    <?php endif; ?>
                                                    <span class="lbhotel-info-card__meta-value">
                                                        <?php
                                                        $value = isset( $row['value'] ) ? $row['value'] : '';
                                                        if ( ! empty( $row['multiline'] ) ) {
                                                            echo wp_kses_post( nl2br( esc_html( $value ) ) );
                                                        } else {
                                                            echo esc_html( $value );
                                                        }
                                                        ?>
                                                    </span>
                                                </p>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="lbhotel-info-card__actions">
                                        <?php foreach ( $action_buttons as $button ) : ?>
                                            <a class="<?php echo esc_attr( $button['class'] ); ?>" href="<?php echo esc_url( $button['url'] ); ?>"<?php echo ! empty( $button['blank'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                                <?php echo esc_html( $button['label'] ); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                                <?php
                            endwhile;
                            ?>
                        </section>
                    <?php else : ?>
                        <p class="lbhotel-archive__empty"><?php esc_html_e( 'No listings found at this time. Please check back soon.', 'lbhotel' ); ?></p>
                    <?php endif; ?>
                </div>

                <?php
                wp_reset_postdata();

                $archive_data = array(
                    'hotels'         => array_values( $places_payload ),
                    'fallbackCenter' => array(
                        'lat' => 31.7917,
                        'lng' => -7.0926,
                    ),
                );

                if ( wp_script_is( 'lbhotel-archive-base', 'enqueued' ) || wp_script_is( 'lbhotel-archive-base', 'registered' ) ) {
                    wp_localize_script( 'lbhotel-archive-base', 'lbHotelArchiveData', $archive_data );
                }

                $pagination_add_args = array();
                if ( isset( $_GET['per_page'] ) && $per_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $pagination_add_args['per_page'] = $per_page;
                }

                $pagination_links = paginate_links(
                    array(
                        'total'     => max( 1, (int) $places_query->max_num_pages ),
                        'current'   => $paged,
                        'mid_size'  => 2,
                        'prev_text' => __( 'Previous', 'lbhotel' ),
                        'next_text' => __( 'Next', 'lbhotel' ),
                        'type'      => 'list',
                        'add_args'  => $pagination_add_args ? $pagination_add_args : false,
                    )
                );

                $pagination_markup = $pagination_links;
                if ( empty( $pagination_markup ) ) {
                    $pagination_markup = sprintf(
                        '<ul class="page-numbers"><li><span class="page-numbers current">%s</span></li></ul>',
                        esc_html__( '1', 'lbhotel' )
                    );
                }
                ?>

                <section class="all-hotels__pagination" aria-label="<?php esc_attr_e( 'Listings pagination', 'lbhotel' ); ?>">
                    <form class="all-hotels__pagination-form" method="get">
                        <label for="hotels-per-page" class="all-hotels__pagination-label">
                            <span><?php esc_html_e( 'Listings per page', 'lbhotel' ); ?></span>
                            <select id="hotels-per-page" name="per_page">
                                <?php foreach ( $per_page_options as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $per_page, $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php
                        if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                if ( 'per_page' === $key || 'paged' === $key ) {
                                    continue;
                                }

                                $sanitized_key = sanitize_key( $key );

                                if ( is_string( $value ) ) {
                                    echo '<input type="hidden" name="' . esc_attr( $sanitized_key ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '" />';
                                }
                            }
                        }
                        ?>
                        <noscript>
                            <button type="submit" class="all-hotels__pagination-apply"><?php esc_html_e( 'Apply', 'lbhotel' ); ?></button>
                        </noscript>
                    </form>
                    <nav class="all-hotels__pagination-links" aria-label="<?php esc_attr_e( 'Pagination links', 'lbhotel' ); ?>">
                        <?php echo wp_kses_post( $pagination_markup ); ?>
                    </nav>
                </section>

                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_sidebar_primary' ) ) { astra_sidebar_primary(); } ?>
    </div>
</div>

<?php
get_footer();
