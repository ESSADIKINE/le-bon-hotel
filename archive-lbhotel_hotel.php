<?php
/**
 * Archive template for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$paged = max( 1, get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 ) );

$query = new WP_Query(
    array(
        'post_type'      => 'lbhotel_hotel',
        'post_status'    => 'publish',
        'paged'          => $paged,
        'posts_per_page' => 12,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);
?>
<div id="primary" class="content-area lbhotel-archive">
    <main id="main" class="site-main" role="main">
        <header class="lbhotel-archive__header">
            <h1 class="lbhotel-archive__title"><?php post_type_archive_title(); ?></h1>
            <p class="lbhotel-archive__intro"><?php esc_html_e( 'Browse immersive experiences and essential details for destinations across Morocco.', 'lbhotel' ); ?></p>
        </header>

        <?php if ( $query->have_posts() ) : ?>
            <div class="lbhotel-archive__grid">
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id     = get_the_ID();
                    $city        = get_post_meta( $post_id, 'lbhotel_city', true );
                    $region      = get_post_meta( $post_id, 'lbhotel_region', true );
                    $country     = get_post_meta( $post_id, 'lbhotel_country', true );
                    $virtual_tour = get_post_meta( $post_id, 'lbhotel_virtual_tour_url', true );
                    $google_maps  = get_post_meta( $post_id, 'lbhotel_google_maps_url', true );
                    $contact      = get_post_meta( $post_id, 'lbhotel_contact_phone', true );
                    $categories   = wp_get_post_terms( $post_id, 'lbhotel_place_category' );
                    ?>
                    <article <?php post_class( 'lbhotel-archive__item' ); ?>>
                        <a class="lbhotel-archive__thumbnail" href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large' ); ?>
                            <?php else : ?>
                                <div class="lbhotel-archive__placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                        </a>
                        <div class="lbhotel-archive__body">
                            <h2 class="lbhotel-archive__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php if ( ! empty( $categories ) ) : ?>
                                <p class="lbhotel-archive__categories"><?php echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) ); ?></p>
                            <?php endif; ?>

                            <?php if ( $city || $region || $country ) : ?>
                                <p class="lbhotel-archive__location">
                                    <?php echo esc_html( trim( implode( ', ', array_filter( array( $city, $region, $country ) ) ) ) ); ?>
                                </p>
                            <?php endif; ?>

                            <div class="lbhotel-archive__excerpt"><?php the_excerpt(); ?></div>

                            <div class="lbhotel-archive__actions">
                                <a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View details', 'lbhotel' ); ?></a>
                                <?php if ( $virtual_tour ) : ?>
                                    <a class="button button-secondary" href="<?php echo esc_url( $virtual_tour ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Virtual tour', 'lbhotel' ); ?></a>
                                <?php endif; ?>
                                <?php if ( $google_maps ) : ?>
                                    <a class="button button-secondary" href="<?php echo esc_url( $google_maps ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Map', 'lbhotel' ); ?></a>
                                <?php endif; ?>
                            </div>

                            <?php if ( $contact ) : ?>
                                <p class="lbhotel-archive__contact"><?php echo esc_html( $contact ); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
                ?>
            </div>

            <nav class="lbhotel-archive__pagination" aria-label="<?php esc_attr_e( 'Places navigation', 'lbhotel' ); ?>">
                <?php
                echo paginate_links(
                    array(
                        'total'   => (int) $query->max_num_pages,
                        'current' => $paged,
                    )
                );
                ?>
            </nav>
        <?php else : ?>
            <p><?php esc_html_e( 'No places found at the moment. Please check back soon.', 'lbhotel' ); ?></p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
