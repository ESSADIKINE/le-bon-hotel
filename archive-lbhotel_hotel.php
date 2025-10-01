<?php
/**
 * Astra archive override for Le Bon Hotel listings.
 *
 * Place this file in your active theme (or child theme) to override the archive
 * view for the `lbhotel_hotel` custom post type provided by the Le Bon Hotel
 * plugin.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme_directory = get_stylesheet_directory_uri();

wp_enqueue_style( 'lbhotel-hotel', $theme_directory . '/hotel.css', array(), '1.0.0' );
wp_enqueue_script( 'lbhotel-hotel', $theme_directory . '/hotel.js', array(), '1.0.0', true );

wp_localize_script(
    'lbhotel-hotel',
    'lbHotelArchiveData',
    array(
        'context' => 'archive',
    )
);

get_header();
?>

<div id="content" class="site-content">
    <div class="ast-container">
        <?php if ( function_exists( 'astra_primary_content_top' ) ) { astra_primary_content_top(); } ?>
        <div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area"'; } ?>>
            <main id="main" class="site-main">
                <?php if ( function_exists( 'astra_primary_content_before' ) ) { astra_primary_content_before(); } ?>
                <div class="entry-content moroccan-entry" data-lbhotel-context="archive">
                    <header class="moroccan-section moroccan-archive__header">
                        <h1 class="moroccan-section__title"><?php post_type_archive_title(); ?></h1>
                        <form id="lbhotel-filter-form" class="moroccan-filter-bar" aria-label="<?php esc_attr_e( 'Filter hotels', 'lbhotel' ); ?>" data-empty-message="<?php esc_attr_e( 'No hotels match your filters. Try a different search.', 'lbhotel' ); ?>">
                            <label class="moroccan-filter-bar__group">
                                <span class="screen-reader-text"><?php esc_html_e( 'Search hotels by title or city', 'lbhotel' ); ?></span>
                                <input type="search" id="lbhotel-search" name="lbhotel_search" placeholder="<?php esc_attr_e( 'Search by hotel or city', 'lbhotel' ); ?>" />
                            </label>
                            <label class="moroccan-filter-bar__group">
                                <span class="screen-reader-text"><?php esc_html_e( 'Filter hotels by distance', 'lbhotel' ); ?></span>
                                <select id="lbhotel-distance" name="lbhotel_distance">
                                    <option value="all"><?php esc_html_e( 'Any distance', 'lbhotel' ); ?></option>
                                    <option value="10"><?php esc_html_e( 'Within 10 km', 'lbhotel' ); ?></option>
                                    <option value="25"><?php esc_html_e( 'Within 25 km', 'lbhotel' ); ?></option>
                                    <option value="50"><?php esc_html_e( 'Within 50 km', 'lbhotel' ); ?></option>
                                </select>
                            </label>
                            <label class="moroccan-filter-bar__group">
                                <span class="screen-reader-text"><?php esc_html_e( 'Sort hotels', 'lbhotel' ); ?></span>
                                <select id="lbhotel-sort" name="lbhotel_sort">
                                    <option value="date" selected><?php esc_html_e( 'Newest', 'lbhotel' ); ?></option>
                                    <option value="price"><?php esc_html_e( 'Price (Low to High)', 'lbhotel' ); ?></option>
                                    <option value="rating"><?php esc_html_e( 'Rating (High to Low)', 'lbhotel' ); ?></option>
                                </select>
                            </label>
                            <button type="reset" class="moroccan-button moroccan-button--ghost"><?php esc_html_e( 'Clear', 'lbhotel' ); ?></button>
                        </form>
                    </header>

                    <?php if ( have_posts() ) : ?>
                        <div class="moroccan-archive-grid" id="lbhotel-archive-grid">
                            <?php while ( have_posts() ) : the_post(); ?>
                                <?php
                                $hotel_id      = get_the_ID();
                                $star_rating   = (int) get_post_meta( $hotel_id, 'lbhotel_star_rating', true );
                                $price_value   = get_post_meta( $hotel_id, 'lbhotel_avg_price_per_night', true );
                                $city_value    = get_post_meta( $hotel_id, 'lbhotel_city', true );
                                $distance_meta = get_post_meta( $hotel_id, 'lbhotel_distance_km', true );
                                $gallery_meta  = get_post_meta( $hotel_id, 'lbhotel_gallery_images', true );

                                $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : array_filter( array_map( 'absint', (array) $gallery_meta ) );

                                $thumbnail_url = get_the_post_thumbnail_url( $hotel_id, 'large' );
                                if ( ! $thumbnail_url && $gallery_ids ) {
                                    $thumbnail_url = wp_get_attachment_image_url( (int) $gallery_ids[0], 'large' );
                                }

                                $price_numeric = '';
                                $price_display = '';
                                if ( '' !== $price_value && null !== $price_value ) {
                                    if ( is_numeric( $price_value ) ) {
                                        $price_numeric = (float) $price_value;
                                        $price_display = number_format_i18n( (float) $price_value, 2 );
                                    } else {
                                        $price_display = sanitize_text_field( $price_value );
                                    }
                                }

                                $distance_value = is_numeric( $distance_meta ) ? (float) $distance_meta : '';
                                $date_value     = get_post_time( 'U' );
                                ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'moroccan-card moroccan-card--hotel' ); ?> data-title="<?php echo esc_attr( get_the_title() ); ?>" data-city="<?php echo esc_attr( $city_value ); ?>" data-price="<?php echo esc_attr( $price_numeric ); ?>" data-rating="<?php echo esc_attr( $star_rating ); ?>" data-distance="<?php echo esc_attr( $distance_value ); ?>" data-date="<?php echo esc_attr( $date_value ); ?>">
                                    <div class="moroccan-card__image">
                                        <?php if ( $thumbnail_url ) : ?>
                                            <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php the_title_attribute(); ?>" />
                                        <?php else : ?>
                                            <span class="moroccan-card__placeholder"><?php esc_html_e( 'No image available', 'lbhotel' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="moroccan-card__body">
                                        <h2 class="moroccan-card__title"><?php the_title(); ?></h2>
                                        <?php if ( $star_rating > 0 ) : ?>
                                            <div class="moroccan-stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $star_rating, 'lbhotel' ), $star_rating ) ); ?>">
                                                <?php echo wp_kses_post( str_repeat( '<span aria-hidden="true">★</span>', min( 5, $star_rating ) ) ); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( $city_value ) : ?>
                                            <p class="moroccan-card__meta">
                                                <span class="moroccan-card__label"><?php esc_html_e( 'City:', 'lbhotel' ); ?></span>
                                                <span class="moroccan-card__value"><?php echo esc_html( $city_value ); ?></span>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ( $price_display ) : ?>
                                            <p class="moroccan-card__meta">
                                                <span class="moroccan-card__label"><?php esc_html_e( 'From', 'lbhotel' ); ?></span>
                                                <span class="moroccan-card__value moroccan-card__value--price"><?php echo esc_html( sprintf( __( '%s / night', 'lbhotel' ), $price_display ) ); ?></span>
                                            </p>
                                        <?php endif; ?>
                                        <a class="moroccan-button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Hotel', 'lbhotel' ); ?></a>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>

                        <nav class="moroccan-pagination" aria-label="<?php esc_attr_e( 'Hotel pagination', 'lbhotel' ); ?>">
                            <?php
                            echo wp_kses_post(
                                paginate_links(
                                    array(
                                        'prev_text' => '&laquo;',
                                        'next_text' => '&raquo;',
                                    )
                                )
                            );
                            ?>
                        </nav>
                    <?php else : ?>
                        <p class="moroccan-placeholder"><?php esc_html_e( 'No hotels found. Please adjust your filters.', 'lbhotel' ); ?></p>
                    <?php endif; ?>
                </div>
                <?php if ( function_exists( 'astra_primary_content_after' ) ) { astra_primary_content_after(); } ?>
            </main>
        </div>
        <?php if ( function_exists( 'astra_primary_content_bottom' ) ) { astra_primary_content_bottom(); } ?>
    </div>
</div>

<?php get_footer(); ?>
