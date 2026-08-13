<?php

namespace PowerOfFamilies\Programs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The `key: value` block a program carries in its description.
 *
 * WordPress Groups stores a group's description as a nullable TEXT column, and
 * this site uses it as a small attribute list -- one pair per line -- naming the
 * program's tile image and home page. Absence is resolved here, so readers ask
 * for a key and get a value or null rather than guarding for both a missing key
 * and a blank one.
 */
final readonly class ProgramDescription
{
    /**
     * @param array<string, string> $attributes lowercased key => non-empty value
     */
    private function __construct(private array $attributes)
    {
    }

    /**
     * Parse a description block.
     *
     * Not being a `key: value` pair is normal, not an error: descriptions are
     * hand-edited in the Groups admin, so free-form lines are expected and are
     * skipped. A repeated key takes its last value.
     */
    public static function parse(?string $description): self
    {
        $attributes = [];

        foreach (explode("\n", $description ?? '') as $line) {
            // Split on the first colon only -- values are URLs, which carry
            // their own.
            $parts = explode(':', $line, 2);

            if (count($parts) < 2) {
                continue;
            }

            $key = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if ('' === $key || '' === $value) {
                continue;
            }

            $attributes[$key] = $value;
        }

        return new self($attributes);
    }

    /**
     * The value declared for a key, or null when it was not declared.
     *
     * A key with a blank value counts as undeclared: `home:` with nothing after
     * it says no more than omitting the line.
     */
    public function get(string $key): ?string
    {
        return $this->attributes[strtolower($key)] ?? null;
    }

    /**
     * The program's tile image.
     */
    public function image(): ?string
    {
        return $this->get('image');
    }

    /**
     * The program's home page.
     */
    public function home(): ?string
    {
        return $this->get('home');
    }
}
