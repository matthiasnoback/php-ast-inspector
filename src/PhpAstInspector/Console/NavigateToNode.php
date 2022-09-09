<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use LogicException;
use PhpAstInspector\PhpParser\NodeNavigator;
use RuntimeException;
use const STDIN;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Terminal;

final class NavigateToNode
{
    public const CHOICE_TAG = 'choice';

    private const NEXT_NODE_KEY = 'd';

    private const PREVIOUS_NODE_KEY = 'a';

    private const PARENT_NODE_KEY = 's';

    private const SUBNODES_KEY = 'w';

    /**
     * @var resource
     * @readonly
     */
    private $inputStream;

    public function __construct(InputInterface $input)
    {
        $inputStream = null;

        if ($input instanceof StreamableInputInterface) {
            $inputStream = $input->getStream();
        }

        if ($inputStream === null) {
            $inputStream = STDIN;
        }

        $this->inputStream = $inputStream;
    }

    public function basedOnUserInput(
        NodeNavigator $navigator,
        ConsoleSectionOutput $outputSection
    ): NodeNavigator {
        $choices = [];

        if ($navigator->hasNextNode()) {
            $choices[self::NEXT_NODE_KEY] = '<choice>' . self::NEXT_NODE_KEY . '</choice> = next node';
        }
        if ($navigator->hasPreviousNode()) {
            $choices[self::PREVIOUS_NODE_KEY] = '<choice>' . self::PREVIOUS_NODE_KEY . '</choice> = previous node';
        }
        if ($navigator->hasParentNode()) {
            $choices[self::PARENT_NODE_KEY] = '<choice>' . self::PARENT_NODE_KEY . '</choice> = parent node';
        }
        if ($navigator->hasSubnode()) {
            $choices[self::SUBNODES_KEY] = '<choice>' . self::SUBNODES_KEY . '</choice> = inspect subnodes';
        }

        $choices[] = '<choice>Ctrl + C</choice> = quit';

        $outputSection->overwrite('<question>Next?</question> (' . implode(', ', $choices) . ')');

        $nextAction = null;
        while (! isset($choices[$nextAction])) {
            $nextAction = $this->readCharacter($this->inputStream);
        }

        $outputSection->clear();

        return match ($nextAction) {
            self::NEXT_NODE_KEY => $navigator->navigateToNextNode(),
            self::PREVIOUS_NODE_KEY => $navigator->navigateToPreviousNode(),
            self::SUBNODES_KEY => $navigator->navigateToFirstSubnode(),
            self::PARENT_NODE_KEY => $navigator->navigateToParentNode(),
            default => throw new LogicException('Action not supported: ' . $nextAction),
        };
    }

    /**
     * @param resource $inputStream
     */
    private function readCharacter($inputStream): string
    {
        if (Terminal::hasSttyAvailable()) {
            /*
             * We have to change the `stty` configuration here, so we can read a single character and not wait for
             * Enter. Normally we'd have to reset it to its original configuration, but Symfony already does this on
             * SIGINT and SIGTERM (see Symfony\Component\Console\Application)
             */
            exec('stty -icanon 2>&1');
        }

        $c = fread($inputStream, 1);

        if ($c === false) {
            throw new RuntimeException('Could not read from input stream');
        }

        return $c;
    }
}
