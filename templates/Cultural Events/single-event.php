<?php
/**
 * Cultural events single template loader.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

lbhotel_set_template_context(
    array(
        'category' => 'cultural-events',
        'type'     => 'single',
    )
);

require dirname( __DIR__ ) . '/shared/single-place.php';
