<?php
/**
 * Import and export tools for hotel data.
 *
 * @package LeBonHotel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles CSV and JSON import/export for hotel listings.
 */
class LBHotel_Import_Export {

    const EXPORT_ACTION = 'lbhotel_export';
    const IMPORT_ACTION = 'lbhotel_import';

    /**
     * Transient key prefix for admin notices.
     *
     * @var string
     */
    protected $notice_transient_prefix = 'lbhotel_import_export_notices_';

    /**
     * Singleton instance.
     *
     * @var LBHotel_Import_Export|null
     */
    protected static $instance = null;

    /**
     * Instantiate the class and register hooks.
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu_hooks' ), 20 );
        add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export_request' ) );
        add_action( 'admin_post_' . self::IMPORT_ACTION, array( $this, 'handle_import_request' ) );
        add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
    }

    /**
     * Retrieve singleton instance.
     *
     * @return LBHotel_Import_Export
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Hooked into admin_menu to bootstrap screen specific actions.
     */
    public function register_menu_hooks() {
        add_action( 'load-hotel_page_lbhotel-settings', array( $this, 'maybe_register_screen_options' ) );
    }

    /**
     * Placeholder callback for future screen hooks.
     */
    public function maybe_register_screen_options() {
        // Reserved for future enhancements.
    }

    /**
     * Output any stored admin notices.
     */
    public function render_admin_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notices = $this->get_stored_notices();

        if ( empty( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            if ( empty( $notice['message'] ) ) {
                continue;
            }

            $class   = ( isset( $notice['type'] ) && 'error' === $notice['type'] ) ? 'notice notice-error' : 'notice notice-success';
            $message = $notice['message'];

            echo '<div class="' . esc_attr( $class ) . '"><p>' . wp_kses_post( $message ) . '</p></div>';
        }

        $this->clear_stored_notices();
    }

