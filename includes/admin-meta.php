<?php
/**
 * Admin meta boxes and meta registration.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register post meta for hotels.
 */
function lbhotel_register_meta_fields() {
    $definitions = lbhotel_get_all_field_definitions();

    foreach ( $definitions as $meta_key => $definition ) {
        $type      = isset( $definition['type'] ) ? $definition['type'] : 'string';
        $sanitize  = isset( $definition['sanitize_callback'] ) ? $definition['sanitize_callback'] : 'sanitize_text_field';

        register_post_meta(
            'lbhotel_hotel',
            $meta_key,
            array(
                'type'              => $type,
                'single'            => true,
                'sanitize_callback' => $sanitize,
                'auth_callback'     => 'lbhotel_can_edit_meta',
                'show_in_rest'      => true,
            )
        );
    }
}

/**
 * Get the maximum number of gallery images allowed.
 *
 * @return int
 */
function lbhotel_get_gallery_max_images() {
    $max_images = (int) apply_filters( 'lbhotel_gallery_max_images', 5 );

    if ( $max_images <= 0 ) {
        $max_images = 5;
    }

    return $max_images;
}

/**
 * Sanitize gallery images meta.
 *
 * @param mixed $value Value.
 * @return array
 */
function lbhotel_sanitize_gallery_images( $value ) {
    if ( is_string( $value ) ) {
        $value = explode( ',', $value );
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    $value = array_map( 'absint', $value );
    $value = array_unique( $value );
    $value = array_filter( $value );

    $value = array_values( $value );

    $max_images = lbhotel_get_gallery_max_images();

    if ( $max_images > 0 ) {
        $value = array_slice( $value, 0, $max_images );
    }

    return $value;
}

/**
 * Basic phone sanitizer.
 *
 * @param string $value Phone.
 * @return string
 */
function lbhotel_sanitize_phone( $value ) {
    $value = preg_replace( '/[^0-9+\-\s]/', '', (string) $value );

    return sanitize_text_field( $value );
}

/**
 * Auth callback for post meta.
 *
 * @param bool   $allowed Whether allowed.
 * @param string $meta_key Meta key.
 * @param int    $post_id Post ID.
 * @param int    $user_id User ID.
 * @param string $cap Capability.
 * @param array  $caps Caps.
 * @return bool
 */
function lbhotel_can_edit_meta( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
    unset( $allowed, $meta_key, $user_id, $cap, $caps );

    return current_user_can( 'edit_post', $post_id );
}

/**
 * Setup admin meta boxes.
 */
function lbhotel_setup_admin_meta_boxes() {
    static $bootstrapped = false;

    if ( $bootstrapped ) {
        return;
    }

    $bootstrapped = true;

    add_action( 'add_meta_boxes_lbhotel_hotel', 'lbhotel_register_meta_boxes' );
    add_action( 'save_post_lbhotel_hotel', 'lbhotel_save_meta', 10, 2 );
    add_action( 'post_edit_form_tag', 'lbhotel_enable_gallery_uploads_form_enctype' );
}

/**
 * Ensure the hotel edit form supports file uploads.
 */
function lbhotel_enable_gallery_uploads_form_enctype() {
    global $typenow;

    if ( 'lbhotel_hotel' === $typenow ) {
        echo ' enctype="multipart/form-data"';
    }
}

/**
 * Retrieve global field definitions constrained to a section.
 *
 * @param string $section Section key.
 * @return array<string,array<string,mixed>>
 */
function lbhotel_get_global_fields_by_section( $section ) {
    $fields = array();

    foreach ( lbhotel_get_global_field_definitions() as $meta_key => $definition ) {
        if ( isset( $definition['section'] ) && $section === $definition['section'] ) {
            $fields[ $meta_key ] = $definition;
        }
    }

    return $fields;
}

/**
 * Render an individual meta input field.
 *
 * @param string $meta_key   Meta key.
 * @param array  $definition Field definition.
 * @param mixed  $value      Current value.
 */
function lbhotel_render_meta_input_field( $meta_key, $definition, $value ) {
    $label       = isset( $definition['label'] ) ? $definition['label'] : '';
    $input       = isset( $definition['input'] ) ? $definition['input'] : 'text';
    $placeholder = isset( $definition['placeholder'] ) ? $definition['placeholder'] : '';
    $description = isset( $definition['description'] ) ? $definition['description'] : '';
    $attributes  = '';

    if ( ! empty( $definition['attributes'] ) && is_array( $definition['attributes'] ) ) {
        foreach ( $definition['attributes'] as $attr_key => $attr_value ) {
            $attributes .= ' ' . esc_attr( $attr_key ) . '="' . esc_attr( $attr_value ) . '"';
        }
    }

    $field_id = $meta_key;

    echo '<p class="lbhotel-meta-field">';
    if ( $label ) {
        echo '<label for="' . esc_attr( $field_id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br />';
    }

    switch ( $input ) {
        case 'textarea':
            echo '<textarea class="widefat" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $meta_key ) . '" rows="4"' . $attributes . '>' . esc_textarea( $value ) . '</textarea>';
            break;
        case 'number':
        case 'url':
        case 'text':
        case 'datetime-local':
            echo '<input type="' . esc_attr( $input ) . '" class="widefat" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $value ) . '"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . $attributes . ' />';
            break;
        default:
            echo '<input type="text" class="widefat" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $value ) . '"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . $attributes . ' />';
            break;
    }

    if ( $description ) {
        echo '<span class="description">' . esc_html( $description ) . '</span>';
    }

    echo '</p>';
}

/**
 * Persist a single meta field based on submitted data.
 *
 * @param int   $post_id    Post ID.
 * @param string $meta_key  Meta key.
 * @param array  $definition Field definition.
 */
function lbhotel_save_single_meta_field( $post_id, $meta_key, $definition ) {
    $sanitize = isset( $definition['sanitize_callback'] ) ? $definition['sanitize_callback'] : 'sanitize_text_field';

    if ( isset( $_POST[ $meta_key ] ) ) {
        $raw        = wp_unslash( $_POST[ $meta_key ] );
        $raw_string = is_string( $raw ) ? trim( $raw ) : $raw;

        if ( ( '' === $raw_string || null === $raw_string ) && '0' !== $raw_string ) {
            delete_post_meta( $post_id, $meta_key );
            return;
        }

        $value = call_user_func( $sanitize, $raw );

        if ( is_string( $value ) ) {
            $trimmed_value = trim( $value );
            if ( '' === $trimmed_value && '0' !== $value ) {
                delete_post_meta( $post_id, $meta_key );
                return;
            }
        }

        if ( is_array( $value ) && empty( $value ) ) {
            delete_post_meta( $post_id, $meta_key );
            return;
        }

        update_post_meta( $post_id, $meta_key, $value );
    } else {
        delete_post_meta( $post_id, $meta_key );
    }
}

/**
 * Derive the category slugs submitted in the current request.
 *
 * @param int $post_id Post ID.
 * @return array<string>
 */
function lbhotel_get_submitted_category_slugs( $post_id ) {
    $slugs = array();

    if ( isset( $_POST['tax_input']['lbhotel_place_category'] ) ) {
        $submitted = wp_unslash( $_POST['tax_input']['lbhotel_place_category'] );

        if ( is_array( $submitted ) ) {
            foreach ( $submitted as $value ) {
                if ( is_numeric( $value ) ) {
                    $term = get_term( (int) $value, 'lbhotel_place_category' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $slugs[] = $term->slug;
                    }
                } elseif ( is_string( $value ) ) {
                    $slugs[] = sanitize_title( $value );
                }
            }
        } elseif ( is_string( $submitted ) ) {
            $parts = array_map( 'trim', explode( ',', $submitted ) );
            foreach ( $parts as $part ) {
                if ( '' !== $part ) {
                    if ( is_numeric( $part ) ) {
                        $term = get_term( (int) $part, 'lbhotel_place_category' );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $slugs[] = $term->slug;
                        }
                    } else {
                        $slugs[] = sanitize_title( $part );
                    }
                }
            }
        }
    }

    if ( empty( $slugs ) ) {
        $terms = wp_get_post_terms( $post_id, 'lbhotel_place_category', array( 'fields' => 'slugs' ) );
        if ( ! is_wp_error( $terms ) ) {
            $slugs = $terms;
        }
    }

    return array_values( array_unique( array_filter( $slugs ) ) );
}

/**
 * Register hotel meta boxes.
 */
function lbhotel_register_meta_boxes() {
    add_meta_box(
        'lbhotel_details_meta',
        __( 'Global Details', 'lbhotel' ),
        'lbhotel_render_details_meta_box',
        'lbhotel_hotel',
        'normal',
        'high'
    );

    add_meta_box(
        'lbhotel_location_meta',
        __( 'Location', 'lbhotel' ),
        'lbhotel_render_location_meta_box',
        'lbhotel_hotel',
        'normal',
        'default'
    );

    add_meta_box(
        'lbhotel_category_meta',
        __( 'Category Specific Fields', 'lbhotel' ),
        'lbhotel_render_category_meta_box',
        'lbhotel_hotel',
        'normal',
        'default'
    );

    add_meta_box(
        'lbhotel_media_meta',
        __( 'Media', 'lbhotel' ),
        'lbhotel_render_media_meta_box',
        'lbhotel_hotel',
        'side',
        'default'
    );
}

/**
 * Render global details meta box.
 *
 * @param WP_Post $post Post object.
 */
function lbhotel_render_details_meta_box( $post ) {
    wp_nonce_field( 'lbhotel_save_meta', 'lbhotel_meta_nonce' );

    echo '<p class="description">' . esc_html__( 'These fields apply to every virtualized place regardless of category.', 'lbhotel' ) . '</p>';

    foreach ( lbhotel_get_global_fields_by_section( 'details' ) as $meta_key => $definition ) {
        $value = get_post_meta( $post->ID, $meta_key, true );
        lbhotel_render_meta_input_field( $meta_key, $definition, $value );
    }
}

/**
 * Render the location meta box.
 *
 * @param WP_Post $post Post object.
 */
function lbhotel_render_location_meta_box( $post ) {
    echo '<p class="description">' . esc_html__( 'Provide precise location information so visitors can find the place easily.', 'lbhotel' ) . '</p>';

    foreach ( lbhotel_get_global_fields_by_section( 'location' ) as $meta_key => $definition ) {
        $value = get_post_meta( $post->ID, $meta_key, true );
        lbhotel_render_meta_input_field( $meta_key, $definition, $value );
    }
}

/**
 * Render the category-specific meta box.
 *
 * @param WP_Post $post Post object.
 */
function lbhotel_render_category_meta_box( $post ) {
    $categories         = lbhotel_get_place_category_labels();
    $category_fields    = lbhotel_get_category_field_definitions();
    $selected_categories = wp_get_post_terms( $post->ID, 'lbhotel_place_category', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $selected_categories ) ) {
        $selected_categories = array();
    }

    echo '<p class="description">' . esc_html__( 'Select one or more categories in the taxonomy box to reveal the relevant fields below.', 'lbhotel' ) . '</p>';

    foreach ( $categories as $slug => $label ) {
        $fields_for_category = lbhotel_get_fields_for_category( $slug );

        if ( empty( $fields_for_category ) ) {
            continue;
        }

        $is_active = in_array( $slug, $selected_categories, true );
        $classes   = 'lbhotel-category-field';

        if ( $is_active ) {
            $classes .= ' is-active';
        }

        $term     = get_term_by( 'slug', $slug, 'lbhotel_place_category' );
        $term_id  = ( $term && ! is_wp_error( $term ) ) ? (string) $term->term_id : '';

        echo '<div class="' . esc_attr( $classes ) . '" data-category="' . esc_attr( $slug ) . '" data-term-id="' . esc_attr( $term_id ) . '"' . ( $is_active ? '' : ' style="display:none;"' ) . '>';
        echo '<h4>' . esc_html( $label ) . '</h4>';

        foreach ( $fields_for_category as $meta_key => $definition ) {
            $value = get_post_meta( $post->ID, $meta_key, true );
            lbhotel_render_meta_input_field( $meta_key, $definition, $value );
        }

        echo '</div>';
    }
}

