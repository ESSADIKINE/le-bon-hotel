<?php
/**
 * PHPUnit tests for custom post type registration.
 */

class LBHotel_Post_Type_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        lbhotel_register_post_type();
    }

    public function test_post_type_exists() {
        $this->assertTrue( post_type_exists( 'lbhotel_hotel' ) );
    }

    public function test_post_type_supports_thumbnail() {
        $this->assertTrue( post_type_supports( 'lbhotel_hotel', 'thumbnail' ) );
    }
}
