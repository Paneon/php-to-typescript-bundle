<?php

namespace Paneon\PhpToTypeScriptBundle\Command;

use Paneon\PhpToTypeScript\Services\ParserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCommand extends Command
{
    protected static $defaultName = 'typescript:generate';

    public function __construct(
        protected ParserService $parserService,
        protected Filesystem $fs,
        protected ?array $additionalFiles,
        ?string $prefix,
        ?string $suffix,
        ?int $indent,
        protected bool $nullable,
        protected string $inputDirectory,
        protected string $outputDirectory,
        protected array $additionalDirectories,
    ) {
        parent::__construct();

        if ($prefix) {
            $this->parserService->setPrefix($prefix);
        }
        if ($suffix) {
            $this->parserService->setSuffix($suffix);
        }
        if ($indent) {
            $this->parserService->setIndent($indent);
        }
        if ($nullable) {
            $this->parserService->setIncludeTypeNullable($nullable);
        }
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate TypeScript interfaces from PHP classes in a directory')
            ->addOption(
                'nullable',
                null,
                InputOption::VALUE_OPTIONAL,
                'Whether or not to use Null-aware types for TS v2 and later',
                false
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $includeTypeNullable = $input->getOption('nullable') !== false;

        if ($includeTypeNullable) {
            $this->parserService->setIncludeTypeNullable($includeTypeNullable);
        }

        // Process project source code
        $this->processDirectory($this->inputDirectory, $this->outputDirectory, true, $output);

        // Process external files
        if ($this->additionalDirectories) {
            foreach ($this->additionalDirectories as $fromDir => $configArray) {
                $this->processDirectory(
                    $fromDir,
                    $this->outputDirectory . $configArray['output'],
                    $configArray['requireAnnotation'],
                    $output
                );
            }
        }

        foreach ($this->additionalFiles as $additionalFile => $configArray) {

            $sourceFileName = $additionalFile;
            $additionalFileOutputDir = $this->outputDirectory . $configArray['output'];

            if ($additionalFileOutputDir[-1] !== DIRECTORY_SEPARATOR) {
                $additionalFileOutputDir .= DIRECTORY_SEPARATOR;
            }

            $content = $this->parserService->getInterfaceContent($sourceFileName, false);

            $targetFile = $additionalFileOutputDir . $this->parserService->getOutputFileName($sourceFileName);
            $this->fs->dumpFile($targetFile, $content);

            $output->writeln('- ' . $sourceFileName . ' => ' . $targetFile);
        }


        $output->writeln(PHP_EOL . '...done!');

        return 0;
    }

    public function processDirectory(string $inputDir, string $outputDir, bool $requireAnnotation, OutputInterface $io)
    {
        if ($inputDir[-1] !== DIRECTORY_SEPARATOR) {
            $inputDir .= DIRECTORY_SEPARATOR;
        }

        if ($outputDir[-1] !== DIRECTORY_SEPARATOR) {
            $outputDir .= DIRECTORY_SEPARATOR;
        }

        $files = $this->rglob($inputDir . '*.php');

        $io->writeln('Processing directory: ' . $inputDir);

        foreach ($files as $sourceFileName) {

            $content = $this->parserService->getInterfaceContent($sourceFileName, $requireAnnotation);

            if ($content) {
                $diffStart = strpos($sourceFileName, $inputDir) + strlen($inputDir);
                $diffEnd = strrpos($sourceFileName, DIRECTORY_SEPARATOR);
                $directoryDiff = substr($sourceFileName, $diffStart, $diffEnd - $diffStart + 1);

                $targetFile = $outputDir . $directoryDiff . $this->parserService->getOutputFileName($sourceFileName);
                $this->fs->dumpFile($targetFile, $content);

                $io->writeln('- ' . $sourceFileName . ' => ' . $targetFile);
            }
        }
    }

    private function rglob($pattern_in, int $flags = 0): array
    {
        $patterns = array();
        if ($flags & GLOB_BRACE) {
            if (preg_match_all('#\{[^.\}]*\}#i', $pattern_in, $matches)) {
                // Get all GLOB_BRACE entries.
                $brace_entries = array();
                foreach ($matches [0] as $index => $match) {
                    $brace_entries [$index] = explode(',', substr($match, 1, -1));
                }

                // Create cartesian product.
                // @source: https://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
                $cart = array(
                    array()
                );
                foreach ($brace_entries as $key => $values) {
                    $append = array();
                    foreach ($cart as $product) {
                        foreach ($values as $item) {
                            $product [$key] = $item;
                            $append [] = $product;
                        }
                    }
                    $cart = $append;
                }

                // Create multiple glob patterns based on the cartesian product.
                foreach ($cart as $vals) {
                    $c_pattern = $pattern_in;
                    foreach ($vals as $index => $val) {
                        $c_pattern = preg_replace(
                            DIRECTORY_SEPARATOR . $matches [0] [$index] . DIRECTORY_SEPARATOR,
                            $val,
                            $c_pattern,
                            1
                        );
                    }
                    $patterns [] = $c_pattern;
                }
            } else {
                $patterns [] = $pattern_in;
            }
        } else {
            $patterns [] = $pattern_in;
        }

        // @source: http://php.net/manual/en/function.glob.php#106595
        $result = array();
        foreach ($patterns as $pattern) {
            $files = glob($pattern, $flags);
            foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                $files = array_merge($files, $this->rglob($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
            }
            $result = array_merge($result, $files);
        }
        return $result;
    }
}
