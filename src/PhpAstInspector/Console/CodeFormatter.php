<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

final class CodeFormatter
{
    public const HIGHLIGHT_TAG = 'highlight';

    public const LINE_NUMBER_TAG = 'line_number';

    private const START_HIGHLIGHT = '<highlight>';

    private const END_HIGHLIGHT = '</highlight>';

    public function format(string $code, ?Highlight $highlight = null): string
    {
        if ($highlight instanceof Highlight) {
            $beforeHighlighted = substr($code, 0, $highlight->startPosition);
            $highlighted = substr(
                $code,
                $highlight->startPosition,
                $highlight->endPosition - $highlight->startPosition
            );
            $afterHighlighted = substr($code, $highlight->endPosition);
            $code = $beforeHighlighted . self::START_HIGHLIGHT . $highlighted . self::END_HIGHLIGHT . $afterHighlighted;
        }

        $lines = explode("\n", $code);
        $maximumLineNumberWidth = strlen((string) count($lines));

        $inHighlightedSection = false;

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (str_contains($line, self::START_HIGHLIGHT)) {
                $inHighlightedSection = true;
            } elseif ($inHighlightedSection) {
                $line = self::START_HIGHLIGHT . $line;
            }
            if (str_contains($line, self::END_HIGHLIGHT)) {
                $inHighlightedSection = false;
            } elseif ($inHighlightedSection) {
                $line .= self::END_HIGHLIGHT;
            }

            $paddedLeft = str_pad((string) $lineNumber, $maximumLineNumberWidth, ' ', STR_PAD_LEFT);
            $paddedRight = str_pad($paddedLeft, 4);

            $lineWithNumber = '<' . self::LINE_NUMBER_TAG . '>' . $paddedRight . '</' . self::LINE_NUMBER_TAG . '>' . $line;

            $lines[$index] = rtrim($lineWithNumber);
        }

        return implode("\n", $lines);
    }
}
