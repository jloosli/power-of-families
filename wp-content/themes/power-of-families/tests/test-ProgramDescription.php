<?php

/**
 * Tests for the ProgramDescription parser.
 *
 * Regression coverage for issue #39: WordPress Groups stores `description` as a
 * nullable TEXT column. The PHP 8.4 modernization briefly typed the parameter as
 * a non-null string, which fatalled /my-account/ for any user whose group
 * description was NULL. Lock that contract in.
 *
 * These cases used to reach a private method through ReflectionMethod; the
 * parser is its own module now, so they exercise the real public interface.
 *
 * @package Power_Of_Families
 */

use PowerOfFamilies\Programs\ProgramDescription;

class test_ProgramDescription extends WP_UnitTestCase {

    public function test_parses_null_description_without_fatal() {
        $meta = ProgramDescription::parse( null );

        $this->assertNull( $meta->image() );
        $this->assertNull( $meta->home() );
    }

    public function test_parses_empty_description() {
        $meta = ProgramDescription::parse( '' );

        $this->assertNull( $meta->image() );
        $this->assertNull( $meta->home() );
    }

    public function test_parses_image_and_home_metadata() {
        $meta = ProgramDescription::parse(
            "image: https://example.com/i.png\nhome: https://example.com/program"
        );

        $this->assertSame( 'https://example.com/i.png', $meta->image() );
        $this->assertSame( 'https://example.com/program', $meta->home() );
    }

    public function test_lines_without_colon_are_ignored() {
        $meta = ProgramDescription::parse(
            "image: https://example.com/i.png\njust a free-form line\nhome: https://example.com"
        );

        $this->assertSame( 'https://example.com/i.png', $meta->image() );
        $this->assertSame( 'https://example.com', $meta->home() );
        $this->assertNull( $meta->get( 'just a free-form line' ) );
    }

    public function test_keys_are_lowercased() {
        $meta = ProgramDescription::parse( 'IMAGE: https://example.com/i.png' );

        $this->assertSame( 'https://example.com/i.png', $meta->image() );
    }

    public function test_lookup_is_case_insensitive() {
        $meta = ProgramDescription::parse( 'image: https://example.com/i.png' );

        $this->assertSame( 'https://example.com/i.png', $meta->get( 'Image' ) );
    }

    /**
     * Splitting on the first colon only. The previous parser exploded on every
     * colon, trimmed each part and rejoined with ':', so whitespace after an
     * inner colon was eaten -- this pins the value as written instead.
     */
    public function test_only_the_first_colon_separates_key_from_value() {
        $meta = ProgramDescription::parse( 'home: https://example.com/a: b' );

        $this->assertSame( 'https://example.com/a: b', $meta->home() );
    }

    public function test_blank_value_counts_as_undeclared() {
        $meta = ProgramDescription::parse( "home:   \nimage: https://example.com/i.png" );

        $this->assertNull( $meta->home() );
        $this->assertSame( 'https://example.com/i.png', $meta->image() );
    }

    public function test_keyless_line_is_ignored() {
        $meta = ProgramDescription::parse( ": https://example.com/orphan\nhome: https://example.com" );

        $this->assertNull( $meta->get( '' ) );
        $this->assertSame( 'https://example.com', $meta->home() );
    }

    public function test_repeated_key_takes_the_last_value() {
        $meta = ProgramDescription::parse( "home: https://example.com/first\nhome: https://example.com/second" );

        $this->assertSame( 'https://example.com/second', $meta->home() );
    }

    /**
     * Descriptions are hand-edited in the Groups admin, so a browser may submit
     * CRLF line endings. The \r must not survive into the value.
     */
    public function test_crlf_line_endings_do_not_leak_into_values() {
        $meta = ProgramDescription::parse( "image: https://example.com/i.png\r\nhome: https://example.com" );

        $this->assertSame( 'https://example.com/i.png', $meta->image() );
        $this->assertSame( 'https://example.com', $meta->home() );
    }

    /**
     * The two live group descriptions, verbatim from production. Between them
     * they carry CRLF endings, a capitalised key, a third key nothing reads,
     * relative URLs, and -- in the Summer Camp Kit row -- a hand-editing
     * mistake that repeats `image` and folds a second `home:` into `store`.
     */
    public function test_parses_production_descriptions() {
        $bloom = ProgramDescription::parse(
            "home: /bloom\r\nImage: image.png\r\nstore: /store/bloom"
        );

        $this->assertSame( '/bloom', $bloom->home() );
        $this->assertSame( 'image.png', $bloom->image() );
        $this->assertSame( '/store/bloom', $bloom->get( 'store' ) );

        $summer = ProgramDescription::parse(
            "home: /summer-kit/\r\nimage: /wp-content/uploads/2014/11/summer-camp-kit-icon.jpg\r\n"
            . "store: home: /summer-kit/\r\nimage: /wp-content/uploads/2014/11/Do_It_Yourself_Summer_Camp_Kit-1.png"
        );

        $this->assertSame( '/summer-kit/', $summer->home() );
        $this->assertSame(
            '/wp-content/uploads/2014/11/Do_It_Yourself_Summer_Camp_Kit-1.png',
            $summer->image()
        );
    }

    public function test_unknown_keys_are_still_readable() {
        $meta = ProgramDescription::parse( 'blurb: A program for families' );

        $this->assertSame( 'A program for families', $meta->get( 'blurb' ) );
    }
}
