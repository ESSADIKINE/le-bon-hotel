<?php
/**
 * Archive template for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wp_query;

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
$global_secondary = array(
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

get_header();

$paged = max(
    1,
    get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 )
);
?>
<div id="primary" class="content-area lbhotel-archive lbhotel-archive--<?php echo esc_attr( $category_slug ); ?>">
    <main id="main" class="site-main" role="main">
        <header class="lbhotel-archive__header">
            <h1 class="lbhotel-archive__title"><?php echo esc_html( $archive_title ); ?></h1>
            <?php if ( $archive_intro_markup ) : ?>
                <div class="lbhotel-archive__intro"><?php echo wp_kses_post( $archive_intro_markup ); ?></div>
            <?php endif; ?>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="lbhotel-archive__grid">
                <?php
                while ( have_posts() ) :
                    the_post();

                    $post_id  = get_the_ID();
                    $city     = lbhotel_get_meta_with_fallback( $post_id, 'vm_city', '' );
                    $region   = lbhotel_get_meta_with_fallback( $post_id, 'vm_region', '' );
                    $country  = lbhotel_get_meta_with_fallback( $post_id, 'vm_country', '' );
                    $contact  = lbhotel_get_meta_with_fallback( $post_id, 'vm_contact_phone', '' );
                    $rating   = lbhotel_get_rating_value( $post_id );

                    $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
                    $excerpt   = get_the_excerpt();
                    $location  = implode( ', ', array_filter( array( $city, $region, $country ) ) );

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

                    if ( empty( $highlights ) && ! empty( $category_display['details'] ) ) {
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

                            $highlights[] = array(
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

                    foreach ( array_merge( $configured_secondary, $global_secondary ) as $action ) {
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

                    $rating_markup = lbhotel_get_rating_markup( $rating, array(
                        'show_value' => false,
                        'class'      => 'lbhotel-rating lbhotel-rating--compact',
                    ) );
                    ?>
                    <article <?php post_class( 'lbhotel-archive__item' ); ?>>
                        <a class="lbhotel-archive__thumbnail" href="<?php the_permalink(); ?>">
                            <?php if ( $image_url ) : ?>
                                <span class="lbhotel-archive__thumb" style="background-image: url(<?php echo esc_url( $image_url ); ?>);"></span>
                                <span class="screen-reader-text"><?php the_title(); ?></span>
                            <?php else : ?>
                                <span class="lbhotel-archive__placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                        <div class="lbhotel-archive__body">
                            <h2 class="lbhotel-archive__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                            <?php if ( $rating_markup ) : ?>
                                <div class="lbhotel-archive__rating">
                                    <?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $location ) : ?>
                                <p class="lbhotel-archive__location"><?php echo esc_html( $location ); ?></p>
                            <?php endif; ?>

                            <?php if ( $excerpt ) : ?>
                                <p class="lbhotel-archive__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 30 ) ); ?></p>
                            <?php endif; ?>

                            <?php if ( ! empty( $highlights ) ) : ?>
                                <ul class="lbhotel-archive__highlights">
                                    <?php foreach ( $highlights as $highlight ) : ?>
                                        <li>
                                            <?php if ( $highlight['label'] ) : ?>
                                                <span class="lbhotel-archive__highlight-label"><?php echo esc_html( $highlight['label'] ); ?>:</span>
                                            <?php endif; ?>
                                            <span class="lbhotel-archive__highlight-value">
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

                            <div class="lbhotel-archive__actions">
                                <a class="lbhotel-button lbhotel-button--primary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View details', 'lbhotel' ); ?></a>
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

                            <?php if ( $contact ) : ?>
                                <p class="lbhotel-archive__contact"><?php echo esc_html( $contact ); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                endwhile;
                ?>
            </div>

            <?php
            $total_pages = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
            if ( $total_pages > 1 ) :
                ?>
                <nav class="lbhotel-archive__pagination" aria-label="<?php esc_attr_e( 'Places navigation', 'lbhotel' ); ?>">
                    <?php
                    echo paginate_links(
                        array(
                            'total'   => $total_pages,
                            'current' => $paged,
                        )
                    );
                    ?>
                </nav>
            <?php endif; ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No places found at the moment. Please check back soon.', 'lbhotel' ); ?></p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
