<?php
/**
 * Archive template for Le Bon Hotel listings.
 *
 * Place this file inside your active theme to customize the archive view for
 * the `lbhotel_hotel` custom post type.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$search_term = isset( $_GET['lbhotel_search'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_search'] ) ) : '';
$distance     = isset( $_GET['lbhotel_distance'] ) ? sanitize_text_field( wp_unslash( $_GET['lbhotel_distance'] ) ) : '';
$sort         = isset( $_GET['lbhotel_sort'] ) ? sanitize_key( wp_unslash( $_GET['lbhotel_sort'] ) ) : 'date';
$paged        = max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );

$allowed_distances = array( '10', '20', '50' );
if ( ! in_array( $distance, $allowed_distances, true ) ) {
    $distance = '';
}

$allowed_sorts = array( 'date', 'price', 'rating' );
if ( ! in_array( $sort, $allowed_sorts, true ) ) {
    $sort = 'date';
}

$query_args = array(
    'post_type'      => 'lbhotel_hotel',
    'post_status'    => 'publish',
    'paged'          => $paged,
    'posts_per_page' => 9,
);

if ( $search_term ) {
    $title_matches = get_posts(
        array(
            'post_type'      => 'lbhotel_hotel',
            'post_status'    => 'publish',
            's'              => $search_term,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    $city_matches = get_posts(
        array(
            'post_type'      => 'lbhotel_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => 'lbhotel_city',
                    'value'   => $search_term,
                    'compare' => 'LIKE',
                ),
            ),
        )
    );

    $matched_ids = array_unique( array_merge( $title_matches, $city_matches ) );

    $query_args['post__in'] = $matched_ids ? $matched_ids : array( 0 );
}

$meta_query = array();

if ( $distance ) {
    $distance_value = absint( $distance );
    $meta_query[]   = array(
        'relation' => 'OR',
        array(
            'key'     => 'lbhotel_distance_km',
            'value'   => $distance_value,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        ),
        array(
            'key'     => 'lbhotel_distance_km',
            'compare' => 'NOT EXISTS',
        ),
    );
}

if ( $meta_query ) {
    if ( count( $meta_query ) > 1 ) {
        $meta_query = array_merge( array( 'relation' => 'AND' ), $meta_query );
    }

    $query_args['meta_query'] = $meta_query;
}

switch ( $sort ) {
    case 'price':
        $query_args['meta_key'] = 'lbhotel_avg_price_per_night';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'ASC';
        break;
    case 'rating':
        $query_args['meta_key'] = 'lbhotel_star_rating';
        $query_args['orderby']  = 'meta_value_num';
        $query_args['order']    = 'DESC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order']   = 'DESC';
        break;
}

$hotels_query = new WP_Query( $query_args );
?>

<div class="hotel-container hotel-archive">
    <header class="hotel-archive-header">
        <h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
        <form class="hotel-archive-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'lbhotel_hotel' ) ); ?>">
            <div class="filter-group">
                <label for="lbhotel_search" class="screen-reader-text"><?php esc_html_e( 'Search hotels', 'lbhotel' ); ?></label>
                <input type="search" id="lbhotel_search" name="lbhotel_search" placeholder="<?php esc_attr_e( 'Search by hotel or city', 'lbhotel' ); ?>" value="<?php echo esc_attr( $search_term ); ?>" />
            </div>
            <div class="filter-group">
                <label for="lbhotel_distance" class="screen-reader-text"><?php esc_html_e( 'Filter by distance', 'lbhotel' ); ?></label>
                <select id="lbhotel_distance" name="lbhotel_distance">
                    <option value=""><?php esc_html_e( 'Any distance', 'lbhotel' ); ?></option>
                    <?php foreach ( $allowed_distances as $option ) : ?>
                        <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $distance, $option ); ?>><?php echo esc_html( sprintf( _x( '%skm', 'distance filter label', 'lbhotel' ), $option ) ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="lbhotel_sort" class="screen-reader-text"><?php esc_html_e( 'Sort hotels', 'lbhotel' ); ?></label>
                <select id="lbhotel_sort" name="lbhotel_sort">
                    <option value="date" <?php selected( $sort, 'date' ); ?>><?php esc_html_e( 'Newest', 'lbhotel' ); ?></option>
                    <option value="price" <?php selected( $sort, 'price' ); ?>><?php esc_html_e( 'Price (Low to High)', 'lbhotel' ); ?></option>
                    <option value="rating" <?php selected( $sort, 'rating' ); ?>><?php esc_html_e( 'Rating (High to Low)', 'lbhotel' ); ?></option>
                </select>
            </div>
            <button type="submit" class="filter-submit"><?php esc_html_e( 'Apply', 'lbhotel' ); ?></button>
        </form>
    </header>

    <?php if ( $hotels_query->have_posts() ) : ?>
        <div class="hotel-archive-grid">
            <?php
            while ( $hotels_query->have_posts() ) :
                $hotels_query->the_post();

                $hotel_id      = get_the_ID();
                $stars         = (int) get_post_meta( $hotel_id, 'lbhotel_star_rating', true );
                $city_value    = get_post_meta( $hotel_id, 'lbhotel_city', true );
                $price_value   = get_post_meta( $hotel_id, 'lbhotel_avg_price_per_night', true );
                $gallery_meta  = get_post_meta( $hotel_id, 'lbhotel_gallery_images', true );
                $gallery_array = is_array( $gallery_meta ) ? $gallery_meta : array_filter( array_map( 'absint', (array) $gallery_meta ) );

                $thumbnail_url = get_the_post_thumbnail_url( $hotel_id, 'large' );
                if ( ! $thumbnail_url && ! empty( $gallery_array ) ) {
                    $thumbnail_url = wp_get_attachment_image_url( (int) $gallery_array[0], 'large' );
                }

                $card_style = $thumbnail_url ? sprintf( 'background-image: url(%s);', esc_url( $thumbnail_url ) ) : '';

                $price_display = '';
                if ( '' !== $price_value && null !== $price_value ) {
                    $price_display = is_numeric( $price_value ) ? number_format_i18n( (float) $price_value, 2 ) : sanitize_text_field( $price_value );
                }
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'hotel-card' ); ?>>
                    <div class="hotel-card-image" style="<?php echo esc_attr( $card_style ); ?>">
                        <?php if ( ! $thumbnail_url ) : ?>
                            <span class="hotel-card-placeholder"><?php esc_html_e( 'No image available', 'lbhotel' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hotel-card-body">
                        <h2 class="hotel-card-title"><?php the_title(); ?></h2>
                        <?php if ( $stars > 0 ) : ?>
                            <div class="hotel-card-stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $stars, 'lbhotel' ), $stars ) ); ?>">
                                <?php echo wp_kses_post( str_repeat( '<span class="star">&#9733;</span>', min( 5, $stars ) ) ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( $city_value ) : ?>
                            <p class="hotel-card-city"><?php echo esc_html( $city_value ); ?></p>
                        <?php endif; ?>
                        <?php if ( $price_display ) : ?>
                            <p class="hotel-card-price"><?php echo esc_html( sprintf( __( 'From %s / night', 'lbhotel' ), $price_display ) ); ?></p>
                        <?php endif; ?>
                        <a class="hotel-card-button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Hotel', 'lbhotel' ); ?></a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <nav class="hotel-pagination" aria-label="<?php esc_attr_e( 'Hotel pagination', 'lbhotel' ); ?>">
            <?php
            echo wp_kses_post(
                paginate_links(
                    array(
                        'total'   => $hotels_query->max_num_pages,
                        'current' => $paged,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    )
                )
            );
            ?>
        </nav>
    <?php else : ?>
        <p class="hotel-no-results"><?php esc_html_e( 'No hotels found matching your filters.', 'lbhotel' ); ?></p>
    <?php endif; ?>
</div>

<?php
wp_reset_postdata();
get_footer();