    /**
     * Handle export submission.
     */
    public function handle_export_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export data.', 'lbhotel' ) );
        }

        check_admin_referer( 'lbhotel_export_nonce' );

        $format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'csv';
        $format = in_array( $format, array( 'csv', 'json' ), true ) ? $format : 'csv';

        $hotels = $this->get_hotels_data();
        $date   = gmdate( 'Ymd_His' );

        if ( 'json' === $format ) {
            $filename = 'hotels_export_' . $date . '.json';
            nocache_headers();
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );

            $payload = array(
                'meta'   => array(
                    'exported_at' => current_time( 'mysql' ),
                    'format'      => 'json',
                ),
                'hotels' => $hotels,
            );

            echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            exit;
        }

        $filename = 'hotels_export_' . $date . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output  = fopen( 'php://output', 'w' );
        $headers = array_keys( $this->get_field_map() );
        fputcsv( $output, $headers );

        foreach ( $hotels as $hotel ) {
            $row = array();

            foreach ( $headers as $header ) {
                $value = isset( $hotel[ $header ] ) ? $hotel[ $header ] : '';

                if ( 'gallery_images' === $header && is_array( $value ) ) {
                    $value = implode( '|', array_map( 'strval', $value ) );
                }

                if ( is_array( $value ) ) {
                    $value = wp_json_encode( $value );
                }

                $row[] = $value;
            }

            fputcsv( $output, $row );
        }

        fputcsv( $output, array( '# Exported at', current_time( 'mysql' ) ) );
        fclose( $output );
        exit;
    }

    /**
     * Handle import submission.
     */
    public function handle_import_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to import data.', 'lbhotel' ) );
        }

        check_admin_referer( 'lbhotel_import_nonce' );

        if ( empty( $_FILES['lbhotel_import_file'] ) || ! isset( $_FILES['lbhotel_import_file']['tmp_name'] ) ) {
            $this->add_admin_notice( esc_html__( 'No file was uploaded.', 'lbhotel' ), 'error' );
            $this->redirect_back();
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $overrides = array(
            'test_form' => false,
            'mimes'     => array(
                'csv'  => 'text/csv',
                'json' => 'application/json',
            ),
        );

        $uploaded = wp_handle_upload( $_FILES['lbhotel_import_file'], $overrides );

        if ( isset( $uploaded['error'] ) ) {
            $this->add_admin_notice( esc_html( $uploaded['error'] ), 'error' );
            $this->redirect_back();
        }

        $file_path = $uploaded['file'];
        $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

        if ( ! in_array( $extension, array( 'csv', 'json' ), true ) ) {
            $this->add_admin_notice( esc_html__( 'Unsupported file type. Please upload a CSV or JSON file.', 'lbhotel' ), 'error' );
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
            $this->redirect_back();
        }

        $results = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => array(),
        );

        if ( 'json' === $extension ) {
            $data = file_get_contents( $file_path );
            $data = json_decode( $data, true );

            if ( null === $data ) {
                $results['errors'][] = esc_html__( 'Invalid JSON file format.', 'lbhotel' );
            } else {
                $records = isset( $data['hotels'] ) && is_array( $data['hotels'] ) ? $data['hotels'] : $data;
                $results = $this->process_import_records( $records, $results );
            }
        } else {
            $handle = fopen( $file_path, 'r' );

            if ( ! $handle ) {
                $results['errors'][] = esc_html__( 'Unable to open the uploaded CSV file.', 'lbhotel' );
            } else {
                $headers = array();

                while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                    if ( empty( $headers ) ) {
                        $headers = $this->normalize_headers( $row );

                        if ( ! $this->validate_headers( $headers ) ) {
                            $results['errors'][] = esc_html__( 'The CSV file headers are invalid.', 'lbhotel' );
                            break;
                        }

                        continue;
                    }

                    if ( $this->is_comment_row( $row ) ) {
                        continue;
                    }

                    $record = array();

                    foreach ( $headers as $index => $header ) {
                        $record[ $header ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                    }

                    if ( $this->is_empty_record( $record ) ) {
                        continue;
                    }

                    $results = $this->process_import_records( array( $record ), $results );
                }

                fclose( $handle );
            }
        }

        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        $this->handle_import_results( $results );
    }

    /**
     * Process import records.
     *
     * @param array $records Records to import.
     * @param array $results Running results tally.
     * @return array
     */
    protected function process_import_records( $records, $results ) {
        if ( empty( $records ) || ! is_array( $records ) ) {
            return $results;
        }

        foreach ( $records as $record ) {
            if ( ! is_array( $record ) ) {
                continue;
            }

            $record = $this->sanitize_record_keys( $record );

            $title = isset( $record['post_title'] ) ? trim( wp_unslash( $record['post_title'] ) ) : '';

            if ( '' === $title ) {
                $results['skipped']++;
                continue;
            }

            $existing_id = $this->locate_existing_post( $record );
            $post_status = $this->sanitize_post_status( isset( $record['post_status'] ) ? $record['post_status'] : 'publish' );

            $postarr = array(
                'post_title'   => sanitize_text_field( $title ),
                'post_content' => isset( $record['post_content'] ) ? wp_kses_post( $record['post_content'] ) : '',
                'post_excerpt' => isset( $record['post_excerpt'] ) ? sanitize_textarea_field( $record['post_excerpt'] ) : '',
                'post_status'  => $post_status,
                'post_type'    => 'lbhotel_hotel',
            );

            if ( $existing_id ) {
                $postarr['ID'] = $existing_id;
            }

            $post_id = wp_insert_post( $postarr, true );

            if ( is_wp_error( $post_id ) ) {
                $results['errors'][] = sprintf(
                    /* translators: 1: Hotel title, 2: Error message */
                    esc_html__( 'Failed to import "%1$s": %2$s', 'lbhotel' ),
                    $title,
                    $post_id->get_error_message()
                );
                continue;
            }

            if ( $existing_id ) {
                $results['updated']++;
            } else {
                $results['created']++;
            }

            $this->update_meta_from_record( $post_id, $record );
        }

        return $results;
    }

    /**
     * Update meta fields for a given record.
     *
     * @param int   $post_id Post ID.
     * @param array $record  Normalized record data.
     */
    protected function update_meta_from_record( $post_id, $record ) {
        $map = $this->get_field_map();

        foreach ( $map as $label => $details ) {
            if ( 'meta' !== $details['type'] ) {
                continue;
            }

            $normalized_key = $this->normalize_label_key( $label );

            if ( ! array_key_exists( $normalized_key, $record ) ) {
                continue;
            }

            $value = $record[ $normalized_key ];

            switch ( $normalized_key ) {
                case 'city':
                case 'region':
                case 'country':
                    $value = sanitize_text_field( $value );
                    break;
                case 'booking_url':
                case 'virtual_tour_url':
                    $value = '' === trim( (string) $value ) ? '' : esc_url_raw( $value );
                    break;
                case 'star_rating':
                case 'rooms_total':
                    $value = $this->sanitize_numeric_meta( $value, 'int' );
                    break;
                case 'avg_price_per_night':
                    $value = $this->sanitize_numeric_meta( $value, 'float' );
                    break;
                case 'lat':
                case 'lng':
                    $value = $this->sanitize_coordinate( $value );
                    break;
                case 'gallery_images':
                    $value = $this->prepare_gallery_value( $value );
                    break;
                default:
                    if ( is_array( $value ) ) {
                        $value = array_map( 'sanitize_text_field', $value );
                    } else {
                        $value = sanitize_text_field( $value );
                    }
                    break;
            }

            update_post_meta( $post_id, $details['key'], $value );
        }
    }

    /**
     * Prepare gallery field value into sanitized array.
     *
     * @param mixed $value Raw value.
     * @return array
     */
    protected function prepare_gallery_value( $value ) {
        if ( empty( $value ) && '0' !== $value ) {
            return array();
        }

        if ( is_string( $value ) ) {
            $trimmed = trim( $value );

            if ( '' === $trimmed ) {
                return array();
            }

            if ( $this->looks_like_json( $trimmed ) ) {
                $decoded = json_decode( $trimmed, true );
                if ( is_array( $decoded ) ) {
                    $value = $decoded;
                } else {
                    $value = preg_split( '/[|,]/', $trimmed );
                }
            } else {
                $value = preg_split( '/[|,]/', $trimmed );
            }
        }

        if ( ! is_array( $value ) ) {
            $value = array( $value );
        }

        $sanitized = array();

        foreach ( $value as $item ) {
            if ( '' === $item && '0' !== $item ) {
                continue;
            }

            if ( is_numeric( $item ) ) {
                $sanitized[] = absint( $item );
            } else {
                $sanitized[] = esc_url_raw( $item );
            }
        }

        return $sanitized;
    }

    /**
     * Determine if a string appears to be JSON encoded.
     *
     * @param string $value Value to inspect.
     * @return bool
     */
    protected function looks_like_json( $value ) {
        $first = substr( $value, 0, 1 );

        return ( '[' === $first || '{' === $first );
    }

    /**
     * Sanitize numeric meta values.
     *
     * @param mixed  $value Raw value.
     * @param string $type  Type: int or float.
     * @return string|int|float
     */
    protected function sanitize_numeric_meta( $value, $type = 'int' ) {
        if ( is_array( $value ) ) {
            return '';
        }

        $value = trim( (string) $value );

        if ( '' === $value ) {
            return '';
        }

        if ( 'float' === $type ) {
            return lbhotel_sanitize_decimal( $value );
        }

        return lbhotel_sanitize_int( $value );
    }

    /**
     * Sanitize coordinate value.
     *
     * @param mixed $value Raw value.
     * @return string|float
     */
    protected function sanitize_coordinate( $value ) {
        return $this->sanitize_numeric_meta( $value, 'float' );
    }

    /**
     * Locate an existing hotel post via ID or title.
     *
     * @param array $record Normalized record.
     * @return int
     */
    protected function locate_existing_post( $record ) {
        $post_id = 0;

        if ( ! empty( $record['id'] ) ) {
            $maybe = absint( $record['id'] );

            if ( $maybe && 'lbhotel_hotel' === get_post_type( $maybe ) ) {
                $post_id = $maybe;
            }
        }

        if ( ! $post_id && ! empty( $record['post_title'] ) ) {
            $existing = get_page_by_title( wp_strip_all_tags( $record['post_title'] ), OBJECT, 'lbhotel_hotel' );

            if ( $existing ) {
                $post_id = (int) $existing->ID;
            }
        }

        return $post_id;
    }

    /**
     * Normalize post status value.
     *
     * @param string $status Raw status.
     * @return string
     */
    protected function sanitize_post_status( $status ) {
        $status   = sanitize_key( $status );
        $allowed  = get_post_stati();
        $fallback = 'draft';

        return in_array( $status, $allowed, true ) ? $status : $fallback;
    }

    /**
     * Sanitize record keys.
     *
     * @param array $record Raw record.
     * @return array
     */
    protected function sanitize_record_keys( $record ) {
        $normalized = array();

        foreach ( $record as $key => $value ) {
            $normalized_key = $this->normalize_label_key( $key );
            $normalized[ $normalized_key ] = $value;
        }

        return $normalized;
    }

    /**
     * Normalize CSV headers.
     *
     * @param array $headers Header row.
     * @return array
     */
    protected function normalize_headers( $headers ) {
        $normalized = array();

        foreach ( $headers as $index => $header ) {
            $normalized[ $index ] = $this->normalize_label_key( $header );
        }

        return $normalized;
    }

    /**
     * Normalize a field label to an array key.
     *
     * @param string $label Label to normalize.
     * @return string
     */
    protected function normalize_label_key( $label ) {
        $normalized = strtolower( trim( (string) $label ) );
        $normalized = str_replace( array( ' ', '-' ), '_', $normalized );

        return $normalized;
    }

    /**
     * Validate CSV headers.
     *
     * @param array $headers Header list.
     * @return bool
     */
    protected function validate_headers( $headers ) {
        return in_array( 'post_title', $headers, true );
    }

    /**
     * Determine if a CSV row is a footer/comment row.
     *
     * @param array $row CSV row.
     * @return bool
     */
    protected function is_comment_row( $row ) {
        if ( empty( $row ) || ! isset( $row[0] ) ) {
            return false;
        }

        $first_cell = trim( (string) $row[0] );

        return ( 0 === strpos( $first_cell, '#' ) );
    }

    /**
     * Determine if a record is empty.
     *
     * @param array $record Record data.
     * @return bool
     */
    protected function is_empty_record( $record ) {
        foreach ( $record as $value ) {
            if ( '' !== trim( (string) $value ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process import results and display notice.
     *
     * @param array $results Results summary.
     */
    protected function handle_import_results( $results ) {
        if ( ! empty( $results['errors'] ) ) {
            $message  = '<strong>' . esc_html__( 'Import completed with errors.', 'lbhotel' ) . '</strong><br />';
            $message .= esc_html( sprintf( __( 'Created: %1$d, Updated: %2$d, Skipped: %3$d', 'lbhotel' ), $results['created'], $results['updated'], $results['skipped'] ) );
            $message .= '<br />' . implode( '<br />', array_map( 'esc_html', $results['errors'] ) );

            $this->add_admin_notice( $message, 'error' );
        } else {
            $message = esc_html( sprintf( __( 'Import completed successfully. Created: %1$d, Updated: %2$d, Skipped: %3$d', 'lbhotel' ), $results['created'], $results['updated'], $results['skipped'] ) );
            $this->add_admin_notice( $message, 'success' );
        }

        $this->redirect_back();
    }

    /**
     * Redirect to settings page import/export tab.
     */
    protected function redirect_back() {
        $url = $this->get_settings_page_url( array( 'tab' => 'import-export' ) );
        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Retrieve settings page URL.
     *
     * @param array $args Additional query args.
     * @return string
     */
    protected function get_settings_page_url( $args = array() ) {
        $base_args = array(
            'post_type' => 'lbhotel_hotel',
            'page'      => 'lbhotel-settings',
        );

        if ( ! empty( $args ) ) {
            $base_args = array_merge( $base_args, $args );
        }

        return add_query_arg( $base_args, admin_url( 'edit.php' ) );
    }

    /**
     * Store admin notice for later display.
     *
     * @param string $message Message text.
     * @param string $type    Notice type.
     */
    protected function add_admin_notice( $message, $type = 'success' ) {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        $key     = $this->notice_transient_prefix . $user_id;
        $notices = get_transient( $key );

        if ( ! is_array( $notices ) ) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient( $key, $notices, MINUTE_IN_SECONDS * 5 );
    }

    /**
     * Retrieve stored notices.
     *
     * @return array
     */
    protected function get_stored_notices() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return array();
        }

        $key = $this->notice_transient_prefix . $user_id;
        $val = get_transient( $key );

        return is_array( $val ) ? $val : array();
    }

    /**
     * Clear stored notices after display.
     */
    protected function clear_stored_notices() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        delete_transient( $this->notice_transient_prefix . $user_id );
    }

    /**
     * Retrieve hotel data for export.
     *
     * @return array
     */
    protected function get_hotels_data() {
        $posts = get_posts(
            array(
                'post_type'      => 'lbhotel_hotel',
                'posts_per_page' => -1,
                'post_status'    => array_keys( get_post_stati() ),
            )
        );

        $fields = $this->get_field_map();
        $data   = array();

        foreach ( $posts as $post ) {
            $row = array();

            foreach ( $fields as $label => $details ) {
                if ( 'post' === $details['type'] ) {
                    $row[ $label ] = isset( $post->{$details['key']} ) ? $post->{$details['key']} : '';
                    continue;
                }

                $meta_value = get_post_meta( $post->ID, $details['key'], true );

                if ( 'gallery_images' === $label && ! is_array( $meta_value ) ) {
                    $meta_value = $meta_value ? array( $meta_value ) : array();
                }

                $row[ $label ] = $meta_value;
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Map export fields.
     *
     * @return array
     */
    protected function get_field_map() {
        return array(
            'ID'                 => array(
                'type' => 'post',
                'key'  => 'ID',
            ),
            'post_title'         => array(
                'type' => 'post',
                'key'  => 'post_title',
            ),
            'post_content'       => array(
                'type' => 'post',
                'key'  => 'post_content',
            ),
            'post_excerpt'       => array(
                'type' => 'post',
                'key'  => 'post_excerpt',
            ),
            'post_status'        => array(
                'type' => 'post',
                'key'  => 'post_status',
            ),
            'city'               => array(
                'type' => 'meta',
                'key'  => 'lbhotel_city',
            ),
            'region'             => array(
                'type' => 'meta',
                'key'  => 'lbhotel_region',
            ),
            'country'            => array(
                'type' => 'meta',
                'key'  => 'lbhotel_country',
            ),
            'star_rating'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_star_rating',
            ),
            'rooms_total'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_rooms_total',
            ),
            'avg_price_per_night'=> array(
                'type' => 'meta',
                'key'  => 'lbhotel_avg_price_per_night',
            ),
            'booking_url'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_booking_url',
            ),
            'gallery_images'     => array(
                'type' => 'meta',
                'key'  => 'lbhotel_gallery_images',
            ),
            'lat'                => array(
                'type' => 'meta',
                'key'  => 'lbhotel_latitude',
            ),
            'lng'                => array(
                'type' => 'meta',
                'key'  => 'lbhotel_longitude',
            ),
            'virtual_tour_url'   => array(
                'type' => 'meta',
                'key'  => 'lbhotel_virtual_tour_url',
            ),
        );
    }

    /**
     * Render Import / Export tab content.
     */
    public function render_tab_content() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $export_action = add_query_arg(
            array(
                'action' => self::EXPORT_ACTION,
            ),
            admin_url( 'admin-post.php' )
        );

        $import_action = add_query_arg(
            array(
                'action' => self::IMPORT_ACTION,
            ),
            admin_url( 'admin-post.php' )
        );
        ?>
        <div class="lbhotel-import-export">
            <div class="card">
                <h3><?php esc_html_e( 'Export Hotels', 'lbhotel' ); ?></h3>
                <p><?php esc_html_e( 'Download all hotel posts and their metadata as a CSV or JSON file.', 'lbhotel' ); ?></p>
                <form method="get" action="<?php echo esc_url( $export_action ); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr( self::EXPORT_ACTION ); ?>" />
                    <?php wp_nonce_field( 'lbhotel_export_nonce' ); ?>
                    <p>
                        <label><input type="radio" name="format" value="csv" checked /> <?php esc_html_e( 'CSV', 'lbhotel' ); ?></label>
                        <label style="margin-left:1em;"><input type="radio" name="format" value="json" /> <?php esc_html_e( 'JSON', 'lbhotel' ); ?></label>
                    </p>
                    <?php submit_button( __( 'Download Data', 'lbhotel' ), 'secondary', 'submit', false ); ?>
                </form>
            </div>

            <div class="card">
                <h3><?php esc_html_e( 'Import Hotels', 'lbhotel' ); ?></h3>
                <p><?php esc_html_e( 'Upload a CSV or JSON file exported from this plugin to create or update hotel listings.', 'lbhotel' ); ?></p>
                <form method="post" action="<?php echo esc_url( $import_action ); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>" />
                    <?php wp_nonce_field( 'lbhotel_import_nonce' ); ?>
                    <p>
                        <input type="file" name="lbhotel_import_file" accept=".csv,.json" required />
                    </p>
                    <p class="description"><?php esc_html_e( 'The importer matches existing hotels by ID (if present) or by title.', 'lbhotel' ); ?></p>
                    <?php submit_button( __( 'Import Now', 'lbhotel' ) ); ?>
                </form>
            </div>
        </div>
        <?php
    }
}
