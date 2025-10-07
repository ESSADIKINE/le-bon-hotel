<?php
/**
 * Single template proxy for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$context = lbhotel_get_template_context();

if ( ! $context || 'single' !== $context['type'] ) {
    $context = lbhotel_set_template_context(
        lbhotel_get_request_template_context() ?: array(
            'category' => lbhotel_get_default_category_slug(),
            'type'     => 'single',
        )
    );
}

require LBHOTEL_PLUGIN_DIR . 'templates/shared/single-place.php';
