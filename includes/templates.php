<?php
/**
 * Template helpers for Virtual Maroc places.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve the template map for each category.
 *
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_category_template_map() {
    static $map = null;

    if ( null === $map ) {
        $map = array(
            'hotels' => array(
                'folder' => 'Hotels',
                'single' => array(
                    'template'      => 'single-hotel.php',
                    'style'         => 'single-hotel.css',
                    'script'        => 'single-hotel.js',
                    'rewrite_slug'  => 'hotel',
                ),
                'archive' => array(
                    'template'      => 'all-hotels.php',
                    'style'         => 'all-hotels.css',
                    'script'        => 'all-hotels.js',
                    'rewrite_slug'  => 'all-hotels',
                ),
            ),
            'restaurants' => array(
                'folder' => 'Restaurants',
                'single' => array(
                    'template'      => 'single-restaurant.php',
                    'style'         => 'single-restaurant.css',
                    'script'        => 'single-restaurant.js',
                    'rewrite_slug'  => 'restaurant',
                ),
                'archive' => array(
                    'template'      => 'all-restaurants.php',
                    'style'         => 'all-restaurants.css',
                    'script'        => 'all-restaurants.js',
                    'rewrite_slug'  => 'all-restaurants',
                ),
            ),
            'cultural-events' => array(
                'folder' => 'Cultural Events',
                'single' => array(
                    'template'      => 'single-event.php',
                    'style'         => 'single-event.css',
                    'script'        => 'single-event.js',
                    'rewrite_slug'  => 'event',
                ),
                'archive' => array(
                    'template'      => 'all-events.php',
                    'style'         => 'all-events.css',
                    'script'        => 'all-events.js',
                    'rewrite_slug'  => 'all-events',
                ),
            ),
            'recreational-activities' => array(
                'folder' => 'Recreational Activities',
                'single' => array(
                    'template'      => 'single-activity.php',
                    'style'         => 'single-activity.css',
                    'script'        => 'single-activity.js',
                    'rewrite_slug'  => 'activity',
                ),
                'archive' => array(
                    'template'      => 'all-activities.php',
                    'style'         => 'all-activities.css',
                    'script'        => 'all-activities.js',
                    'rewrite_slug'  => 'all-activities',
                ),
            ),
            'shopping' => array(
                'folder' => 'Shopping',
                'single' => array(
                    'template'      => 'single-shop.php',
                    'style'         => 'single-shop.css',
                    'script'        => 'single-shop.js',
                    'rewrite_slug'  => 'shop',
                ),
                'archive' => array(
                    'template'      => 'all-shops.php',
                    'style'         => 'all-shops.css',
                    'script'        => 'all-shops.js',
                    'rewrite_slug'  => 'all-shops',
                ),
            ),
            'sports-activities' => array(
                'folder' => 'Sports Activities',
                'single' => array(
                    'template'      => 'single-sport.php',
                    'style'         => 'single-sport.css',
                    'script'        => 'single-sport.js',
                    'rewrite_slug'  => 'sport',
                ),
                'archive' => array(
                    'template'      => 'all-sports.php',
                    'style'         => 'all-sports.css',
                    'script'        => 'all-sports.js',
                    'rewrite_slug'  => 'all-sports',
                ),
            ),
            'tourist-sites' => array(
                'folder' => 'Tourist Sites',
                'single' => array(
                    'template'      => 'single-site.php',
                    'style'         => 'single-site.css',
                    'script'        => 'single-site.js',
                    'rewrite_slug'  => 'site',
                ),
                'archive' => array(
                    'template'      => 'all-sites.php',
                    'style'         => 'all-sites.css',
                    'script'        => 'all-sites.js',
                    'rewrite_slug'  => 'all-sites',
                ),
            ),
        );
    }

    return $map;
}

/**
 * Retrieve the default category slug.
 *
 * @return string
 */
function lbhotel_get_default_category_slug() {
    $map = lbhotel_get_category_template_map();
    reset( $map );

    return key( $map );
}

/**
 * Ensure a provided category slug is valid.
 *
 * @param string $slug Raw slug.
 * @return string
 */
function lbhotel_normalize_category_slug( $slug ) {
    $map = lbhotel_get_category_template_map();

    if ( isset( $map[ $slug ] ) ) {
        return $slug;
    }

    return lbhotel_get_default_category_slug();
}

/**
 * Locate category configuration.
 *
 * @param string $slug Category slug.
 * @return array<string,mixed>|null
 */
function lbhotel_get_category_template_config( $slug ) {
    $map = lbhotel_get_category_template_map();

    return isset( $map[ $slug ] ) ? $map[ $slug ] : null;
}

/**
 * Build a filesystem path for a template asset.
 *
 * @param string $slug Category slug.
 * @param string $type Template type (single|archive).
 * @param string $asset Asset key (template|style|script).
 * @return string Empty string if not found.
 */
function lbhotel_get_category_template_path( $slug, $type, $asset = 'template' ) {
    $config = lbhotel_get_category_template_config( $slug );

    if ( ! $config || empty( $config[ $type ][ $asset ] ) ) {
        return '';
    }

    $relative = 'templates/' . $config['folder'] . '/' . $config[ $type ][ $asset ];
    $path     = trailingslashit( LBHOTEL_PLUGIN_DIR ) . $relative;

    return file_exists( $path ) ? $path : '';
}

