<?php

/**
 * Tests for the My_Programs class.
 *
 * @package Power_Of_Families
 */
class test_MyPrograms extends WP_UnitTestCase {

    private \PowerOfFamilies\POF\Programs\My_Programs $my_programs;
    private ReflectionMethod $parse_description;

    protected function setUp(): void {
        parent::setUp();
        $this->my_programs = new \PowerOfFamilies\POF\Programs\My_Programs();
        $this->parse_description = new ReflectionMethod(
            \PowerOfFamilies\POF\Programs\My_Programs::class,
            'getProgramMetaFromDescription'
        );
        $this->parse_description->setAccessible( true );
    }

    // -------------------------------------------------------------------------
    // getProgramMetaFromDescription — regression coverage for issue #39
    //
    // WordPress Groups stores `description` as a nullable TEXT column. The
    // PHP 8.4 modernization briefly typed this parameter as a non-null string,
    // which fatalled /my-account/ for any user whose group description was
    // NULL. Lock that contract in.
    // -------------------------------------------------------------------------

    public function test_parses_null_description_without_fatal() {
        $meta = $this->parse_description->invoke( $this->my_programs, null );

        $this->assertInstanceOf( \stdClass::class, $meta );
        $this->assertEmpty( get_object_vars( $meta ) );
    }

    public function test_parses_empty_description() {
        $meta = $this->parse_description->invoke( $this->my_programs, '' );

        $this->assertInstanceOf( \stdClass::class, $meta );
        $this->assertEmpty( get_object_vars( $meta ) );
    }

    public function test_parses_image_and_home_metadata() {
        $description = "image: https://example.com/i.png\nhome: https://example.com/program";

        $meta = $this->parse_description->invoke( $this->my_programs, $description );

        $this->assertSame( 'https://example.com/i.png', $meta->image );
        $this->assertSame( 'https://example.com/program', $meta->home );
    }

    public function test_lines_without_colon_are_ignored() {
        $description = "image: https://example.com/i.png\njust a free-form line\nhome: https://example.com";

        $meta = $this->parse_description->invoke( $this->my_programs, $description );

        $this->assertSame( 'https://example.com/i.png', $meta->image );
        $this->assertSame( 'https://example.com', $meta->home );
        $this->assertObjectNotHasProperty( 'just a free-form line', $meta );
    }

    public function test_keys_are_lowercased() {
        $description = "IMAGE: https://example.com/i.png";

        $meta = $this->parse_description->invoke( $this->my_programs, $description );

        $this->assertSame( 'https://example.com/i.png', $meta->image );
    }
}
