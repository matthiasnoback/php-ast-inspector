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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

final class InspectCommand extends Command
{
    public const COMMAND_NAME = 'inspect';

    private CodeFormatter $codeFormatter;

    private Parser $parser;

    private GetNodeInfo $getNodeInfo;
    private ConsoleSectionOutput $codeSection;
    private ConsoleSectionOutput $infoSection;

    public function __construct()
    {
        parent::__construct();

        $this->codeFormatter = new CodeFormatter();
        $this->parser = new Parser();
        $this->getNodeInfo = new GetNodeInfo();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addArgument('file', InputArgument::REQUIRED, 'The PHP script that should be parsed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $output->getFormatter()
            ->setStyle(CodeFormatter::HIGHLIGHT_TAG, new OutputFormatterStyle('yellow', '', ['bold']));
        $output->getFormatter()
            ->setStyle(CodeFormatter::LINE_NUMBER_TAG, new OutputFormatterStyle('gray', '', []));
        $output->getFormatter()
            ->setStyle('choice', new OutputFormatterStyle('', '', ['bold']));
        $output->getFormatter()
            ->setStyle('subnode', new OutputFormatterStyle('yellow', '', []));
        $output->getFormatter()
            ->setStyle('current_node', new OutputFormatterStyle('green', '', []));

        $output->setErrorOutput(new NullOutput());
        $this->codeSection = $output->section();
        $this->infoSection = $output->section();
        $questionSection = $output->section();

        $fileArgument = $input->getArgument('file');
        assert(is_string($fileArgument));
        $code = file_get_contents($fileArgument);
        if (! is_string($code)) {
            throw new RuntimeException('Could not read file: ' . $fileArgument);
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $nodes = $this->parser->parse($code);
        $navigator = NodeNavigator::selectFirstFrom($nodes);

        while (true) {
            $this->printCodeWithHighlightedNode($code, $navigator->currentNode());

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

            $choices['q'] = '<choice>q</choice> = quit';

            $nextAction = $questionHelper->ask(
                $input,
                $questionSection,
                new Question('<question>Next?</question> (' . implode(', ', $choices) . ')')
            );
            $questionSection->clear();
            if ($nextAction === 'q') {
                return 0;
            } elseif ($nextAction === 'd') {
                $navigator = $navigator->navigateToNextNode();
            } elseif ($nextAction === 'a') {
                $navigator = $navigator->navigateToPreviousNode();
            } elseif ($nextAction === 'w') {
                $navigator = $navigator->navigateToFirstSubnode();
            } elseif ($nextAction === 's') {
                $navigator = $navigator->navigateToParentNode();
            } else {
                throw new LogicException('Action not supported: ' . $nextAction);
            }
        }
    }

    private function printCodeWithHighlightedNode(string $code, Node $node): void
    {
        $this->codeSection->overwrite(
            $this->codeFormatter->format($code, Highlight::createForPhpParserNode($node)) . "\n"
        );

        $tempOutput = new BufferedOutput();
        $breadcrumbs = (new NodeNavigator($node))->breadcrumbs();
        $breadcrumbs[count($breadcrumbs) - 1] = '<current_node>' . $breadcrumbs[count($breadcrumbs) - 1] . '</current_node>';
        $tempOutput->writeln('Current node: ' . implode(' > ', $breadcrumbs) . "\n");

        $table = new Table($tempOutput);
        $table->setStyle('compact');
        $nodeInfo = $this->getNodeInfo->forNode($node);
        foreach ($nodeInfo as $key => $value) {
            $table->addRow(['<subnode>' . $key . '</subnode>', $value]);
        }
        $table->render();
        $this->infoSection->overwrite($tempOutput->fetch());
    }
}
