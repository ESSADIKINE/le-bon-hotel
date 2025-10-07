<?php
/**
 * Field configuration for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve the available Virtual Maroc place categories.
 *
 * @return array<string,string> Associative array of slug => label.
 */
function lbhotel_get_place_category_labels() {
    return array(
        'hotels'                  => __( 'Hotels', 'lbhotel' ),
        'restaurants'             => __( 'Restaurants', 'lbhotel' ),
        'tourist-sites'           => __( 'Tourist Sites', 'lbhotel' ),
        'recreational-activities' => __( 'Recreational Activities', 'lbhotel' ),
        'shopping'                => __( 'Shopping', 'lbhotel' ),
        'sports-activities'       => __( 'Sports Activities', 'lbhotel' ),
        'cultural-events'         => __( 'Cultural Events', 'lbhotel' ),
    );
}

/**
 * Retrieve default descriptions for each place category.
 *
 * @return array<string,string>
 */
function lbhotel_get_place_category_descriptions() {
    return array(
        'hotels'                 => __( 'Discover Morocco\'s hotels with booking details, room highlights, and immersive previews.', 'lbhotel' ),
        'restaurants'            => __( 'Explore restaurants across Morocco featuring menus, specialties, and reservation information.', 'lbhotel' ),
        'tourist-sites'          => __( 'Plan visits to renowned tourist attractions with opening hours, ticketing, and event schedules.', 'lbhotel' ),
        'recreational-activities'=> __( 'Find recreational experiences with booking links, activity types, and seasonal availability.', 'lbhotel' ),
        'shopping'               => __( 'Browse shopping destinations showcasing product categories, store details, and promotions.', 'lbhotel' ),
        'sports-activities'      => __( 'Locate sports activities with training schedules, equipment rental options, and facility information.', 'lbhotel' ),
        'cultural-events'        => __( 'Stay up to date on cultural events with ticket links, event types, and key scheduling details.', 'lbhotel' ),
    );
}

/**
 * Retrieve the global field definitions shared by all categories.
 *
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_global_field_definitions() {
    return array(
        'vm_virtual_tour_url' => array(
            'label'             => __( 'Virtual Tour URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'section'           => 'details',
            'placeholder'       => 'https://',
        ),
        'vm_google_map_url'  => array(
            'label'             => __( 'Google Map URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'section'           => 'location',
            'placeholder'       => 'https://maps.google.com/',
        ),
        'vm_street_address'  => array(
            'label'             => __( 'Street Address', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'vm_city'            => array(
            'label'             => __( 'City', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'vm_region'          => array(
            'label'             => __( 'Region/State', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'vm_postal_code'     => array(
            'label'             => __( 'Postal Code', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'vm_country'         => array(
            'label'             => __( 'Country', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'vm_latitude'        => array(
            'label'             => __( 'Latitude', 'lbhotel' ),
            'type'              => 'number',
            'input'             => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
            'section'           => 'location',
            'attributes'        => array(
                'step' => '0.000001',
            ),
        ),
        'vm_longitude'       => array(
            'label'             => __( 'Longitude', 'lbhotel' ),
            'type'              => 'number',
            'input'             => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
            'section'           => 'location',
            'attributes'        => array(
                'step' => '0.000001',
            ),
        ),
        'vm_contact_phone'   => array(
            'label'             => __( 'Contact Phone', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'lbhotel_sanitize_phone',
            'section'           => 'details',
        ),
        'vm_rating'          => array(
            'label'             => __( 'Rating (0-5)', 'lbhotel' ),
            'type'              => 'number',
            'input'             => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_rating',
            'section'           => 'details',
            'attributes'        => array(
                'min'  => '0',
                'max'  => '5',
                'step' => '0.1',
            ),
        ),
        'vm_gallery'         => array(
            'label'             => __( 'Gallery Images', 'lbhotel' ),
            'type'              => 'array',
            'input'             => 'gallery',
            'sanitize_callback' => 'lbhotel_sanitize_gallery_images',
            'section'           => 'media',
        ),
    );
}

/**
 * Retrieve category-specific field definitions.
 *
 * Each field definition includes the categories where it applies.
 *
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_category_field_definitions() {
    return array(
        'vm_booking_url' => array(
            'label'             => __( 'Booking URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'hotels', 'recreational-activities' ),
            'placeholder'       => 'https://',
        ),
        'vm_hotel_type' => array(
            'label'             => __( 'Hotel Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'select',
            'options'           => array(
                'Apartment' => __( 'Apartment', 'lbhotel' ),
                'Boutique'  => __( 'Boutique', 'lbhotel' ),
                'Business'  => __( 'Business', 'lbhotel' ),
                'Hostel'    => __( 'Hostel', 'lbhotel' ),
                'Resort'    => __( 'Resort', 'lbhotel' ),
            ),
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'hotels' ),
        ),
        'vm_menu_url' => array(
            'label'             => __( 'Menu URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'restaurants' ),
            'placeholder'       => 'https://',
        ),
        'vm_specialties' => array(
            'label'             => __( 'Specialties', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'restaurants' ),
        ),
        'vm_opening_hours' => array(
            'label'             => __( 'Opening Hours', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'restaurants', 'tourist-sites' ),
        ),
        'vm_event_datetime' => array(
            'label'             => __( 'Event Date & Time', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'datetime-local',
            'sanitize_callback' => 'lbhotel_sanitize_datetime_local',
            'applies_to'        => array( 'cultural-events' ),
        ),
        'vm_ticket_url' => array(
            'label'             => __( 'Ticket URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'cultural-events' ),
            'placeholder'       => 'https://',
        ),
        'vm_event_type' => array(
            'label'             => __( 'Event Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'cultural-events' ),
        ),
        'vm_activity_type' => array(
            'label'             => __( 'Activity Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'recreational-activities' ),
        ),
        'vm_product_categories' => array(
            'label'             => __( 'Product Categories', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'shopping' ),
        ),
        'vm_sales_url' => array(
            'label'             => __( 'Sales URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'shopping' ),
            'placeholder'       => 'https://',
        ),
        'vm_sport_type' => array(
            'label'             => __( 'Sport Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'sports-activities' ),
        ),
        'vm_equipment_rental_url' => array(
            'label'             => __( 'Equipment Rental URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'sports-activities' ),
            'placeholder'       => 'https://',
        ),
        'vm_ticket_info_url' => array(
            'label'             => __( 'Ticket Info URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'tourist-sites' ),
            'placeholder'       => 'https://',
        ),
    );
}

/**
 * Retrieve all field definitions indexed by meta key.
 *
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_all_field_definitions() {
    return array_merge( lbhotel_get_global_field_definitions(), lbhotel_get_category_field_definitions() );
}

/**
 * Helper to filter category field definitions for a specific category.
 *
 * @param string $category_slug Category slug.
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_fields_for_category( $category_slug ) {
    $fields = array();

    foreach ( lbhotel_get_category_field_definitions() as $meta_key => $definition ) {
        if ( empty( $definition['applies_to'] ) || in_array( $category_slug, $definition['applies_to'], true ) ) {
            $fields[ $meta_key ] = $definition;
        }
    }

    return $fields;
}
