<?php
/**
 * PHPUnit tests for meta saving routines.
 */

class LBHotel_Meta_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        lbhotel_register_meta_fields();
    }

    public function test_meta_sanitization_on_save() {
        $post_id = $this->factory->post->create( array( 'post_type' => 'lbhotel_hotel' ) );
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );

        $_POST['lbhotel_meta_nonce']            = wp_create_nonce( 'lbhotel_save_meta' );
        $_POST['lbhotel_avg_price_per_night']   = '150,75';
        $_POST['lbhotel_star_rating']           = '5';
        $_POST['lbhotel_checkin_time']          = '15:00';
        $_POST['lbhotel_checkout_time']         = '11:00';
        $_POST['lbhotel_contact_phone']         = '+212 6 11 22 33 44';
        $_POST['lbhotel_has_free_breakfast']    = '1';
        $_POST['lbhotel_has_parking']           = '1';
        $_POST['lbhotel_rooms_total']           = '80';
        $_POST['lbhotel_rooms_json']            = wp_json_encode( array( array( 'name' => 'Suite', 'price' => '2000', 'capacity' => 3, 'availability' => 'Available', 'images' => array() ) ) );

        lbhotel_save_meta( $post_id, get_post( $post_id ) );

        $this->assertSame( 150.75, get_post_meta( $post_id, 'lbhotel_avg_price_per_night', true ) );
        $this->assertSame( 5, (int) get_post_meta( $post_id, 'lbhotel_star_rating', true ) );
        $this->assertSame( '+212 6 11 22 33 44', get_post_meta( $post_id, 'lbhotel_contact_phone', true ) );
        $this->assertSame( 80, (int) get_post_meta( $post_id, 'lbhotel_rooms_total', true ) );
        $rooms = get_post_meta( $post_id, 'lbhotel_rooms', true );
        $this->assertIsArray( $rooms );
        $this->assertSame( 'Suite', $rooms[0]['name'] );

        unset( $_POST );
    }
}
