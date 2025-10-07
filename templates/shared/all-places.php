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
$descriptions     = lbhotel_get_place_category_descriptions();
$archive_title    = isset( $category_labels[ $category_slug ] ) ? $category_labels[ $category_slug ] : post_type_archive_title( '', false );
$archive_intro    = isset( $descriptions[ $category_slug ] ) ? $descriptions[ $category_slug ] : __( 'Browse immersive experiences across Morocco.', 'lbhotel' );

if ( is_tax( 'lbhotel_place_category' ) ) {
    $term = get_queried_object();
    if ( $term instanceof WP_Term ) {
        $archive_title = $term->name;
        $term_desc     = term_description( $term->term_id, 'lbhotel_place_category' );
        if ( $term_desc ) {
            $archive_intro = $term_desc;
        }
    }
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

                    $post_id       = get_the_ID();
                    $city          = lbhotel_get_place_meta( $post_id, 'vm_city' );
                    $region        = lbhotel_get_place_meta( $post_id, 'vm_region' );
                    $location_bits = array_filter( array( $city, $region ) );
                    $virtual_tour  = lbhotel_get_place_meta( $post_id, 'vm_virtual_tour_url' );
                    $map_url       = lbhotel_get_place_meta( $post_id, 'vm_google_map_url' );
                    $latitude      = lbhotel_get_place_meta( $post_id, 'vm_latitude' );
                    $longitude     = lbhotel_get_place_meta( $post_id, 'vm_longitude' );
                    $rating_value  = lbhotel_get_place_meta( $post_id, 'vm_rating' );
                    $rating_markup = $rating_value ? lbhotel_render_rating_stars( (float) $rating_value ) : '';
                    $excerpt       = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 30 );

                    if ( ! $map_url && $latitude && $longitude ) {
                        $map_url = sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', rawurlencode( $latitude . ',' . $longitude ) );
                    }

                    $categories = wp_get_post_terms( $post_id, 'lbhotel_place_category' );
                    $action_links = array();

                    if ( $virtual_tour ) {
                        $action_links[] = array(
                            'label' => __( 'Virtual tour', 'lbhotel' ),
                            'url'   => $virtual_tour,
                            'icon'  => '🎥',
                        );
                    }

                    if ( $map_url ) {
                        $action_links[] = array(
                            'label' => __( 'Open map', 'lbhotel' ),
                            'url'   => $map_url,
                            'icon'  => '🗺️',
                        );
                    }

                    foreach ( $categories as $term ) {
                        $fields_for_category = lbhotel_get_fields_for_category( $term->slug );
                        if ( isset( $fields_for_category['vm_booking_url'] ) ) {
                            $booking_url = lbhotel_get_place_meta( $post_id, 'vm_booking_url' );
                            if ( $booking_url ) {
                                $action_links[] = array(
                                    'label' => __( 'Book now', 'lbhotel' ),
                                    'url'   => $booking_url,
                                    'icon'  => '🛎️',
                                );
                                break;
                            }
                        }
                    }
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'lbhotel-archive-card' ); ?>>
                        <a class="lbhotel-archive-card__link" href="<?php the_permalink(); ?>">
                            <div class="lbhotel-archive-card__media">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large' ); ?>
                                <?php else : ?>
                                    <div class="lbhotel-archive-card__placeholder" aria-hidden="true">📍</div>
                                <?php endif; ?>
                            </div>
                            <div class="lbhotel-archive-card__body">
                                <h2 class="lbhotel-archive-card__title"><?php the_title(); ?></h2>
                                <?php if ( $location_bits ) : ?>
                                    <p class="lbhotel-archive-card__location"><?php echo esc_html( implode( ', ', $location_bits ) ); ?></p>
                                <?php endif; ?>
                                <?php if ( $rating_markup ) : ?>
                                    <div class="lbhotel-archive-card__rating"><?php echo wp_kses_post( $rating_markup ); ?></div>
                                <?php endif; ?>
                                <p class="lbhotel-archive-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                                <span class="lbhotel-archive-card__cta"><?php esc_html_e( 'View details', 'lbhotel' ); ?></span>
                            </div>
                        </a>
                        <?php if ( $action_links ) : ?>
                            <div class="lbhotel-archive-card__actions" role="group" aria-label="<?php esc_attr_e( 'Quick actions', 'lbhotel' ); ?>">
                                <?php foreach ( $action_links as $action ) : ?>
                                    <a class="lbhotel-archive-card__action" href="<?php echo esc_url( $action['url'] ); ?>" target="_blank" rel="noopener">
                                        <span aria-hidden="true"><?php echo esc_html( $action['icon'] ); ?></span>
                                        <span class="screen-reader-text"><?php echo esc_html( $action['label'] ); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php
                endwhile;
                ?>
            </div>

            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p class="lbhotel-archive__empty"><?php esc_html_e( 'No listings available yet.', 'lbhotel' ); ?></p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
