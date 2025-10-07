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
        'hotels'                 => __( 'Hotels', 'lbhotel' ),
        'restaurants'            => __( 'Restaurants', 'lbhotel' ),
        'tourist-sites'          => __( 'Tourist Sites', 'lbhotel' ),
        'recreational-activities'=> __( 'Recreational Activities', 'lbhotel' ),
        'shopping'               => __( 'Shopping', 'lbhotel' ),
        'sports-activities'      => __( 'Sports Activities', 'lbhotel' ),
        'cultural-events'        => __( 'Cultural Events', 'lbhotel' ),
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
        'lbhotel_virtual_tour_url' => array(
            'label'             => __( 'Virtual Tour URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'section'           => 'details',
            'placeholder'       => 'https://',
        ),
        'lbhotel_google_maps_url'  => array(
            'label'             => __( 'Google Maps URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'section'           => 'location',
            'placeholder'       => 'https://maps.google.com/',
        ),
        'lbhotel_address'          => array(
            'label'             => __( 'Street Address or Landmark', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'lbhotel_city'             => array(
            'label'             => __( 'City', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'lbhotel_region'           => array(
            'label'             => __( 'Region/State', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'lbhotel_postal_code'      => array(
            'label'             => __( 'Postal Code', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'lbhotel_country'          => array(
            'label'             => __( 'Country', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'section'           => 'location',
        ),
        'lbhotel_latitude'         => array(
            'label'             => __( 'Latitude', 'lbhotel' ),
            'type'              => 'number',
            'input'             => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
            'section'           => 'location',
            'attributes'        => array(
                'step' => '0.000001',
            ),
        ),
        'lbhotel_longitude'        => array(
            'label'             => __( 'Longitude', 'lbhotel' ),
            'type'              => 'number',
            'input'             => 'number',
            'sanitize_callback' => 'lbhotel_sanitize_decimal',
            'section'           => 'location',
            'attributes'        => array(
                'step' => '0.000001',
            ),
        ),
        'lbhotel_contact_phone'    => array(
            'label'             => __( 'Contact Phone', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'lbhotel_sanitize_phone',
            'section'           => 'details',
        ),
        'vm_rating'                => array(
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
        'lbhotel_gallery_images'   => array(
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
        'lbhotel_booking_url'        => array(
            'label'             => __( 'Booking URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'hotels', 'recreational-activities' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_room_types'         => array(
            'label'             => __( 'Room Types', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'hotels' ),
            'description'       => __( 'List different room types separated by commas or new lines.', 'lbhotel' ),
        ),
        'lbhotel_hotel_type'         => array(
            'label'             => __( 'Hotel Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'hotels' ),
        ),
        'lbhotel_price_range'        => array(
            'label'             => __( 'Price Range', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'hotels' ),
        ),
        'lbhotel_menu_url'           => array(
            'label'             => __( 'Menu URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'restaurants' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_specialties'        => array(
            'label'             => __( 'Specialties', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'restaurants' ),
            'description'       => __( 'Highlight signature dishes or cuisines.', 'lbhotel' ),
        ),
        'lbhotel_opening_hours'      => array(
            'label'             => __( 'Opening Hours', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'restaurants', 'tourist-sites' ),
            'description'       => __( 'Provide daily opening hours.', 'lbhotel' ),
        ),
        'lbhotel_reservation_url'    => array(
            'label'             => __( 'Reservation URL or Phone', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'restaurants' ),
        ),
        'lbhotel_ticket_price_url'   => array(
            'label'             => __( 'Ticket Price URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'tourist-sites', 'cultural-events' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_event_schedule_url' => array(
            'label'             => __( 'Event Schedule URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'tourist-sites', 'cultural-events' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_activity_type'      => array(
            'label'             => __( 'Activity Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'recreational-activities' ),
        ),
        'lbhotel_seasonality'        => array(
            'label'             => __( 'Seasonality', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'recreational-activities' ),
        ),
        'lbhotel_product_categories' => array(
            'label'             => __( 'Product Categories', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'shopping' ),
            'description'       => __( 'List the main product categories offered.', 'lbhotel' ),
        ),
        'lbhotel_store_type'         => array(
            'label'             => __( 'Store Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'shopping' ),
        ),
        'lbhotel_sales_url'          => array(
            'label'             => __( 'Sales or Promotions URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'shopping' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_sport_type'         => array(
            'label'             => __( 'Sport Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'sports-activities' ),
        ),
        'lbhotel_equipment_rental_url' => array(
            'label'             => __( 'Equipment Rental URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'sports-activities' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_training_schedule_url' => array(
            'label'             => __( 'Training Schedule URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'sports-activities' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_event_date_time'    => array(
            'label'             => __( 'Event Date & Time', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'datetime-local',
            'sanitize_callback' => 'lbhotel_sanitize_datetime_local',
            'applies_to'        => array( 'cultural-events' ),
        ),
        'lbhotel_event_type'         => array(
            'label'             => __( 'Event Type', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'text',
            'sanitize_callback' => 'sanitize_text_field',
            'applies_to'        => array( 'cultural-events' ),
        ),
        'lbhotel_ticket_url'         => array(
            'label'             => __( 'Ticket URL', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'url',
            'sanitize_callback' => 'esc_url_raw',
            'applies_to'        => array( 'cultural-events' ),
            'placeholder'       => 'https://',
        ),
        'lbhotel_training_schedule'  => array(
            'label'             => __( 'Training Schedule', 'lbhotel' ),
            'type'              => 'string',
            'input'             => 'textarea',
            'sanitize_callback' => 'lbhotel_sanitize_multiline_text',
            'applies_to'        => array( 'sports-activities' ),
            'description'       => __( 'Provide training times or class details.', 'lbhotel' ),
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

/**
 * Provide display configuration for each category in the front-end templates.
 *
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_category_display_config() {
    return array(
        'hotels' => array(
            'highlights' => array(
                'vm_hotel_type' => array(
                    'label' => __( 'Hotel type', 'lbhotel' ),
                ),
            ),
            'details'    => array(
                'vm_room_types' => array(
                    'label'     => __( 'Room types', 'lbhotel' ),
                    'multiline' => true,
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_booking_url',
                    'label' => __( 'Book now', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
        ),
        'restaurants' => array(
            'highlights' => array(
                'vm_specialties' => array(
                    'label'     => __( 'Specialties', 'lbhotel' ),
                    'multiline' => true,
                ),
            ),
            'details'    => array(
                'vm_opening_hours' => array(
                    'label'     => __( 'Opening hours', 'lbhotel' ),
                    'multiline' => true,
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_menu_url',
                    'label' => __( 'View menu', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
            'secondary_actions' => array(
                array(
                    'meta'  => 'vm_booking_url',
                    'label' => __( 'Reserve', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--ghost',
                ),
            ),
        ),
        'cultural-events' => array(
            'highlights' => array(
                'vm_event_type' => array(
                    'label' => __( 'Event type', 'lbhotel' ),
                ),
            ),
            'details'    => array(
                'vm_event_datetime' => array(
                    'label' => __( 'Event date & time', 'lbhotel' ),
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_ticket_url',
                    'label' => __( 'Buy tickets', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
            'secondary_actions' => array(
                array(
                    'meta'  => 'vm_ticket_info_url',
                    'label' => __( 'Ticket information', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--ghost',
                ),
            ),
        ),
        'recreational-activities' => array(
            'highlights' => array(
                'vm_activity_type' => array(
                    'label' => __( 'Activity type', 'lbhotel' ),
                ),
            ),
            'details'    => array(
                'vm_seasonality' => array(
                    'label' => __( 'Seasonality', 'lbhotel' ),
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_booking_url',
                    'label' => __( 'Book activity', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
        ),
        'shopping' => array(
            'highlights' => array(
                'vm_product_categories' => array(
                    'label'     => __( 'Product categories', 'lbhotel' ),
                    'multiline' => true,
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_sales_url',
                    'label' => __( 'View promotions', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
        ),
        'sports-activities' => array(
            'highlights' => array(
                'vm_sport_type' => array(
                    'label' => __( 'Sport type', 'lbhotel' ),
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_equipment_rental_url',
                    'label' => __( 'Rent equipment', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
            'secondary_actions' => array(
                array(
                    'meta'  => 'vm_booking_url',
                    'label' => __( 'Book session', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--ghost',
                ),
            ),
        ),
        'tourist-sites' => array(
            'highlights' => array(
                'vm_opening_hours' => array(
                    'label'     => __( 'Opening hours', 'lbhotel' ),
                    'multiline' => true,
                ),
            ),
            'actions'    => array(
                array(
                    'meta'  => 'vm_ticket_info_url',
                    'label' => __( 'Ticket information', 'lbhotel' ),
                    'class' => 'lbhotel-button lbhotel-button--primary',
                ),
            ),
        ),
    );
}
