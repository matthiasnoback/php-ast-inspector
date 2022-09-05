<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use LogicException;
use PhpAstInspector\PhpParser\NodeNavigator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Question\Question;

final class NavigateToNode
{
    public const CHOICE_TAG = 'choice';

    public function __construct(
        private readonly QuestionHelper $questionHelper
    ) {
    }

    public function basedOnUserInput(
        NodeNavigator $navigator,
        InputInterface $input,
        ConsoleSectionOutput $outputSection
    ): NodeNavigator {
        $choices = [];

        if ($navigator->hasNextNode()) {
            $choices[] = '<choice>d</choice> = next node';
        }
        if ($navigator->hasPreviousNode()) {
            $choices[] = '<choice>a</choice> = previous node';
        }
        if ($navigator->hasParentNode()) {
            $choices[] = '<choice>s</choice> = parent node';
        }
        if ($navigator->hasSubnode()) {
            $choices[] = '<choice>w</choice> = inspect subnodes';
        }

        $choices[] = '<choice>Ctrl + C</choice> = quit';

        $nextAction = $this->questionHelper->ask(
            $input,
            $outputSection,
            new Question('<question>Next?</question> (' . implode(', ', $choices) . ')')
        );

        $outputSection->clear();

        if ($nextAction === 'd') {
            return $navigator->navigateToNextNode();
        } elseif ($nextAction === 'a') {
            return $navigator->navigateToPreviousNode();
        } elseif ($nextAction === 'w') {
            return $navigator->navigateToFirstSubnode();
        } elseif ($nextAction === 's') {
            return $navigator->navigateToParentNode();
        }

        throw new LogicException('Action not supported: ' . $nextAction);
    }
}
