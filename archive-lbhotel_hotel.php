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

get_header();

$category_term        = is_tax( 'lbhotel_place_category' ) ? get_queried_object() : null;
$archive_title        = $category_term ? $category_term->name : post_type_archive_title( '', false );
$descriptions         = lbhotel_get_place_category_descriptions();
$default_message      = __( 'Browse immersive experiences and essential details for destinations across Morocco.', 'lbhotel' );
$archive_intro_markup = wpautop( $default_message );

if ( $category_term ) {
    $term_description = term_description( $category_term->term_id, 'lbhotel_place_category' );

    if ( ! empty( $term_description ) ) {
        $archive_intro_markup = $term_description;
    } elseif ( isset( $descriptions[ $category_term->slug ] ) ) {
        $archive_intro_markup = wpautop( $descriptions[ $category_term->slug ] );
    }
}

$paged = max(
    1,
    get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? (int) get_query_var( 'page' ) : 1 )
);
?>
<div id="primary" class="content-area lbhotel-archive">
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

                    $post_id      = get_the_ID();
                    $city         = get_post_meta( $post_id, 'lbhotel_city', true );
                    $region       = get_post_meta( $post_id, 'lbhotel_region', true );
                    $country      = get_post_meta( $post_id, 'lbhotel_country', true );
                    $virtual_tour = get_post_meta( $post_id, 'lbhotel_virtual_tour_url', true );
                    $google_maps  = get_post_meta( $post_id, 'lbhotel_google_maps_url', true );
                    $contact      = get_post_meta( $post_id, 'lbhotel_contact_phone', true );
                    $categories   = wp_get_post_terms( $post_id, 'lbhotel_place_category' );

                    $category_actions   = array();
                    $category_highlight = array();

                    foreach ( $categories as $category ) {
                        switch ( $category->slug ) {
                            case 'hotels':
                                $booking_url = get_post_meta( $post_id, 'lbhotel_booking_url', true );
                                if ( $booking_url ) {
                                    $category_actions['booking'] = array(
                                        'label' => __( 'Book now', 'lbhotel' ),
                                        'url'   => $booking_url,
                                        'class' => 'button',
                                    );
                                }

                                $price_range = get_post_meta( $post_id, 'lbhotel_price_range', true );
                                if ( $price_range ) {
                                    $category_highlight['price'] = array(
                                        'label' => __( 'Price range', 'lbhotel' ),
                                        'value' => $price_range,
                                    );
                                }

                                $room_types = get_post_meta( $post_id, 'lbhotel_room_types', true );
                                if ( $room_types ) {
                                    $category_highlight['room_types'] = array(
                                        'label'     => __( 'Room types', 'lbhotel' ),
                                        'value'     => $room_types,
                                        'multiline' => true,
                                    );
                                }
                                break;
                            case 'restaurants':
                                $menu_url = get_post_meta( $post_id, 'lbhotel_menu_url', true );
                                if ( $menu_url ) {
                                    $category_actions['menu'] = array(
                                        'label' => __( 'View menu', 'lbhotel' ),
                                        'url'   => $menu_url,
                                        'class' => 'button',
                                    );
                                }

                                $reservation_url = get_post_meta( $post_id, 'lbhotel_reservation_url', true );
                                if ( $reservation_url ) {
                                    if ( filter_var( $reservation_url, FILTER_VALIDATE_URL ) ) {
                                        $category_actions['reservation'] = array(
                                            'label' => __( 'Reserve a table', 'lbhotel' ),
                                            'url'   => $reservation_url,
                                            'class' => 'button button-secondary',
                                        );
                                    } else {
                                        $category_highlight['reservation'] = array(
                                            'label' => __( 'Reservations', 'lbhotel' ),
                                            'value' => $reservation_url,
                                        );
                                    }
                                }

                                $opening_hours = get_post_meta( $post_id, 'lbhotel_opening_hours', true );
                                if ( $opening_hours ) {
                                    $category_highlight['opening_hours'] = array(
                                        'label'     => __( 'Opening hours', 'lbhotel' ),
                                        'value'     => $opening_hours,
                                        'multiline' => true,
                                    );
                                }

                                $specialties = get_post_meta( $post_id, 'lbhotel_specialties', true );
                                if ( $specialties ) {
                                    $category_highlight['specialties'] = array(
                                        'label'     => __( 'Specialties', 'lbhotel' ),
                                        'value'     => $specialties,
                                        'multiline' => true,
                                    );
                                }
                                break;
                            case 'tourist-sites':
                                $opening_hours = get_post_meta( $post_id, 'lbhotel_opening_hours', true );
                                if ( $opening_hours ) {
                                    $category_highlight['opening_hours'] = array(
                                        'label'     => __( 'Opening hours', 'lbhotel' ),
                                        'value'     => $opening_hours,
                                        'multiline' => true,
                                    );
                                }

                                $ticket_price_url = get_post_meta( $post_id, 'lbhotel_ticket_price_url', true );
                                if ( $ticket_price_url ) {
                                    $category_actions['ticket_price'] = array(
                                        'label' => __( 'Ticket pricing', 'lbhotel' ),
                                        'url'   => $ticket_price_url,
                                        'class' => 'button',
                                    );
                                }

                                $event_schedule_url = get_post_meta( $post_id, 'lbhotel_event_schedule_url', true );
                                if ( $event_schedule_url ) {
                                    $category_actions['event_schedule'] = array(
                                        'label' => __( 'Event schedule', 'lbhotel' ),
                                        'url'   => $event_schedule_url,
                                        'class' => 'button button-secondary',
                                    );
                                }
                                break;
                            case 'recreational-activities':
                                $activity_type = get_post_meta( $post_id, 'lbhotel_activity_type', true );
                                if ( $activity_type ) {
                                    $category_highlight['activity_type'] = array(
                                        'label' => __( 'Activity type', 'lbhotel' ),
                                        'value' => $activity_type,
                                    );
                                }

                                $booking_url = get_post_meta( $post_id, 'lbhotel_booking_url', true );
                                if ( $booking_url ) {
                                    $category_actions['activity_booking'] = array(
                                        'label' => __( 'Book activity', 'lbhotel' ),
                                        'url'   => $booking_url,
                                        'class' => 'button',
                                    );
                                }

                                $seasonality = get_post_meta( $post_id, 'lbhotel_seasonality', true );
                                if ( $seasonality ) {
                                    $category_highlight['seasonality'] = array(
                                        'label' => __( 'Seasonality', 'lbhotel' ),
                                        'value' => $seasonality,
                                    );
                                }
                                break;
                            case 'shopping':
                                $sales_url = get_post_meta( $post_id, 'lbhotel_sales_url', true );
                                if ( $sales_url ) {
                                    $category_actions['sales'] = array(
                                        'label' => __( 'View promotions', 'lbhotel' ),
                                        'url'   => $sales_url,
                                        'class' => 'button',
                                    );
                                }

                                $product_categories = get_post_meta( $post_id, 'lbhotel_product_categories', true );
                                if ( $product_categories ) {
                                    $category_highlight['products'] = array(
                                        'label'     => __( 'Product categories', 'lbhotel' ),
                                        'value'     => $product_categories,
                                        'multiline' => true,
                                    );
                                }

                                $store_type = get_post_meta( $post_id, 'lbhotel_store_type', true );
                                if ( $store_type ) {
                                    $category_highlight['store_type'] = array(
                                        'label' => __( 'Store type', 'lbhotel' ),
                                        'value' => $store_type,
                                    );
                                }
                                break;
                            case 'sports-activities':
                                $sport_type = get_post_meta( $post_id, 'lbhotel_sport_type', true );
                                if ( $sport_type ) {
                                    $category_highlight['sport_type'] = array(
                                        'label' => __( 'Sport type', 'lbhotel' ),
                                        'value' => $sport_type,
                                    );
                                }

                                $equipment_url = get_post_meta( $post_id, 'lbhotel_equipment_rental_url', true );
                                if ( $equipment_url ) {
                                    $category_actions['equipment'] = array(
                                        'label' => __( 'Rent equipment', 'lbhotel' ),
                                        'url'   => $equipment_url,
                                        'class' => 'button',
                                    );
                                }

                                $training_schedule_url = get_post_meta( $post_id, 'lbhotel_training_schedule_url', true );
                                if ( $training_schedule_url ) {
                                    $category_actions['training_schedule_url'] = array(
                                        'label' => __( 'Training schedule', 'lbhotel' ),
                                        'url'   => $training_schedule_url,
                                        'class' => 'button button-secondary',
                                    );
                                }

                                $training_schedule = get_post_meta( $post_id, 'lbhotel_training_schedule', true );
                                if ( $training_schedule ) {
                                    $category_highlight['training_schedule'] = array(
                                        'label'     => __( 'Training details', 'lbhotel' ),
                                        'value'     => $training_schedule,
                                        'multiline' => true,
                                    );
                                }
                                break;
                            case 'cultural-events':
                                $event_date_time = get_post_meta( $post_id, 'lbhotel_event_date_time', true );
                                if ( $event_date_time ) {
                                    $timestamp = strtotime( $event_date_time );
                                    if ( $timestamp ) {
                                        $formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
                                    } else {
                                        $formatted = $event_date_time;
                                    }

                                    $category_highlight['event_date_time'] = array(
                                        'label' => __( 'Event date & time', 'lbhotel' ),
                                        'value' => $formatted,
                                    );
                                }

                                $event_type = get_post_meta( $post_id, 'lbhotel_event_type', true );
                                if ( $event_type ) {
                                    $category_highlight['event_type'] = array(
                                        'label' => __( 'Event type', 'lbhotel' ),
                                        'value' => $event_type,
                                    );
                                }

                                $ticket_url = get_post_meta( $post_id, 'lbhotel_ticket_url', true );
                                if ( $ticket_url ) {
                                    $category_actions['ticket_url'] = array(
                                        'label' => __( 'Buy tickets', 'lbhotel' ),
                                        'url'   => $ticket_url,
                                        'class' => 'button',
                                    );
                                }

                                $ticket_price_url = get_post_meta( $post_id, 'lbhotel_ticket_price_url', true );
                                if ( $ticket_price_url ) {
                                    $category_actions['ticket_price'] = array(
                                        'label' => __( 'Ticket pricing', 'lbhotel' ),
                                        'url'   => $ticket_price_url,
                                        'class' => 'button button-secondary',
                                    );
                                }

                                $event_schedule_url = get_post_meta( $post_id, 'lbhotel_event_schedule_url', true );
                                if ( $event_schedule_url ) {
                                    $category_actions['event_schedule'] = array(
                                        'label' => __( 'Event schedule', 'lbhotel' ),
                                        'url'   => $event_schedule_url,
                                        'class' => 'button button-secondary',
                                    );
                                }
                                break;
                        }
                    }
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

                            <?php if ( ! empty( $category_highlight ) ) : ?>
                                <ul class="lbhotel-archive__highlights">
                                    <?php foreach ( $category_highlight as $highlight ) : ?>
                                        <li>
                                            <span class="lbhotel-archive__highlight-label"><?php echo esc_html( $highlight['label'] ); ?>:</span>
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
                                <a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View details', 'lbhotel' ); ?></a>
                                <?php
                                foreach ( $category_actions as $action ) {
                                    printf(
                                        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener">%3$s</a>',
                                        esc_attr( $action['class'] ),
                                        esc_url( $action['url'] ),
                                        esc_html( $action['label'] )
                                    );
                                }

                                if ( $virtual_tour ) {
                                    printf(
                                        '<a class="button button-secondary" href="%1$s" target="_blank" rel="noopener">%2$s</a>',
                                        esc_url( $virtual_tour ),
                                        esc_html__( 'Virtual tour', 'lbhotel' )
                                    );
                                }

                                if ( $google_maps ) {
                                    printf(
                                        '<a class="button button-secondary" href="%1$s" target="_blank" rel="noopener">%2$s</a>',
                                        esc_url( $google_maps ),
                                        esc_html__( 'Map', 'lbhotel' )
                                    );
                                }
                                ?>
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
