<?php

namespace Paneon\PhpToTypeScriptBundle\Command;

use Paneon\PhpToTypeScript\Services\ParserService;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCommandSingle extends Command
{
    protected static string $defaultName = 'typescript:generate-single';

    public function __construct(private ParserService $parserService, private Filesystem $fs)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate TypeScript interfaces from PHP classes in a directory')
            ->addArgument(
                'inputFile',
                InputArgument::REQUIRED,
                'The file to parse'
            )
            ->addArgument(
                'outputDir',
                InputArgument::OPTIONAL,
                'Where to export the generated class to',
                join(DIRECTORY_SEPARATOR, ['assets', 'js', 'interfaces'])
            )
            ->addArgument(
                'indent',
                InputArgument::OPTIONAL,
                'Changes the indentation size',
                2
            )
            ->addArgument(
                'prefix',
                InputArgument::OPTIONAL,
                'Adds an prefix to the interface class',
                ''
            )
            ->addArgument(
                'suffix',
                InputArgument::OPTIONAL,
                'Add a suffix to the interface class',
                ''
            )
            ->addOption(
                'nullable',
                null,
                InputOption::VALUE_OPTIONAL,
                'Whether or not to use Null-aware types for TS v2 and later',
                false
            );
    }

    /**
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceFileName = $input->getArgument('inputFile');
        $outputDir = $input->getArgument('outputDir');
        $prefix = $input->getArgument('prefix');
        $suffix = $input->getArgument('suffix');
        $indent = $input->getArgument('indent');
        $includeTypeNullable = $input->getOption('nullable') !== false;

        if ($outputDir[-1] !== DIRECTORY_SEPARATOR) {
            $outputDir .= DIRECTORY_SEPARATOR;
        }

        if ($includeTypeNullable) {
            $this->parserService->setIncludeTypeNullable($includeTypeNullable);
        }

        $targetFile = $outputDir . $this->parserService->getOutputFileName($sourceFileName);

        $output->writeln('Generating files...');

        if($prefix){
            $this->parserService->setPrefix($prefix);
        }
        if($suffix){
            $this->parserService->setSuffix($suffix);
        }
        if($indent){
            $this->parserService->setIndent($indent);
        }

        $content = $this->parserService->getInterfaceContent(
            $sourceFileName,
            false
        );

        if ($content) {
            $this->fs->dumpFile($targetFile, $content);

            $output->writeln(
                [
                    '',
                    'Processed:',
                    '- In:  ' . $sourceFileName,
                    '- Out: ' . $targetFile,
                ]
            );
        }

        $output->writeln(PHP_EOL . '...done!');

        return 0;
    }
}
