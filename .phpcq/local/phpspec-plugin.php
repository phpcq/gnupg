<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;

return new class implements DiagnosticsPluginInterface, OutputTransformerFactoryInterface {
    public function getName(): string
    {
        return 'phpspec';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeStringOption('config_file', 'The phpspec.yml configuration file')
            ->isRequired()
            ->withDefaultValue('phpspec.yml');

        $configOptionsBuilder->describeStringListOption(
            'custom_flags',
            'Any custom flags to pass to phpunit. For valid flags refer to the phpunit documentation.',
        );

        $configOptionsBuilder
            ->describeStringOption('phpspec_path', 'The path to the phpspec binary')
            ->isRequired()
            ->withDefaultValue('vendor/bin/phpspec');
    }

    public function createDiagnosticTasks(PluginConfigurationInterface $config, EnvironmentInterface $environment): iterable
    {
        $projectRoot = $environment->getProjectConfiguration()->getProjectRootPath();
        yield $environment
            ->getTaskFactory()
            ->buildPhpProcess('phpspec', $this->buildArguments($config))
            ->withWorkingDirectory($projectRoot)
            ->withOutputTransformer($this)
            ->build();
    }

    private function buildArguments(PluginConfigurationInterface $config): array
    {
        $arguments = [
            $config->getString('phpspec_path'),
            'run',
            '--format=junit',
            '-c',
            $config->getString('config_file')
        ];
        if ($config->has('custom_flags')) {
            foreach ($config->getStringList('custom_flags') as $flag) {
                $arguments[] = $flag;
            }
        }

        return $arguments;
    }

    public function createFor(TaskReportInterface $report): OutputTransformerInterface
    {
        return new class($report) implements OutputTransformerInterface {
            /** @var TaskReportInterface $report */
            private $report;
            /** @var string */
            private $buffer = '';

            public function __construct(TaskReportInterface $report)
            {
                $this->report = $report;
            }

            public function write(string $data, int $channel): void
            {
                if (OutputInterface::CHANNEL_STDOUT === $channel) {
                    $this->buffer .= $data;
                }
            }

            public function finish(int $exitCode): void
            {
                $xmlDocument = new DOMDocument('1.0');
                $xmlDocument->loadXML($this->buffer);

                $rootNode = $xmlDocument->firstChild;

                if (!$rootNode instanceof DOMNode || $rootNode->nodeName !== 'testsuites') {
                    return;
                }

                foreach ($rootNode->childNodes as $childNode) {
                    if ((!$childNode instanceof DOMElement) || ($childNode->nodeName !== 'testsuite')) {
                        continue;
                    }
                    $this->walkTestSuite($childNode);
                }

                $this->report->close(
                    $exitCode === 0 ? TaskReportInterface::STATUS_PASSED : TaskReportInterface::STATUS_FAILED
                );
            }

            private function walkTestSuite(DOMElement $testsuite): void
            {
                foreach ($testsuite->childNodes as $childNode) {
                    if (!$childNode instanceof DOMElement) {
                        continue;
                    }

                    switch ($childNode->nodeName) {
                        case 'testsuite':
                            $this->walkTestSuite($childNode);
                            break;
                        case 'testcase':
                            $this->walkTestCase($childNode, $testsuite->getAttribute('name'));
                    }
                }
            }

            private function walkTestCase(DOMElement $testCase, string $testSuite): void
            {
                $severity = $this->getSeverity($testCase);
                if (null === $severity) {
                    return;
                }

                $className = $classFile = $testCase->getAttribute('classname');
                $report    = false;

                foreach ($testCase->childNodes as $childNode) {
                    if (!$childNode instanceof DOMElement) {
                        continue;
                    }
                    if (! in_array($childNode->nodeName, ['failure', 'error', 'skipped'])) {
                        continue;
                    }

                    $report = true;
                    $this->report
                        ->addDiagnostic($severity, $childNode->getAttribute('message'))
                        ->forClass($testSuite)
                        ->fromSource($className . ': ' . $testCase->getAttribute('name'))
                        ->end();
                }

                if ($report === false) {
                    $this->report
                        ->addDiagnostic($severity, $testSuite . ': ' . $testCase->getAttribute('name'))
                        ->forClass($testSuite)
                        ->fromSource($className);
                }
            }

            /** @psalm-return ?TDiagnosticSeverity */
            private function getSeverity(DOMElement $childNode): ?string
            {
                switch ($childNode->getAttribute('status')) {
                    case 'passed':
                        return TaskReportInterface::SEVERITY_INFO;

                    case 'failed':
                        return TaskReportInterface::SEVERITY_MAJOR;

                    case 'broken':
                        return TaskReportInterface::SEVERITY_MINOR;

                    case 'skipped':
                    case 'pending':
                        return TaskReportInterface::SEVERITY_MARGINAL;

                    default:
                        return null;
                }
            }
        };
    }
};