/**
 * Render the media meta box (gallery uploads).
 *
 * @param WP_Post $post Post object.
 */
function lbhotel_render_media_meta_box( $post ) {
    $gallery    = get_post_meta( $post->ID, 'lbhotel_gallery_images', true );
    $gallery    = lbhotel_sanitize_gallery_images( $gallery );
    $max_images = lbhotel_get_gallery_max_images();
    $remaining  = max( 0, $max_images - count( $gallery ) );

    echo '<div id="lbhotel-gallery-field" class="lbhotel-gallery-field" data-max="' . esc_attr( $max_images ) . '">';
    echo '<p class="lbhotel-gallery-label"><label for="lbhotel_gallery_upload">' . esc_html__( 'Gallery images', 'lbhotel' ) . '</label></p>';
    echo '<ul class="lbhotel-gallery-list">';

    foreach ( $gallery as $image_id ) {
        $thumbnail = wp_get_attachment_image( $image_id, 'thumbnail', false );

        if ( ! $thumbnail ) {
            continue;
        }

        echo '<li class="lbhotel-gallery-item">';
        echo '<div class="lbhotel-gallery-thumb">' . $thumbnail . '</div>';
        echo '<label class="lbhotel-gallery-remove">';
        echo '<input type="checkbox" name="lbhotel_gallery_remove[]" value="' . esc_attr( $image_id ) . '" /> ' . esc_html__( 'Remove', 'lbhotel' );
        echo '</label>';
        echo '<input type="hidden" name="lbhotel_existing_gallery[]" value="' . esc_attr( $image_id ) . '" />';
        echo '</li>';
    }

    echo '</ul>';

    if ( empty( $gallery ) ) {
        echo '<p class="lbhotel-gallery-empty">' . esc_html__( 'No images uploaded yet.', 'lbhotel' ) . '</p>';
    }

    $disabled_attribute = ( $max_images > 0 && $remaining <= 0 ) ? ' disabled="disabled"' : '';

    echo '<p><input type="file" id="lbhotel_gallery_upload" name="lbhotel_gallery_upload[]" accept="image/*" multiple' . $disabled_attribute . ' /></p>';

    if ( $max_images > 0 ) {
        if ( $remaining > 0 ) {
            echo '<p class="description">' . sprintf( esc_html__( 'You can upload up to %1$d images. %2$d remaining.', 'lbhotel' ), $max_images, $remaining ) . '</p>';
        } else {
            echo '<p class="description">' . esc_html__( 'You have reached the maximum number of gallery images.', 'lbhotel' ) . '</p>';
        }
    }

    echo '</div>';
}

