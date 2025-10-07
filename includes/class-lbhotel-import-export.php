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
    const SAMPLE_ACTION = 'lbhotel_sample_csv';

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
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
        add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export_request' ) );
        add_action( 'admin_post_' . self::IMPORT_ACTION, array( $this, 'handle_import_request' ) );
        add_action( 'admin_post_' . self::SAMPLE_ACTION, array( $this, 'handle_sample_csv_request' ) );
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
    public function register_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=lbhotel_hotel',
            __( 'Import/Export Hotels', 'lbhotel' ),
            __( 'Import/Export', 'lbhotel' ),
            'manage_options',
            'hotel-import-export',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Output any stored admin notices.
     */
    public function render_admin_notices() {
        if ( ! current_user_can( 'manage_options' ) || ! $this->is_import_export_screen() ) {
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
     * Determine if the current screen is the Import/Export admin page.
     *
     * @return bool
     */
    protected function is_import_export_screen() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();

            if ( $screen && 'lbhotel_hotel_page_hotel-import-export' === $screen->id ) {
                return true;
            }
        }

        $page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

        return ( 'hotel-import-export' === $page && 'lbhotel_hotel' === $post_type );
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
            fputcsv( $output, $this->prepare_csv_row( $headers, $hotel ) );
        }

        fputcsv( $output, array( '# Exported at', current_time( 'mysql' ) ) );
        fclose( $output );
        exit;
    }

    /**
     * Download a sample CSV template.
     */
    public function handle_sample_csv_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export data.', 'lbhotel' ) );
        }

        check_admin_referer( 'lbhotel_sample_nonce' );

        $filename = 'hotels_sample_' . gmdate( 'Ymd_His' ) . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output  = fopen( 'php://output', 'w' );
        $headers = array_keys( $this->get_field_map() );
        fputcsv( $output, $headers );

        $sample = $this->get_sample_record();
        fputcsv( $output, $this->prepare_csv_row( $headers, $sample ) );
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
                $delimiter = $this->detect_csv_delimiter( $handle );
                rewind( $handle );

                $headers = array();

                while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
                    if ( empty( $headers ) ) {
                        $headers = $this->normalize_headers( $row );

                        if ( ! $this->validate_headers( $headers ) ) {
                            $results['errors'][] = esc_html__( 'The CSV file headers are invalid. Download the sample CSV to see the expected format.', 'lbhotel' );
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
                case 'address':
                case 'postal_code':
                    $value = sanitize_text_field( $value );
                    break;
                case 'city':
                case 'region':
                case 'country':
                    $value = sanitize_text_field( $value );
                    break;
                case 'checkin_time':
                case 'checkout_time':
                    $value = function_exists( 'lbhotel_sanitize_time' ) ? lbhotel_sanitize_time( $value ) : sanitize_text_field( $value );
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
                case 'has_free_breakfast':
                case 'has_parking':
                    $value = function_exists( 'lbhotel_sanitize_bool' ) ? lbhotel_sanitize_bool( $value ) : (bool) $value;
                    break;
                case 'lat':
                case 'lng':
                    $value = $this->sanitize_coordinate( $value );
                    break;
                case 'gallery_images':
                    $value = $this->prepare_gallery_value( $value );
                    break;
                case 'contact_phone':
                    $value = function_exists( 'lbhotel_sanitize_phone' ) ? lbhotel_sanitize_phone( $value ) : sanitize_text_field( $value );
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
     * Detect the most likely delimiter for a CSV file.
     *
     * @param resource $handle File handle.
     * @return string
     */
    protected function detect_csv_delimiter( $handle ) {
        $delimiters  = array( ',', ';', '\t' );
        $best        = ',';
        $best_fields = 1;
        $position    = ftell( $handle );

        if ( false === $position ) {
            $position = 0;
        }

        $lines = array();

        while ( count( $lines ) < 3 && ! feof( $handle ) ) {
            $line = fgets( $handle );

            if ( false === $line ) {
                break;
            }

            if ( '' !== trim( $line ) ) {
                $lines[] = $line;
            }
        }

        foreach ( $lines as $line ) {
            foreach ( $delimiters as $delimiter ) {
                $fields = str_getcsv( $line, $delimiter );

                if ( count( $fields ) > $best_fields ) {
                    $best_fields = count( $fields );
                    $best        = $delimiter;
                }
            }
        }

        fseek( $handle, $position );

        return $best;
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
     * Remove UTF-8 BOM from header cells.
     *
     * @param mixed $value Raw value.
     * @return mixed
     */
    protected function strip_utf8_bom( $value ) {
        if ( is_string( $value ) ) {
            return preg_replace( '/^\xEF\xBB\xBF/', '', $value );
        }

        return $value;
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
            $header               = $this->strip_utf8_bom( $header );
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
        $normalized = preg_replace( '/\[\]$/', '', $normalized );
        $normalized = preg_replace( '/[^a-z0-9_]/', '_', $normalized );
        $normalized = preg_replace( '/_+/', '_', $normalized );

        switch ( $normalized ) {
            case 'post_id':
                return 'id';
            case 'name':
            case 'hotel_name':
            case 'title':
                return 'post_title';
            case 'description':
            case 'details':
            case 'content':
                return 'post_content';
            case 'summary':
                return 'post_excerpt';
            case 'status':
                return 'post_status';
            case 'location':
                return 'city';
            case 'street':
            case 'address_line':
            case 'street_address':
                return 'address';
            case 'postalcode':
            case 'postcode':
            case 'zip':
            case 'zipcode':
                return 'postal_code';
            case 'checkin':
            case 'check_in':
                return 'checkin_time';
            case 'checkout':
            case 'check_out':
                return 'checkout_time';
            case 'price':
            case 'price_per_night':
            case 'average_price':
            case 'avg_price':
                return 'avg_price_per_night';
            case 'rating':
                return 'star_rating';
            case 'total_rooms':
                return 'rooms_total';
            case 'latitude':
                return 'lat';
            case 'longitude':
                return 'lng';
            case 'images':
            case 'gallery':
                return 'gallery_images';
            case 'virtual_tour':
            case 'virtualtour':
                return 'virtual_tour_url';
            case 'phone':
            case 'phone_number':
            case 'telephone':
                return 'contact_phone';
        }

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
    protected function redirect_back( $args = array() ) {
        $url = $this->get_import_export_page_url( $args );
        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Retrieve Import/Export page URL.
     *
     * @param array $args Additional query args.
     * @return string
     */
    protected function get_import_export_page_url( $args = array() ) {
        $base_args = array(
            'post_type' => 'lbhotel_hotel',
            'page'      => 'hotel-import-export',
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

                switch ( $label ) {
                    case 'gallery_images':
                        if ( ! is_array( $meta_value ) ) {
                            $meta_value = $meta_value ? array( $meta_value ) : array();
                        }

                        if ( function_exists( 'lbhotel_sanitize_gallery_images' ) ) {
                            $meta_value = lbhotel_sanitize_gallery_images( $meta_value );
                        }
                        break;
                    case 'has_free_breakfast':
                    case 'has_parking':
                        $meta_value = $meta_value ? 1 : 0;
                        break;
                    case 'avg_price_per_night':
                        if ( '' !== $meta_value && null !== $meta_value && function_exists( 'lbhotel_sanitize_decimal' ) ) {
                            $meta_value = lbhotel_sanitize_decimal( $meta_value );
                        }
                        break;
                    case 'rooms_total':
                    case 'star_rating':
                        if ( '' !== $meta_value && null !== $meta_value && function_exists( 'lbhotel_sanitize_int' ) ) {
                            $meta_value = lbhotel_sanitize_int( $meta_value );
                        }
                        break;
                    case 'lat':
                    case 'lng':
                        if ( '' !== $meta_value && null !== $meta_value && function_exists( 'lbhotel_sanitize_decimal' ) ) {
                            $meta_value = lbhotel_sanitize_decimal( $meta_value );
                        }
                        break;
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
            'address'            => array(
                'type' => 'meta',
                'key'  => 'lbhotel_address',
            ),
            'city'               => array(
                'type' => 'meta',
                'key'  => 'lbhotel_city',
            ),
            'region'             => array(
                'type' => 'meta',
                'key'  => 'lbhotel_region',
            ),
            'postal_code'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_postal_code',
            ),
            'country'            => array(
                'type' => 'meta',
                'key'  => 'lbhotel_country',
            ),
            'checkin_time'       => array(
                'type' => 'meta',
                'key'  => 'lbhotel_checkin_time',
            ),
            'checkout_time'      => array(
                'type' => 'meta',
                'key'  => 'lbhotel_checkout_time',
            ),
            'rooms_total'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_rooms_total',
            ),
            'avg_price_per_night'=> array(
                'type' => 'meta',
                'key'  => 'lbhotel_avg_price_per_night',
            ),
            'has_free_breakfast' => array(
                'type' => 'meta',
                'key'  => 'lbhotel_has_free_breakfast',
            ),
            'has_parking'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_has_parking',
            ),
            'star_rating'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_star_rating',
            ),
            'booking_url'        => array(
                'type' => 'meta',
                'key'  => 'lbhotel_booking_url',
            ),
            'contact_phone'      => array(
                'type' => 'meta',
                'key'  => 'lbhotel_contact_phone',
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
     * Provide a sample record for CSV template download.
     *
     * @return array
     */
    protected function get_sample_record() {
        return array(
            'ID'                 => '',
            'post_title'         => __( 'Sample Hotel', 'lbhotel' ),
            'post_content'       => __( 'Describe the hotel amenities, highlights, and nearby attractions.', 'lbhotel' ),
            'post_excerpt'       => __( 'A quick summary of the hotel.', 'lbhotel' ),
            'post_status'        => 'publish',
            'address'            => __( '123 Ocean Drive', 'lbhotel' ),
            'city'               => __( 'Casablanca', 'lbhotel' ),
            'region'             => __( 'Casablanca-Settat', 'lbhotel' ),
            'postal_code'        => '20000',
            'country'            => __( 'Morocco', 'lbhotel' ),
            'checkin_time'       => '15:00',
            'checkout_time'      => '11:00',
            'rooms_total'        => 120,
            'avg_price_per_night'=> 180,
            'has_free_breakfast' => 1,
            'has_parking'        => 1,
            'star_rating'        => 5,
            'booking_url'        => 'https://example.com/book-room',
            'contact_phone'      => '+212-600-123456',
            'gallery_images'     => array(
                'https://example.com/uploads/hotel-room.jpg',
                'https://example.com/uploads/hotel-lobby.jpg',
            ),
            'lat'                => '33.5731',
            'lng'                => '-7.5898',
            'virtual_tour_url'   => 'https://example.com/virtual-tour',
        );
    }

    /**
     * Prepare a CSV row for output.
     *
     * @param array $headers Header list.
     * @param array $record  Data record.
     * @return array
     */
    protected function prepare_csv_row( $headers, $record ) {
        $row = array();

        foreach ( $headers as $header ) {
            $value = isset( $record[ $header ] ) ? $record[ $header ] : '';

            if ( 'gallery_images' === $header ) {
                if ( is_array( $value ) ) {
                    $value = implode( '|', array_map( 'strval', $value ) );
                }
            } elseif ( in_array( $header, array( 'has_free_breakfast', 'has_parking' ), true ) ) {
                $value = $value ? 1 : 0;
            } elseif ( is_array( $value ) ) {
                $value = wp_json_encode( $value );
            }

            $row[] = $value;
        }

        return $row;
    }

    /**
     * Render Import / Export tab content.
     */
    public function render_admin_page() {
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

        $export_json_url = add_query_arg(
            array(
                'action'  => self::EXPORT_ACTION,
                'format'  => 'json',
                '_wpnonce'=> wp_create_nonce( 'lbhotel_export_nonce' ),
            ),
            admin_url( 'admin-post.php' )
        );

        $sample_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => self::SAMPLE_ACTION,
                ),
                admin_url( 'admin-post.php' )
            ),
            'lbhotel_sample_nonce'
        );
        ?>
        <div class="wrap lbhotel-import-export-page">
            <h1><?php esc_html_e( 'Import / Export Hotels', 'lbhotel' ); ?></h1>
            <p class="lbhotel-page-intro"><?php esc_html_e( 'Export your current hotel directory or import updates from a CSV file. Use the sample template to get started quickly.', 'lbhotel' ); ?></p>

            <div class="lbhotel-import-export-grid">
                <div class="lbhotel-card card">
                    <h2><?php esc_html_e( 'Export Hotels', 'lbhotel' ); ?></h2>
                    <p><?php esc_html_e( 'Download all hotel posts, locations, pricing, and metadata for safe keeping or bulk editing.', 'lbhotel' ); ?></p>
                    <form method="get" action="<?php echo esc_url( $export_action ); ?>" class="lbhotel-export-form">
                        <input type="hidden" name="action" value="<?php echo esc_attr( self::EXPORT_ACTION ); ?>" />
                        <input type="hidden" name="format" value="csv" />
                        <?php wp_nonce_field( 'lbhotel_export_nonce' ); ?>
                        <?php submit_button( __( 'Download CSV', 'lbhotel' ), 'primary', 'submit', false ); ?>
                    </form>
                    <p>
                        <a class="button button-secondary" href="<?php echo esc_url( $sample_url ); ?>">
                            <?php esc_html_e( 'Download Sample CSV', 'lbhotel' ); ?>
                        </a>
                        <a class="button button-link" href="<?php echo esc_url( $export_json_url ); ?>">
                            <?php esc_html_e( 'Download JSON', 'lbhotel' ); ?>
                        </a>
                    </p>
                    <p class="description"><?php esc_html_e( 'A footer row is included with the export date to help track backups.', 'lbhotel' ); ?></p>
                </div>

                <div class="lbhotel-card card">
                    <h2><?php esc_html_e( 'Import Hotels', 'lbhotel' ); ?></h2>
                    <p><?php esc_html_e( 'Upload a CSV file that follows the sample format to create new hotels or update existing ones by title or ID.', 'lbhotel' ); ?></p>
                    <form method="post" action="<?php echo esc_url( $import_action ); ?>" enctype="multipart/form-data" class="lbhotel-import-form">
                        <input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>" />
                        <?php wp_nonce_field( 'lbhotel_import_nonce' ); ?>
                        <p>
                            <input type="file" name="lbhotel_import_file" accept=".csv,.json" required />
                        </p>
                        <?php submit_button( __( 'Import Now', 'lbhotel' ), 'primary', 'submit', false ); ?>
                    </form>
                    <p class="description"><?php esc_html_e( 'Tip: The importer automatically detects commas or semicolons as delimiters.', 'lbhotel' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Images can be provided as media IDs or URLs separated by the “|” character.', 'lbhotel' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
