<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use LogicException;
use PhpAstInspector\PhpParser\GetNodeInfo;
use PhpAstInspector\PhpParser\NodeNavigator;
use PhpAstInspector\PhpParser\Parser;
use PhpParser\Node;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class InspectCommand extends Command
{
    public const COMMAND_NAME = 'inspect';

    private const CHOICE_TAG = 'choice';

    private CodeFormatter $codeFormatter;

    private Parser $parser;

    private RenderNodeInfo $renderNodeInfo;

    public function __construct()
    {
        parent::__construct();

        $this->codeFormatter = new CodeFormatter();
        $this->parser = new Parser();
        $this->renderNodeInfo = new RenderNodeInfo(new GetNodeInfo());
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addArgument('file', InputArgument::REQUIRED, 'The PHP script that should be parsed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        assert($output instanceof ConsoleOutputInterface);

        $output->getFormatter()
            ->setStyle(CodeFormatter::HIGHLIGHT_TAG, new OutputFormatterStyle('yellow', '', ['bold']));
        $output->getFormatter()
            ->setStyle(CodeFormatter::LINE_NUMBER_TAG, new OutputFormatterStyle('gray', '', []));
        $output->getFormatter()
            ->setStyle(self::CHOICE_TAG, new OutputFormatterStyle('', '', ['bold']));
        $output->getFormatter()
            ->setStyle(RenderNodeInfo::SUBNODE_TAG, new OutputFormatterStyle('yellow', '', []));
        $output->getFormatter()
            ->setStyle(RenderNodeInfo::CURRENT_NODE_TAG, new OutputFormatterStyle('green', '', []));

        $codeSection = $output->section();
        $infoSection = $output->section();
        $questionSection = $output->section();

        $fileArgument = $input->getArgument('file');
        assert(is_string($fileArgument));
        $code = file_get_contents($fileArgument);
        if (! is_string($code)) {
            throw new RuntimeException('Could not read file: ' . $fileArgument);
        }

        $nodes = $this->parser->parse($code);
        $navigator = NodeNavigator::selectFirstFrom($nodes);

        while (true) {
            $this->printCodeWithHighlightedNode($code, $navigator->currentNode(), $codeSection, $infoSection);

            $navigator = $this->askForNextMove($navigator, $input, $questionSection);
        }
    }

    private function printCodeWithHighlightedNode(
        string $code,
        Node $node,
        ConsoleSectionOutput $codeSection,
        ConsoleSectionOutput $infoSection
    ): void {
        $codeSection->overwrite(
            $this->codeFormatter->format($code, Highlight::createForPhpParserNode($node)) . "\n"
        );

        $infoSection->overwrite($this->renderNodeInfo->forNode($node));
    }

    private function askForNextMove(
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

        $nextAction = $this->questionHelper()
            ->ask(
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

    private function questionHelper(): QuestionHelper
    {
        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);

        return $questionHelper;
    }
}
