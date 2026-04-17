<?php

declare(strict_types=1);

namespace ResilientLogger\Utils;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;

enum Direction {
    case FORWARD;
    case BACKWARD;
}

final class HumanReadableDiffer {
    private Differ $differ;

    /**
     * Matches two or more newlines (with optional horizontal whitespace) to split paragraphs.
     */
    private const RE_SPLIT_PARAGRAPHS = '/\n\s*\n/';

    /**
     * Matches:
     * 1. HTML tags: <...>
     * 2. Non-whitespace, non-tag sequences: [^\s<>]+
     * 3. Whitespace sequences: \s+
     */
    private const RE_TOKENIZE = '/<[^>]+>|[^\s<>]+|\s+/u';

    public function __construct(
        private readonly int $contextLimitBackward = 3, 
        private readonly int $contextLimitForward = 3
    ) {
        $this->differ = new Differ(new class implements DiffOutputBuilderInterface {
            public function getDiff(array $diff): string {
                throw new \LogicException('HumanReadableDiffer should only use diffToArray().');
            }
        });
    }

    public function diff(string $old, string $new): string {
        if ($old === $new) {
            return '';
        }

        $oldParagraphs = preg_split(self::RE_SPLIT_PARAGRAPHS, $old, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $newParagraphs = preg_split(self::RE_SPLIT_PARAGRAPHS, $new, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        
        $paragraphDiff = $this->differ->diffToArray($oldParagraphs, $newParagraphs);
        $allChanges = [];

        for ($i = 0, $count = count($paragraphDiff); $i < $count; $i++) {
            [$content, $type] = $paragraphDiff[$i];
            
            if ($type === Differ::REMOVED && isset($paragraphDiff[$i + 1])) {
                [$nextContent, $nextType] = $paragraphDiff[$i + 1];
                if ($nextType === Differ::ADDED) {
                    foreach ($this->diffWords($content, $nextContent) as $wordChange) {
                        $allChanges[] = $wordChange;
                    }
                    $i++; 
                    continue;
                }
            }

            if ($type === Differ::ADDED) {
                $allChanges[] = sprintf('[+ %s]', trim($content));
            } elseif ($type === Differ::REMOVED) {
                $allChanges[] = sprintf('[- %s]', trim($content));
            }
        }

        $filteredChanges = array_values(array_filter($allChanges));

        return implode("\n", array_map(
            fn(int $idx, string $change) => sprintf('[%d] %s', $idx, $change),
            array_keys($filteredChanges),
            $filteredChanges
        ));
    }

    private function diffWords(string $old, string $new): array {
        $oldTokens = iterator_to_array($this->generateTokens($old));
        $newTokens = iterator_to_array($this->generateTokens($new));

        $wordDiff = $this->differ->diffToArray(
            array_map('trim', $oldTokens),
            array_map('trim', $newTokens)
        );

        $segmentChanges = [];
        $totalDiffItems = count($wordDiff);
        $oldPointer = 0;

        for ($diffIdx = 0; $diffIdx < $totalDiffItems; $diffIdx++) {
            [, $type] = $wordDiff[$diffIdx];

            if ($type === Differ::OLD) {
                $oldPointer++;
                continue;
            }

            $prefix = $this->getDiffContext($oldTokens, $oldPointer, Direction::BACKWARD);
            $removed = [];
            $added = [];

            while ($diffIdx < $totalDiffItems) {
                [$content, $t] = $wordDiff[$diffIdx];
                if ($t === Differ::OLD) break;
                
                $trimmed = trim($content);
                if ($t === Differ::REMOVED) {
                    if ($trimmed !== '') $removed[] = $trimmed;
                    $oldPointer++;
                } elseif ($t === Differ::ADDED) {
                    if ($trimmed !== '') $added[] = $trimmed;
                }
                $diffIdx++;
            }

            $suffix = $this->getDiffContext($oldTokens, $oldPointer, Direction::FORWARD);
            $diffBlock = array_filter([
                $removed ? '-' . implode(' ', $removed) : null,
                $added   ? '+' . implode(' ', $added)   : null,
            ]);

            if (!empty($diffBlock)) {
                $segmentChanges[] = trim(sprintf('%s [%s] %s', $prefix, implode(' ', $diffBlock), $suffix));
            }
            $diffIdx--; 
        }

        return $segmentChanges;
    }

    private function getDiffContext(array $tokens, int $index, Direction $direction): string {
        $context = [];
        $foundCount = 0;

        [$limit, $step, $pos] = match ($direction) {
            Direction::FORWARD => [
                $this->contextLimitForward, 
                1, 
                $index
            ],
            Direction::BACKWARD => [
                $this->contextLimitBackward, 
                -1, 
                $index - 1
            ],
        };

        while (isset($tokens[$pos]) && $foundCount < $limit) {
            $token = $tokens[$pos];

            if (trim($token) !== '') {
                $foundCount++;
            }

            $context[] = $token;
            $pos += $step;
        }

        if ($direction === Direction::BACKWARD) {
            $context = array_reverse($context);
        }

        return trim(implode('', $context));
    }

    private function generateTokens(string $text): \Generator {
        $offset = 0;
        $matches = [];

        while (preg_match(self::RE_TOKENIZE, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            yield $matches[0][0];
            $offset = $matches[0][1] + strlen($matches[0][0]);
        }
    }
}