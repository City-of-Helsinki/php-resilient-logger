<?php

declare(strict_types=1);

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ResilientLogger\Utils\HumanReadableDiffer;

#[CoversClass(HumanReadableDiffer::class)]
class HumanReadableDifferTest extends TestCase {
    private HumanReadableDiffer $differ;

    protected function setUp(): void {
        // Default context is 3 before, 3 after.
        $this->differ = new HumanReadableDiffer();
    }

    #[Test]
    public function it_returns_empty_string_when_content_is_identical(): void {
        $content = "Example content\n\nAcross multiple lines.";
        $this->assertSame('', $this->differ->diff($content, $content));
    }

    #[Test]
    #[DataProvider('diffDataProvider')]
    public function it_generates_correct_human_readable_diff(string $old, string $new, string $expected): void {
        $actual = $this->differ->diff($old, $new);
        $this->assertSame($expected, $actual);
    }

    public static function diffDataProvider(): array {
        return [
            'simple_word_replacement' => [
                'old' => 'The quick brown fox.',
                'new' => 'The quick red fox.',
                // Context 3 includes "The quick" (2 words) and "fox." (1 word)
                'expected' => '[0] The quick [-brown +red] fox.',
            ],
            'multiple_word_changes' => [
                'old' => 'Hello world, how are you?',
                'new' => 'Goodbye world, how is life?',
                // The library finds middle-ground, resulting in fragmented segments.
                // This is expected behavior for a precise word-level differ.
                'expected' => "[0] [-Hello +Goodbye] world, how are\n" .
                              "[1] Hello world, how [-are +is] you?\n" .
                              "[2] world, how are [-you? +life?]",
            ],
            'paragraph_addition' => [
                'old' => 'First paragraph.',
                'new' => "First paragraph.\n\nSecond paragraph.",
                'expected' => '[0] [+ Second paragraph.]',
            ],
            'html_token_preservation' => [
                'old' => 'View <a href="/old">Link</a> now.',
                'new' => 'View <a href="/new">Link</a> now.',
                // "View" is before; "Link", "</a>", and "now." are the 3 tokens after.
                'expected' => '[0] View [-<a href="/old"> +<a href="/new">] Link</a> now.',
            ],
        ];
    }

    #[Test]
    public function it_limits_context_to_configured_amount(): void {
        // We set context to 2 to verify we can restrict the output.
        $this->differ = new HumanReadableDiffer(2, 2);
        
        $old = "word1 word2 word3 target word4 word5 word6";
        $new = "word1 word2 word3 changed word4 word5 word6";

        $result = $this->differ->diff($old, $new);

        // Context 2: "word2 word3 [-target +changed] word4 word5"
        $this->assertStringContainsString('word2 word3 [-target +changed] word4 word5', $result);
        $this->assertStringNotContainsString('word1', $result);
        $this->assertStringNotContainsString('word6', $result);
    }

    #[Test]
    public function it_handles_multiple_consecutive_spaces_gracefully(): void {
        $old = "Word    Word";
        $new = "Word Changed Word";

        $result = $this->differ->diff($old, $new);

        // We now ignore whitespace diffs, so it just sees the word "Changed" added.
        $this->assertStringContainsString('Word [+Changed] Word', $result);
    }

    #[Test]
    public function it_respects_asymmetrical_context_limits(): void {
        $differ = new HumanReadableDiffer(1, 4);

        $old = "A B C target D E F G H";
        $new = "A B C changed D E F G H";

        $result = $differ->diff($old, $new);

        // Expected: 1 word before (C), 4 words after (D, E, F, G).
        $this->assertStringContainsString('C [-target +changed] D E F G', $result);
        $this->assertStringNotContainsString('B', $result);
        $this->assertStringNotContainsString('H', $result);
    }
}