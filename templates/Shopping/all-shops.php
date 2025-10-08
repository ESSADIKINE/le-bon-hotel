<?php
/**
 * Shopping archive template loader.
 *
 * @package VirtualMaroc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

lbhotel_set_template_context(
    array(
        'category' => 'shopping',
        'type'     => 'archive',
    )
);

require dirname( __DIR__ ) . '/shared/all-places.php';
