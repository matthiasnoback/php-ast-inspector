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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class InspectCommand extends Command
{
    public const COMMAND_NAME = 'inspect';

    private CodeFormatter $codeFormatter;

    private OutputInterface $output;

    private Parser $parser;

    private GetNodeInfo $getNodeInfo;

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
        $this->output = $output;
        $this->output->getFormatter()
            ->setStyle(CodeFormatter::HIGHLIGHT_TAG, new OutputFormatterStyle('yellow', '', ['bold']));
        $this->output->getFormatter()
            ->setStyle(CodeFormatter::LINE_NUMBER_TAG, new OutputFormatterStyle('gray', '', []));

        $fileArgument = $input->getArgument('file');
        assert(is_string($fileArgument));
        $code = file_get_contents($fileArgument);
        if (!is_string($code)) {
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
                $choices['d'] = 'next node';
            }
            if ($navigator->hasPreviousNode()) {
                $choices['a'] = 'previous node';
            }
            if ($navigator->hasParentNode()) {
                $choices['s'] = 'parent node';
            }
            if ($navigator->hasSubnode()) {
                $choices['w'] = 'inspect subnodes';
            }

            $choices['q'] = 'quit';

            $nextAction = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion('<question>Next?</question>', $choices)
            );
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
        $this->output->write(
            $this->codeFormatter->format($code, Highlight::createForPhpParserNode($node)) . "\n\n"
        );

        $breadcrumbs = (new NodeNavigator($node))->breadcrumbs();
        $breadcrumbs[count($breadcrumbs) - 1] = '<info>' . $breadcrumbs[count($breadcrumbs) - 1] . '</info>';
        $this->output->writeln('Current node: ' . implode(' > ', $breadcrumbs) . "\n");

        $table = new Table($this->output);
        $table->setStyle('compact');
        $nodeInfo = $this->getNodeInfo->forNode($node);
        foreach ($nodeInfo as $key => $value) {
            $table->addRow(['<comment>' . $key . '</comment>', $value]);
        }
        $table->render();
        $this->output->writeln('');
    }
}