/**
 * Save meta box data.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function lbhotel_save_meta( $post_id, $post ) {
    if ( ! isset( $_POST['lbhotel_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbhotel_meta_nonce'] ) ), 'lbhotel_save_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $global_fields   = lbhotel_get_global_field_definitions();
    $category_fields = lbhotel_get_category_field_definitions();

    foreach ( $global_fields as $meta_key => $definition ) {
        if ( 'gallery' === ( $definition['input'] ?? '' ) ) {
            continue;
        }

        lbhotel_save_single_meta_field( $post_id, $meta_key, $definition );
    }

    $selected_categories = lbhotel_get_submitted_category_slugs( $post_id );

    foreach ( $category_fields as $meta_key => $definition ) {
        $applies = empty( $definition['applies_to'] ) || array_intersect( $definition['applies_to'], $selected_categories );

        if ( $applies ) {
            lbhotel_save_single_meta_field( $post_id, $meta_key, $definition );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }

    $existing_gallery = array();
    if ( isset( $_POST['lbhotel_existing_gallery'] ) ) {
        $existing_gallery = array_map( 'absint', (array) wp_unslash( $_POST['lbhotel_existing_gallery'] ) );
    }

    $removed_gallery = array();
    if ( isset( $_POST['lbhotel_gallery_remove'] ) ) {
        $removed_gallery = array_map( 'absint', (array) wp_unslash( $_POST['lbhotel_gallery_remove'] ) );
    }

    $gallery = array();

    foreach ( $existing_gallery as $image_id ) {
        if ( ! $image_id || in_array( $image_id, $removed_gallery, true ) ) {
            continue;
        }

        $gallery[] = $image_id;
    }

    $max_images = lbhotel_get_gallery_max_images();

    if ( isset( $_FILES['lbhotel_gallery_upload'] ) && isset( $_FILES['lbhotel_gallery_upload']['name'] ) && is_array( $_FILES['lbhotel_gallery_upload']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploads = $_FILES['lbhotel_gallery_upload'];

        foreach ( $uploads['name'] as $index => $unused ) {
            if ( empty( $uploads['name'][ $index ] ) ) {
                continue;
            }

            if ( ! empty( $uploads['error'][ $index ] ) && UPLOAD_ERR_OK !== $uploads['error'][ $index ] ) {
                continue;
            }

            if ( $max_images > 0 && count( $gallery ) >= $max_images ) {
                break;
            }

            $file_key = 'lbhotel_gallery_upload_' . $index;
            $_FILES[ $file_key ] = array(
                'name'     => sanitize_file_name( wp_unslash( $uploads['name'][ $index ] ) ),
                'type'     => $uploads['type'][ $index ],
                'tmp_name' => $uploads['tmp_name'][ $index ],
                'error'    => $uploads['error'][ $index ],
                'size'     => $uploads['size'][ $index ],
            );

            $attachment_id = media_handle_upload( $file_key, $post_id );

            unset( $_FILES[ $file_key ] );

            if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
                continue;
            }

            if ( in_array( $attachment_id, $gallery, true ) ) {
                continue;
            }

            $gallery[] = $attachment_id;

            if ( $max_images > 0 && count( $gallery ) >= $max_images ) {
                break;
            }
        }
    }

    $gallery = lbhotel_sanitize_gallery_images( $gallery );

    if ( ! empty( $gallery ) ) {
        update_post_meta( $post_id, 'lbhotel_gallery_images', $gallery );
    } else {
        delete_post_meta( $post_id, 'lbhotel_gallery_images' );
    }
}

