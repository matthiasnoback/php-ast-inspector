<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use PHPUnit\Framework\TestCase;

final class CodeFormatterTest extends TestCase
{
    private CodeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new CodeFormatter();
    }

    public function testItAddsLineNumbers(): void
    {
        $code = <<<'CODE_SAMPLE'
<?php

echo 'Line 3';
CODE_SAMPLE;

        $formatted = $this->formatter->format($code);

        self::assertSame(
            <<<'CODE_SAMPLE'
<line_number>1   </line_number><?php
<line_number>2   </line_number>
<line_number>3   </line_number>echo 'Line 3';
CODE_SAMPLE
,
            $formatted
        );
    }

    public function testItAlignsLineNumbersToTheRight(): void
    {
        $code = <<<'CODE_SAMPLE'
<?php

echo 'Line 3';
echo 'Line 4';
echo 'Line 5';
echo 'Line 6';
echo 'Line 7';
echo 'Line 8';
echo 'Line 9';
echo 'Line 10';
CODE_SAMPLE;

        $formatted = $this->formatter->format($code);

        self::assertSame(
            <<<'CODE_SAMPLE'
<line_number> 1  </line_number><?php
<line_number> 2  </line_number>
<line_number> 3  </line_number>echo 'Line 3';
<line_number> 4  </line_number>echo 'Line 4';
<line_number> 5  </line_number>echo 'Line 5';
<line_number> 6  </line_number>echo 'Line 6';
<line_number> 7  </line_number>echo 'Line 7';
<line_number> 8  </line_number>echo 'Line 8';
<line_number> 9  </line_number>echo 'Line 9';
<line_number>10  </line_number>echo 'Line 10';
CODE_SAMPLE
,
            $formatted
        );
    }

    public function testItHighlightsPartOfAGivenLine(): void
    {
        $code = <<<'CODE_SAMPLE'
<?php

echo 'Line 3';
CODE_SAMPLE;

        $formatted = $this->formatter->format($code, new Highlight(12, 20));

        self::assertSame(
            <<<'CODE_SAMPLE'
<line_number>1   </line_number><?php
<line_number>2   </line_number>
<line_number>3   </line_number>echo <highlight>'Line 3'</highlight>;
CODE_SAMPLE
,
            $formatted
        );
    }

    public function testItHighlightsAcrossMultipleLines(): void
    {
        $code = <<<'CODE_SAMPLE'
<?php

echo 'Line 3';
echo 'Line 4';
CODE_SAMPLE;

        $formatted = $this->formatter->format($code, new Highlight(12, 35));

        self::assertSame(
            <<<'CODE_SAMPLE'
<line_number>1   </line_number><?php
<line_number>2   </line_number>
<line_number>3   </line_number>echo <highlight>'Line 3';</highlight>
<line_number>4   </line_number><highlight>echo 'Line 4'</highlight>;
CODE_SAMPLE
,
            $formatted
        );
    }
}