/**
 * Build an asset URL for a category template resource.
 *
 * @param string $slug Category slug.
 * @param string $type Template type (single|archive).
 * @param string $asset Asset key (style|script|template).
 * @return string
 */
function lbhotel_get_category_template_url( $slug, $type, $asset ) {
    $config = lbhotel_get_category_template_config( $slug );

    if ( ! $config || empty( $config[ $type ][ $asset ] ) ) {
        return '';
    }

    $relative = 'templates/' . $config['folder'] . '/' . $config[ $type ][ $asset ];
    $path     = trailingslashit( LBHOTEL_PLUGIN_DIR ) . $relative;

    if ( ! file_exists( $path ) ) {
        return '';
    }

    $segments = array( 'templates', $config['folder'], $config[ $type ][ $asset ] );
    $segments = array_map(
        static function ( $segment ) {
            $parts = explode( '/', $segment );
            $parts = array_map( 'rawurlencode', $parts );

            return implode( '/', $parts );
        },
        $segments
    );

    return LBHOTEL_PLUGIN_URL . implode( '/', $segments );
}

/**
 * Retrieve the rewrite slug for category singles.
 *
 * @param string $slug Category slug.
 * @return string
 */
function lbhotel_get_category_single_rewrite_slug( $slug ) {
    $config = lbhotel_get_category_template_config( $slug );

    return $config && isset( $config['single']['rewrite_slug'] ) ? $config['single']['rewrite_slug'] : 'place';
}

/**
 * Retrieve the rewrite slug for category archives.
 *
 * @param string $slug Category slug.
 * @return string
 */
function lbhotel_get_category_archive_rewrite_slug( $slug ) {
    $config = lbhotel_get_category_template_config( $slug );

    return $config && isset( $config['archive']['rewrite_slug'] ) ? $config['archive']['rewrite_slug'] : 'all-places';
}

/**
 * Determine the primary category for a given post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lbhotel_get_primary_category_slug( $post_id ) {
    $terms = get_the_terms( $post_id, 'lbhotel_place_category' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return lbhotel_get_default_category_slug();
    }

    $map   = lbhotel_get_category_template_map();
    $order = array_keys( $map );
    $terms = array_filter(
        $terms,
        static function ( $term ) use ( $map ) {
            return isset( $map[ $term->slug ] );
        }
    );

    if ( empty( $terms ) ) {
        return lbhotel_get_default_category_slug();
    }

    $term_slugs = wp_list_pluck( $terms, 'slug' );

    foreach ( $order as $candidate ) {
        if ( in_array( $candidate, $term_slugs, true ) ) {
            return $candidate;
        }
    }

    return array_shift( $term_slugs );
}

/**
 * Resolve the template context for the current request.
 *
 * @return array<string,string>|null
 */
function lbhotel_get_request_template_context() {
    if ( is_singular( 'lbhotel_hotel' ) ) {
        $post_id = get_queried_object_id();
        if ( $post_id ) {
            return array(
                'category' => lbhotel_get_primary_category_slug( $post_id ),
                'type'     => 'single',
            );
        }

        return array(
            'category' => lbhotel_get_default_category_slug(),
            'type'     => 'single',
        );
    }

    if ( is_tax( 'lbhotel_place_category' ) ) {
        $term = get_queried_object();
        $slug = $term instanceof WP_Term ? $term->slug : '';

        return array(
            'category' => lbhotel_normalize_category_slug( $slug ),
            'type'     => 'archive',
        );
    }

    if ( is_post_type_archive( 'lbhotel_hotel' ) ) {
        return array(
            'category' => lbhotel_get_default_category_slug(),
            'type'     => 'archive',
        );
    }

    return null;
}

/**
 * Persist the template context for downstream usage.
 *
 * @param array<string,string>|null $context Context data.
 * @return array<string,string>|null
 */
function lbhotel_set_template_context( $context ) {
    if ( ! is_array( $context ) ) {
        $GLOBALS['lbhotel_template_context'] = null;

        return null;
    }

    $category = lbhotel_normalize_category_slug( isset( $context['category'] ) ? $context['category'] : '' );
    $type     = isset( $context['type'] ) && in_array( $context['type'], array( 'single', 'archive' ), true ) ? $context['type'] : 'single';

    $resolved = array(
        'category' => $category,
        'type'     => $type,
    );

    $GLOBALS['lbhotel_template_context'] = $resolved;

    return $resolved;
}

/**
 * Retrieve the current template context.
 *
 * @return array<string,string>|null
 */
function lbhotel_get_template_context() {
    return isset( $GLOBALS['lbhotel_template_context'] ) ? $GLOBALS['lbhotel_template_context'] : null;
}

/**
 * Bootstrap template context after WordPress resolves the query.
 */
function lbhotel_prepare_template_context() {
    lbhotel_set_template_context( lbhotel_get_request_template_context() );
}
add_action( 'wp', 'lbhotel_prepare_template_context' );

/**
 * Filter template loading to include category-specific templates.
 *
 * @param string $template Current template.
 * @return string
 */
function lbhotel_template_include( $template ) {
    $context = lbhotel_get_template_context();

    if ( ! $context ) {
        return $template;
    }

    $path = lbhotel_get_category_template_path( $context['category'], $context['type'], 'template' );

    return $path ? $path : $template;
}
add_filter( 'template_include', 'lbhotel_template_include', 20 );
