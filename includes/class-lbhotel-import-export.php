<?php
/**
 * Import and export tools for Virtual Maroc places.
 *
 * @package VirtualMaroc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles CSV and XML import/export for virtual places.
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
            __( 'Import/Export Places', 'lbhotel' ),
            __( 'Import/Export', 'lbhotel' ),
            'manage_options',
            'places-import-export',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Determine if the current screen is the Import/Export admin page.
     *
     * @return bool
     */
    protected function is_import_export_screen() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && 'lbhotel_hotel_page_places-import-export' === $screen->id ) {
                return true;
            }
        }

        $page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

        return ( 'places-import-export' === $page && 'lbhotel_hotel' === $post_type );
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
     * Render the Import/Export admin screen.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'lbhotel' ) );
        }

        $export_url = add_query_arg(
            array(
                'action' => self::EXPORT_ACTION,
                '_wpnonce' => wp_create_nonce( 'lbhotel_export_nonce' ),
            ),
            admin_url( 'admin-post.php' )
        );

        $sample_url = add_query_arg(
            array(
                'action' => self::SAMPLE_ACTION,
                '_wpnonce' => wp_create_nonce( 'lbhotel_sample_nonce' ),
            ),
            admin_url( 'admin-post.php' )
        );

        ?>
        <div class="wrap lbhotel-import-export-page">
            <h1><?php esc_html_e( 'Import and Export Virtual Places', 'lbhotel' ); ?></h1>
            <p class="lbhotel-page-intro">
                <?php esc_html_e( 'Export all virtual places to CSV or XML for backups, or import data to quickly populate the directory. Categories and all configured custom fields are included automatically.', 'lbhotel' ); ?>
            </p>

            <div class="lbhotel-import-export-grid">
                <div class="lbhotel-card card">
                    <h2><?php esc_html_e( 'Export places', 'lbhotel' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Choose the format you would like to download. The file will include all global fields, category specific fields, and assigned categories.', 'lbhotel' ); ?></p>
                    <form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lbhotel-export-form">
                        <input type="hidden" name="action" value="<?php echo esc_attr( self::EXPORT_ACTION ); ?>" />
                        <?php wp_nonce_field( 'lbhotel_export_nonce' ); ?>
                        <label for="lbhotel-export-format" class="screen-reader-text"><?php esc_html_e( 'Export format', 'lbhotel' ); ?></label>
                        <select name="format" id="lbhotel-export-format">
                            <option value="csv"><?php esc_html_e( 'CSV', 'lbhotel' ); ?></option>
                            <option value="xml"><?php esc_html_e( 'XML', 'lbhotel' ); ?></option>
                        </select>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Download', 'lbhotel' ); ?></button>
                    </form>
                    <p class="description">
                        <a class="button" href="<?php echo esc_url( $sample_url ); ?>"><?php esc_html_e( 'Download sample CSV', 'lbhotel' ); ?></a>
                    </p>
                </div>

                <div class="lbhotel-card card">
                    <h2><?php esc_html_e( 'Import places', 'lbhotel' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Upload a CSV or XML file generated from this tool. Existing places will be updated when IDs match; otherwise new entries are created.', 'lbhotel' ); ?></p>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lbhotel-import-form">
                        <input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>" />
                        <?php wp_nonce_field( 'lbhotel_import_nonce' ); ?>
                        <input type="file" name="lbhotel_import_file" accept=".csv,.xml,text/csv,application/xml" required />
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'lbhotel' ); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
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
        $format = in_array( $format, array( 'csv', 'xml' ), true ) ? $format : 'csv';

        $data   = $this->get_places_data();
        $date   = gmdate( 'Ymd_His' );

        if ( 'xml' === $format ) {
            $filename = 'virtual-places_' . $date . '.xml';
            nocache_headers();
            header( 'Content-Type: application/xml; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );

            $xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><places></places>' );
            $xml->addAttribute( 'exported_at', current_time( 'mysql' ) );

            foreach ( $data as $place ) {
                $node = $xml->addChild( 'place' );
                foreach ( $place as $key => $value ) {
                    if ( 'meta' === $key ) {
                        $meta_node = $node->addChild( 'meta' );
                        foreach ( $value as $meta_key => $meta_value ) {
                            if ( is_array( $meta_value ) ) {
                                $meta_node->addChild( $meta_key, wp_json_encode( $meta_value ) );
                            } else {
                                $meta_node->addChild( $meta_key, htmlspecialchars( (string) $meta_value ) );
                            }
                        }
                    } else {
                        $node->addChild( $key, htmlspecialchars( (string) $value ) );
                    }
                }
            }

            echo $xml->asXML();
            exit;
        }

        $filename = 'virtual-places_' . $date . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output  = fopen( 'php://output', 'w' );
        $columns = array_keys( $this->get_export_columns() );
        fputcsv( $output, $columns );

        foreach ( $data as $place ) {
            $row = array();
            foreach ( $columns as $column_key ) {
                if ( 'meta' === $column_key ) {
                    continue;
                }

                if ( array_key_exists( $column_key, $place ) ) {
                    $row[] = $place[ $column_key ];
                } elseif ( isset( $place['meta'][ $column_key ] ) ) {
                    $value = $place['meta'][ $column_key ];
                    if ( is_array( $value ) ) {
                        $row[] = wp_json_encode( $value );
                    } else {
                        $row[] = $value;
                    }
                } else {
                    $row[] = '';
                }
            }

            fputcsv( $output, $row );
        }

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

        $filename = 'virtual-places-sample_' . gmdate( 'Ymd_His' ) . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output  = fopen( 'php://output', 'w' );
        $columns = array_keys( $this->get_export_columns() );
        fputcsv( $output, $columns );

        $sample = $this->get_sample_record();
        $row    = array();
        foreach ( $columns as $column ) {
            if ( isset( $sample['meta'][ $column ] ) ) {
                $value = $sample['meta'][ $column ];
                $row[] = is_array( $value ) ? wp_json_encode( $value ) : $value;
            } elseif ( isset( $sample[ $column ] ) ) {
                $row[] = $sample[ $column ];
            } else {
                $row[] = '';
            }
        }

        fputcsv( $output, $row );
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
                'csv' => 'text/csv',
                'xml' => 'application/xml',
            ),
        );

        $uploaded = wp_handle_upload( $_FILES['lbhotel_import_file'], $overrides );

        if ( isset( $uploaded['error'] ) ) {
            $this->add_admin_notice( esc_html( $uploaded['error'] ), 'error' );
            $this->redirect_back();
        }

        $file_path = $uploaded['file'];
        $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

        $results = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => array(),
        );

        if ( 'xml' === $extension ) {
            $data = simplexml_load_file( $file_path );
            if ( false === $data ) {
                $results['errors'][] = esc_html__( 'Unable to parse the XML file.', 'lbhotel' );
            } else {
                $records = array();
                foreach ( $data->place as $place ) {
                    $record = array();
                    foreach ( $place->children() as $key => $value ) {
                        if ( 'meta' === $key ) {
                            $record['meta'] = array();
                            foreach ( $value->children() as $meta_key => $meta_value ) {
                                $record['meta'][ $meta_key ] = (string) $meta_value;
                            }
                        } else {
                            $record[ $key ] = (string) $value;
                        }
                    }
                    $records[] = $record;
                }
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
                        $headers = array_map( array( $this, 'normalize_header_key' ), $row );
                        continue;
                    }

                    $record = array();
                    foreach ( $headers as $index => $header ) {
                        $record[ $header ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                    }
                    $results = $this->process_import_records( array( $record ), $results );
                }
                fclose( $handle );
            }
        }

        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        $message = sprintf(
            /* translators: 1: created count, 2: updated count, 3: skipped count */
            esc_html__( 'Import complete. %1$d created, %2$d updated, %3$d skipped.', 'lbhotel' ),
            (int) $results['created'],
            (int) $results['updated'],
            (int) $results['skipped']
        );

        $this->add_admin_notice( $message );

        if ( ! empty( $results['errors'] ) ) {
            foreach ( $results['errors'] as $error ) {
                $this->add_admin_notice( $error, 'error' );
            }
        }

        $this->redirect_back();
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
                $results['skipped']++;
                continue;
            }

            $outcome = $this->import_single_place( $record );

            if ( is_wp_error( $outcome ) ) {
                $results['errors'][] = $outcome->get_error_message();
            } elseif ( 'created' === $outcome ) {
                $results['created']++;
            } elseif ( 'updated' === $outcome ) {
                $results['updated']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Import a single place record.
     *
     * @param array $record Normalized record data.
     * @return string|WP_Error created|updated|skipped or error.
     */
    protected function import_single_place( $record ) {
        $meta_mapping = $this->get_meta_mapping();

        $title = isset( $record['post_title'] ) ? trim( wp_unslash( $record['post_title'] ) ) : '';

        if ( '' === $title ) {
            return 'skipped';
        }

        $post_id = isset( $record['post_id'] ) ? absint( $record['post_id'] ) : 0;

        if ( $post_id && 'lbhotel_hotel' !== get_post_type( $post_id ) ) {
            $post_id = 0;
        }

        if ( ! $post_id ) {
            $existing = get_page_by_title( $title, OBJECT, 'lbhotel_hotel' );
            if ( $existing ) {
                $post_id = $existing->ID;
            }
        }

        $status = isset( $record['post_status'] ) ? sanitize_key( $record['post_status'] ) : 'publish';
        $status = in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $status : 'publish';

        $postarr = array(
            'post_title'   => sanitize_text_field( $title ),
            'post_content' => isset( $record['post_content'] ) ? wp_kses_post( $record['post_content'] ) : '',
            'post_excerpt' => isset( $record['post_excerpt'] ) ? sanitize_textarea_field( $record['post_excerpt'] ) : '',
            'post_status'  => $status,
            'post_type'    => 'lbhotel_hotel',
        );

        if ( $post_id ) {
            $postarr['ID'] = $post_id;
        }

        $result = wp_insert_post( $postarr, true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $post_id = (int) $result;

        $categories_field = isset( $record['categories'] ) ? $record['categories'] : '';
        $category_slugs   = array();

        if ( is_array( $categories_field ) ) {
            $category_slugs = $categories_field;
        } elseif ( is_string( $categories_field ) && '' !== trim( $categories_field ) ) {
            $parts = array_map( 'trim', explode( ',', $categories_field ) );
            foreach ( $parts as $part ) {
                if ( '' !== $part ) {
                    $category_slugs[] = sanitize_title( $part );
                }
            }
        }

        if ( ! empty( $category_slugs ) ) {
            wp_set_object_terms( $post_id, $category_slugs, 'lbhotel_place_category' );
        }

        $meta_values = array();

        if ( isset( $record['meta'] ) && is_array( $record['meta'] ) ) {
            $meta_values = $record['meta'];
        } else {
            foreach ( $record as $key => $value ) {
                if ( isset( $meta_mapping[ $key ] ) ) {
                    $meta_values[ $key ] = $value;
                }
            }
        }

        foreach ( $meta_values as $rest_key => $value ) {
            if ( ! isset( $meta_mapping[ $rest_key ] ) ) {
                continue;
            }

            $meta_key   = $meta_mapping[ $rest_key ];
            $definition = lbhotel_get_all_field_definitions()[ $meta_key ];
            $input      = isset( $definition['input'] ) ? $definition['input'] : 'text';

            if ( 'gallery' === $input ) {
                if ( is_string( $value ) ) {
                    if ( $this->looks_like_json( $value ) ) {
                        $decoded = json_decode( $value, true );
                        $value   = is_array( $decoded ) ? $decoded : explode( '|', $value );
                    } else {
                        $value = preg_split( '/[|,]/', $value );
                    }
                }

                if ( ! is_array( $value ) ) {
                    $value = array( $value );
                }

                $value = array_map( 'absint', array_filter( (array) $value ) );
                $value = array_values( array_unique( $value ) );

                if ( ! empty( $value ) ) {
                    update_post_meta( $post_id, $meta_key, $value );
                } else {
                    delete_post_meta( $post_id, $meta_key );
                }

                continue;
            }

            $sanitized = $this->sanitize_meta_value( $definition, $value );

            if ( '' === $sanitized || ( is_array( $sanitized ) && empty( $sanitized ) ) ) {
                delete_post_meta( $post_id, $meta_key );
            } else {
                update_post_meta( $post_id, $meta_key, $sanitized );
            }
        }

        return isset( $postarr['ID'] ) ? 'updated' : 'created';
    }

    /**
     * Sanitize a meta value using the configured callback.
     *
     * @param array $definition Field definition.
     * @param mixed $value      Value to sanitize.
     * @return mixed
     */
    protected function sanitize_meta_value( $definition, $value ) {
        $sanitize = isset( $definition['sanitize_callback'] ) ? $definition['sanitize_callback'] : 'sanitize_text_field';

        if ( is_callable( $sanitize ) ) {
            return call_user_func( $sanitize, $value );
        }

        return $value;
    }

    /**
     * Retrieve export columns.
     *
     * @return array<string,string> key => label.
     */
    protected function get_export_columns() {
        $columns = array(
            'post_id'      => __( 'Post ID', 'lbhotel' ),
            'post_title'   => __( 'Title', 'lbhotel' ),
            'post_content' => __( 'Content', 'lbhotel' ),
            'post_excerpt' => __( 'Excerpt', 'lbhotel' ),
            'post_status'  => __( 'Status', 'lbhotel' ),
            'categories'   => __( 'Categories', 'lbhotel' ),
        );

        foreach ( lbhotel_get_all_field_definitions() as $meta_key => $definition ) {
            $rest_key = lbhotel_rest_format_meta_key( $meta_key );
            $label    = isset( $definition['label'] ) ? $definition['label'] : $rest_key;
            $columns[ $rest_key ] = $label;
        }

        return $columns;
    }

    /**
     * Build the meta mapping array.
     *
     * @return array<string,string>
     */
    protected function get_meta_mapping() {
        $mapping = array();

        foreach ( lbhotel_get_all_field_definitions() as $meta_key => $definition ) {
            unset( $definition );
            $mapping[ lbhotel_rest_format_meta_key( $meta_key ) ] = $meta_key;
        }

        return $mapping;
    }

    /**
     * Retrieve all places data for export.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function get_places_data() {
        $columns = $this->get_export_columns();
        $meta_mapping = $this->get_meta_mapping();

        $query = new WP_Query(
            array(
                'post_type'      => 'lbhotel_hotel',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
            )
        );

        $data = array();

        foreach ( $query->posts as $post ) {
            $record = array(
                'post_id'      => $post->ID,
                'post_title'   => get_the_title( $post ),
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_status'  => $post->post_status,
                'categories'   => implode( ',', wp_list_pluck( wp_get_post_terms( $post->ID, 'lbhotel_place_category' ), 'slug' ) ),
                'meta'         => array(),
            );

            foreach ( $meta_mapping as $rest_key => $meta_key ) {
                $definition = lbhotel_get_all_field_definitions()[ $meta_key ];
                $input      = isset( $definition['input'] ) ? $definition['input'] : 'text';
                $value      = get_post_meta( $post->ID, $meta_key, true );

                if ( 'gallery' === $input ) {
                    $value = lbhotel_sanitize_gallery_images( $value );
                    $record['meta'][ $rest_key ] = $value;
                } else {
                    $record['meta'][ $rest_key ] = $value;
                }
            }

            $data[] = $record;
        }

        return $data;
    }

    /**
     * Provide a sample record for the CSV template.
     *
     * @return array
     */
    protected function get_sample_record() {
        $record = array(
            'post_id'      => '',
            'post_title'   => __( 'Sample Place', 'lbhotel' ),
            'post_content' => __( 'Add your full description here.', 'lbhotel' ),
            'post_excerpt' => __( 'Short summary of the place.', 'lbhotel' ),
            'post_status'  => 'publish',
            'categories'   => 'hotels',
            'meta'         => array(),
        );

        foreach ( lbhotel_get_all_field_definitions() as $meta_key => $definition ) {
            $rest_key = lbhotel_rest_format_meta_key( $meta_key );
            $sample   = '';

            switch ( $meta_key ) {
                case 'lbhotel_virtual_tour_url':
                    $sample = 'https://example.com/virtual-tour';
                    break;
                case 'lbhotel_google_maps_url':
                    $sample = 'https://maps.google.com/?q=Morocco';
                    break;
                case 'lbhotel_contact_phone':
                    $sample = '+212 600-000000';
                    break;
                case 'lbhotel_room_types':
                case 'lbhotel_specialties':
                case 'lbhotel_product_categories':
                case 'lbhotel_training_schedule':
                    $sample = "Example item one\nExample item two";
                    break;
                case 'lbhotel_latitude':
                    $sample = '31.7917';
                    break;
                case 'lbhotel_longitude':
                    $sample = '-7.0926';
                    break;
                case 'lbhotel_gallery_images':
                    $sample = array();
                    break;
                default:
                    $sample = '';
                    break;
            }

            $record['meta'][ $rest_key ] = $sample;
        }

        return $record;
    }

    /**
     * Normalize header keys for CSV parsing.
     *
     * @param string $header Raw header.
     * @return string
     */
    protected function normalize_header_key( $header ) {
        $normalized = strtolower( trim( (string) $header ) );
        $normalized = str_replace( array( ' ', '-' ), '_', $normalized );
        $normalized = preg_replace( '/[^a-z0-9_]/', '', $normalized );

        if ( 'name' === $normalized || 'title' === $normalized ) {
            return 'post_title';
        }

        return $normalized;
    }

    /**
     * Detect if a string looks like JSON.
     *
     * @param string $value Value to inspect.
     * @return bool
     */
    protected function looks_like_json( $value ) {
        $value = trim( (string) $value );

        return ( '' !== $value && in_array( $value[0], array( '{', '[' ), true ) );
    }

    /**
     * Add an admin notice for later rendering.
     *
     * @param string $message Message.
     * @param string $type    success|error.
     */
    protected function add_admin_notice( $message, $type = 'success' ) {
        $key     = $this->notice_transient_prefix . get_current_user_id();
        $notices = get_transient( $key );

        if ( ! is_array( $notices ) ) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient( $key, $notices, MINUTE_IN_SECONDS * 30 );
    }

    /**
     * Retrieve stored admin notices.
     *
     * @return array
     */
    protected function get_stored_notices() {
        $key = $this->notice_transient_prefix . get_current_user_id();
        $notices = get_transient( $key );

        return is_array( $notices ) ? $notices : array();
    }

    /**
     * Clear stored notices.
     */
    protected function clear_stored_notices() {
        delete_transient( $this->notice_transient_prefix . get_current_user_id() );
    }

    /**
     * Redirect back to the import/export screen.
     */
    protected function redirect_back() {
        $url = add_query_arg(
            array(
                'post_type' => 'lbhotel_hotel',
                'page'      => 'places-import-export',
            ),
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $url );
        exit;
    }
}
