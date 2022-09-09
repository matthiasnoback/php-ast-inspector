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
            $choices['d'] = '<choice>d</choice> = next node';
        }
        if ($navigator->hasPreviousNode()) {
            $choices['a'] = '<choice>a</choice> = previous node';
        }
        if ($navigator->hasParentNode()) {
            $choices['s'] = '<choice>s</choice> = parent node';
        }
        if ($navigator->hasSubnode()) {
            $choices['w'] = '<choice>w</choice> = inspect subnodes';
        }

        $choices[] = '<choice>Ctrl + C</choice> = quit';

        $outputSection->overwrite('<question>Next?</question> (' . implode(', ', $choices) . ')');

        $nextAction = null;
        while (! isset($choices[$nextAction])) {
            $nextAction = $this->readCharacter($this->inputStream);
        }

        $outputSection->clear();

        return match ($nextAction) {
            'd' => $navigator->navigateToNextNode(),
            'a' => $navigator->navigateToPreviousNode(),
            'w' => $navigator->navigateToFirstSubnode(),
            's' => $navigator->navigateToParentNode(),
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
