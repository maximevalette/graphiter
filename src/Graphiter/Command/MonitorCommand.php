<?php

namespace Graphiter\Command;

use Graphiter\Alerter\ProwlAlerter;
use Graphiter\Alerter\TwilioAlerter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Graphiter\Alerter\MultiAlerter;
use Graphiter\Alerter\EmailAlerter;
use Graphiter\Graphite\GraphiteData;
use Graphiter\Graphite\GraphiteMonitor;

/**
 * Class MonitorCommand
 *
 * @package Graphiter\Command
 */
class MonitorCommand extends Command
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Configure
     */
    protected function configure()
    {
        $this->setName('monitor')->setDescription('Poll graphite based on configured alerts')->addOption('dry-run', null, InputOption::VALUE_NONE, "Just print alerts, don't send them to alerters");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();

        $config = include __DIR__ . "/../../../resources/config.php";

        $alerters = ['alert' => new MultiAlerter([]), 'warn' => new MultiAlerter([]),];

        if (!$options['dry-run']) {

            $alerters = [
                'warn' =>
                    new MultiAlerter([
                            new EmailAlerter($config['email'], 'warn'),
                            new ProwlAlerter($config['prowl'], 'warn'),
                        ]
                    ),
                'alert' =>
                    new MultiAlerter([
                            new EmailAlerter($config['email'], 'alert'),
                            new ProwlAlerter($config['prowl'], 'alert'),
                            new TwilioAlerter($config['twilio'], 'alert'),
                        ]
                    ),
            ];

        }

        $options['graphite_url'] = $config['graphite']['url'];
        $options['threshold']    = $config['threshold'];
        $options['lookback']     = $config['lookback'];

        $metrics = $this->processTemplates($config['metrics']);

        $graphite = new GraphiteData($config['graphite']);
        $monitor  = new GraphiteMonitor($output, $alerters, $graphite, $metrics, $options);

        $output->writeln("<info>Found " . count($metrics) . " metrics</info>");

        foreach (array_keys($metrics) as $metric) {
            $output->writeln("<info>    $metric</info>");
        }

        $monitor->monitor($output);
    }

    /**
     * @param array $metrics
     *
     * @return mixed
     */
    protected function processTemplates(array $metrics)
    {
        foreach ($metrics as $metric => $config) {
            if (isset($config['template'])) {
                unset($metrics[$metric]);
                foreach ($config['keys'] as $key) {
                    foreach ($config['template'] as $m => $c) {
                        $m           = sprintf($m, $key);
                        $c['target'] = sprintf($c['target'], $key);
                        $c['fetch']  = sprintf($c['fetch'], $key);
                        $metrics[$m] = $c;
                    }
                }
            }
        }

        return $metrics;
    }
}
