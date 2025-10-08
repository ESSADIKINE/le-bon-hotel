<?php
/**
 * Uninstall cleanup for Virtual Maroc.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'lbhotel_settings' );
delete_option( 'lbhotel_restaurant_migrated' );

$meta_keys = array(
    'lbhotel_virtual_tour_url',
    'lbhotel_google_maps_url',
    'lbhotel_address',
    'lbhotel_city',
    'lbhotel_region',
    'lbhotel_postal_code',
    'lbhotel_country',
    'lbhotel_latitude',
    'lbhotel_longitude',
    'lbhotel_contact_phone',
    'lbhotel_gallery_images',
    'lbhotel_booking_url',
    'lbhotel_room_types',
    'lbhotel_hotel_type',
    'lbhotel_price_range',
    'lbhotel_menu_url',
    'lbhotel_specialties',
    'lbhotel_opening_hours',
    'lbhotel_reservation_url',
    'lbhotel_ticket_price_url',
    'lbhotel_event_schedule_url',
    'lbhotel_activity_type',
    'lbhotel_seasonality',
    'lbhotel_product_categories',
    'lbhotel_store_type',
    'lbhotel_sales_url',
    'lbhotel_sport_type',
    'lbhotel_equipment_rental_url',
    'lbhotel_training_schedule_url',
    'lbhotel_event_date_time',
    'lbhotel_event_type',
    'lbhotel_ticket_url',
    'lbhotel_training_schedule',
);

foreach ( $meta_keys as $meta_key ) {
    delete_post_meta_by_key( $meta_key );
}
