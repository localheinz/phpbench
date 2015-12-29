<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Console\Command;

use PhpBench\Benchmark\SuiteDocument;
use PhpBench\Report\ReportManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PhpBench\Console\Command\Handler\ReportHandler;

class ReportCommand extends Command
{
    private $reportHandler;

    public function __construct(
        ReportHandler $reportHandler
    ) {
        parent::__construct();
        $this->reportHandler = $reportHandler;
    }

    public function configure()
    {
        $this->setName('report');
        $this->setDescription('Generate a report from an XML file');
        $this->setHelp(<<<EOT
Generate a report from an existing XML file.

To dump an XML file, use the <info>run</info> command with the
<comment>dump-file</comment option.
EOT
        );
        ReportHandler::configure($this);

        $this->addOption('file', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Report XML file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getOption('file');

        if (!$files) {
            throw new \InvalidArgumentException(
                'You must specify at least one result --file (generated by the run command\'s `--dump-file=` option)'
            );
        }

        if (!$input->getOption('report')) {
            throw new \InvalidArgumentException(
                'You must specify or configure at least one report, e.g.: --report=default'
            );
        }

        $aggregateDom = new SuiteDocument();
        $aggregateEl = $aggregateDom->createRoot('phpbench');

        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find suite result file "%s" (cwd: %s)', $file, getcwd()
                ));
            }

            $suiteResult = new SuiteDocument();
            $suiteResult->loadXml(file_get_contents($file));

            foreach ($suiteResult->xpath()->query('//suite') as $suiteEl) {
                $suiteEl = $aggregateDom->importNode($suiteEl, true);

                if (!$suiteEl->getAttribute('name')) {
                    $suiteEl->setAttribute('name', basename($file));
                }

                $aggregateEl->appendChild($suiteEl);
            }
        }

        $this->reportHandler->reportsFromInput($input, $output, $aggregateDom);
    }
}
