<?php
/**
 * Uninstall cleanup for Le Bon Hotel.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'lbhotel_settings' );
delete_option( 'lbhotel_restaurant_migrated' );

$meta_keys = array(
    'lbhotel_address',
    'lbhotel_city',
    'lbhotel_region',
    'lbhotel_postal_code',
    'lbhotel_country',
    'lbhotel_checkin_time',
    'lbhotel_checkout_time',
    'lbhotel_rooms_total',
    'lbhotel_avg_price_per_night',
    'lbhotel_has_free_breakfast',
    'lbhotel_has_parking',
    'lbhotel_star_rating',
    'lbhotel_gallery_images',
    'lbhotel_virtual_tour_url',
    'lbhotel_contact_phone',
    'lbhotel_booking_url',
    'lbhotel_rooms',
    'lbhotel_latitude',
    'lbhotel_longitude',
);

foreach ( $meta_keys as $meta_key ) {
    delete_post_meta_by_key( $meta_key );
}
