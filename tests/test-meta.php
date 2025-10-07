<?php
/**
 * PHPUnit tests for meta saving routines.
 */

class LBHotel_Meta_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        lbhotel_register_post_type();
        lbhotel_register_taxonomies();
        lbhotel_register_meta_fields();
    }

    public function test_meta_sanitization_on_save() {
        $post_id = $this->factory->post->create( array( 'post_type' => 'lbhotel_hotel' ) );
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );

        $_POST['lbhotel_meta_nonce']                   = wp_create_nonce( 'lbhotel_save_meta' );
        $_POST['lbhotel_virtual_tour_url']             = 'https://example.com/tour';
        $_POST['lbhotel_google_maps_url']              = ' https://maps.google.com/?q=31.7,-7.0 ';
        $_POST['lbhotel_contact_phone']                = '+212 6 11 22 33 44';
        $_POST['lbhotel_latitude']                     = '31.7917';
        $_POST['lbhotel_longitude']                    = '-7.0926';
        $_POST['lbhotel_opening_hours']                = "Mon-Fri: 9-18\nSat: 10-14";
        $_POST['tax_input']['lbhotel_place_category']  = array( 'restaurants' );

        lbhotel_save_meta( $post_id, get_post( $post_id ) );

        $this->assertSame( 'https://example.com/tour', get_post_meta( $post_id, 'lbhotel_virtual_tour_url', true ) );
        $this->assertSame( 'https://maps.google.com/?q=31.7,-7.0', get_post_meta( $post_id, 'lbhotel_google_maps_url', true ) );
        $this->assertSame( '+212 6 11 22 33 44', get_post_meta( $post_id, 'lbhotel_contact_phone', true ) );
        $this->assertSame( 31.7917, get_post_meta( $post_id, 'lbhotel_latitude', true ) );
        $this->assertSame( -7.0926, get_post_meta( $post_id, 'lbhotel_longitude', true ) );
        $this->assertSame( "Mon-Fri: 9-18\nSat: 10-14", get_post_meta( $post_id, 'lbhotel_opening_hours', true ) );

        unset( $_POST );
    }
}
