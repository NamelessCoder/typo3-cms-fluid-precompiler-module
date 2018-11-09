<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Command to profile Fluid templates
 */
class FluidTemplateProfileCommand extends Command
{
    use FluidPrecompilerServiceTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Profile Fluid templates')
            ->addArgument(
                'extension',
                InputArgument::REQUIRED,
                'Extension key of the Fluid template'
            )
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Path to the template'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        $outputHelper = new SymfonyStyle($input, $output);

        // Get arguments and options
        $extension = $input->getArgument('extension');
        $template = $input->getArgument('template');

        $result = $this->getPrecompilerService()->profileTemplateFile($template, $extension);

        // General information
        $outputHelper->section('General');
        $rows = [
            [
                'name' => 'template',
                'value' => $result['identifier']
            ],
            [
                'name' => 'layoutName',
                'value' => $result['layoutName']
            ],
            [
                'name' => 'layoutFilename',
                'value' => $result['layoutFilename']
            ],
            [
                'name' => 'compilable',
                'value' => $result['compilable']
            ],
            [
                'name' => 'efficiency',
                'value' => $result['efficiency']
            ],
            [
                'name' => 'sections (' . count($result['sections']) . ')',
                'value' => implode(", ", $result['sections'])
            ],
            [
                'name' => 'nodes',
                'value' => $result['nodes']
            ]
        ];

        $outputHelper->table(
            ['Info', 'Value'],
            $rows
        );

        // Namespaces
        $outputHelper->section('Namespaces');
        /** @var RenderingContextInterface $context */
        $context = $result['context'];
        $namespaces = $context->getViewHelperResolver()->getNamespaces();
        $namespaces = array_map(function ($phpNamespaces) {
            return implode(', ', $phpNamespaces);
        }, $namespaces);

        $rows = [];
        foreach ($namespaces as $alias => $phpNamespaces) {
            $rows[] = [
                'alias' => $alias,
                'namespaces' => $phpNamespaces
            ];
        }

        $outputHelper->table(
            array_keys($rows[0]),
            $rows
        );


        // ViewHelpers
        $outputHelper->section('ViewHelpers');
        if ($result['helpers']) {
            $viewHelpers = $result['helpers'];

            $rows = [];
            foreach ($viewHelpers as $className => $helper) {
                $rows[] = [
                    'name' => $className,
                    'escaped' => $helper['escaped'],
                    'usages' => $helper['usages'],
                    'efficiency' => $helper['efficiency']
                ];
            }

            $outputHelper->table(
                array_keys($rows[0]),
                $rows
            );
        } else {
            $logger->info('No ViewHelpers used.');
        }


        // Partials
        $outputHelper->section('Partials');
        if ($result['fanouts']) {
            foreach ($result['fanouts'] as $fanout) {
                $outputHelper->text(vsprintf("Partial: %s\nSection:%s", [$fanout['partial'], $fanout['section']]));

                $rows = [];

                foreach ($fanout['arguments'] as $argument => $argumentInfo) {
                    $rows[] = [
                        'name' => $argument,
                        'type' => $argumentInfo['type'],
                        'value' => $argumentInfo['source']
                    ];
                }

                $outputHelper->table(
                    array_keys($rows[0]),
                    $rows
                );

                $outputHelper->newLine();
            }
        }
    }
}