<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace PhpBench\Console\Command\Handler;

use PhpBench\Benchmark\Runner;
use PhpBench\Benchmark\RunnerContext;
use PhpBench\Model\Suite;
use PhpBench\Progress\LoggerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunnerHandler
{
    private $loggerRegistry;
    private $defaultProgress;
    private $benchPath;
    private $runner;

    public function __construct(
        Runner $runner,
        LoggerRegistry $loggerRegistry,
        $defaultProgress = null,
        $benchPath = null
    ) {
        $this->runner = $runner;
        $this->loggerRegistry = $loggerRegistry;
        $this->defaultProgress = $defaultProgress;
        $this->benchPath = $benchPath;
    }

    public static function configure(Command $command)
    {
        $command->addArgument('path', InputArgument::OPTIONAL, 'Path to benchmark(s)');
        $command->addOption('filter', [], InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore all benchmarks not matching command filter (can be a regex)');
        $command->addOption('group', [], InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Group to run (can be specified multiple times)');
        $command->addOption('parameters', null, InputOption::VALUE_REQUIRED, 'Override parameters to use in (all) benchmarks');
        $command->addOption('assert', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Override assertions');
        $command->addOption('revs', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Override number of revs (revolutions) on (all) benchmarks');
        $command->addOption('progress', 'l', InputOption::VALUE_REQUIRED, 'Progress logger to use');

        // command option is parsed before the container is compiled.
        $command->addOption('bootstrap', 'b', InputOption::VALUE_REQUIRED, 'Set or override the bootstrap file.');
        $command->addOption('group', [], InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Group to run (can be specified multiple times)');
        $command->addOption('executor', [], InputOption::VALUE_REQUIRED, 'Executor to use', 'microtime');
        $command->addOption('stop-on-error', [], InputOption::VALUE_NONE, 'Stop on the first error encountered');

        // Launcher options (processed in PhpBench.php before the container is initialized).
        $command->addOption('php-binary', null, InputOption::VALUE_REQUIRED, 'Alternative PHP binary to use');
        $command->addOption('php-config', null, InputOption::VALUE_REQUIRED, 'JSON-like object of PHP INI settings');
        $command->addOption('php-wrapper', null, InputOption::VALUE_REQUIRED, 'Prefix process launch command with this string');
    }

    public function runFromInput(InputInterface $input, OutputInterface $output, array $options = []): Suite
    {
        $context = new RunnerContext(
            $input->getArgument('path') ?: $this->benchPath,
            array_merge(
                [
                    'parameters' => $this->getParameters($input->getOption('parameters')),
                    'revolutions' => $input->getOption('revs'),
                    'filters' => $input->getOption('filter'),
                    'groups' => $input->getOption('group'),
                    'executor' => $input->getOption('executor'),
                    'stop_on_error' => $input->getOption('stop-on-error'),
                ],
                $options
            )
        );

        $progressLoggerName = $input->getOption('progress') ?: $this->defaultProgress;

        $progressLogger = $this->loggerRegistry->getProgressLogger($progressLoggerName);
        $progressLogger->setOutput($output);
        $this->runner->setProgressLogger($progressLogger);

        return $this->runner->run($context);
    }

    private function getParameters($parametersJson)
    {
        if (null === $parametersJson) {
            return;
        }

        $parameters = [];
        if ($parametersJson) {
            $parameters = json_decode($parametersJson, true);
            if (null === $parameters) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not decode parameters JSON string: "%s"', $parametersJson
                ));
            }
        }

        return $parameters;
    }
}
