<?php

namespace Graphiter\Graphite;

use Exception;
use Symfony\Component\Console\Output\Output;
use Graphiter\Data\Units;

/**
 * Class GraphiteMonitor
 *
 * @package Graphiter\Graphite
 */
class GraphiteMonitor
{
    protected $metrics;
    protected $options;
    protected $lookback = 10;
    protected $threshold = 2;
    protected $output;
    protected $alerter;
    protected $graphite;
    protected $db = [];

    /**
     * @param Output       $output
     * @param string       $alerter
     * @param GraphiteData $graphite
     * @param array        $metrics
     * @param array        $options
     */
    public function __construct(Output $output, $alerter, GraphiteData $graphite, array $metrics, $options)
    {
        $this->output   = $output;
        $this->alerter  = $alerter;
        $this->metrics  = $metrics;
        $this->graphite = $graphite;
        $this->options  = $options;

        $this->lookback  = $options['lookback'];
        $this->threshold = $options['threshold'];

        if (file_exists($this->options['db_file'])) {
            $this->db = unserialize(file_get_contents($this->options['db_file']));
        }
    }

    /**
     * Monitor
     */
    public function monitor()
    {
        $units = new Units();

        foreach ($this->metrics as $metric => $config) {

            $threshold = (isset($config['threshold'])) ? $config['threshold'] : $this->threshold;
            $lookback = (isset($config['lookback'])) ? $config['lookback'] : $this->lookback;

            if ($this->output->isVerbose()) {
                $this->output->writeln('Fetching ' . $config['target']);
            }

            $data = $this->graphite->fetch($config['target'], $lookback, $config['fetch']);

            $warnLevel  = $units->toRaw($config['warn']);
            $alertLevel = $units->toRaw($config['alert']);

            $warn     = 0;
            $alert    = 0;
            $badValue = null;

            if ($this->output->isVerbose()) {
                $this->output->writeln('Values: ' . implode(', ', $data));
            }

            $checkOrder = 'gt';

            if ($warnLevel > $alertLevel) {
                $checkOrder = 'lt';
            }

            foreach ($data as $ts => $value) {
                if ($checkOrder == 'gt') {
                    if ($value >= $warnLevel) {
                        if ($this->output->isVerbose()) {
                            $this->output->writeln('<error>Value ' . $value . ' is above Warn level ' . $warnLevel . '</error>');
                        }
                        $badValue = $value;
                        $warn++;
                    }
                    if ($value >= $alertLevel) {
                        if ($this->output->isVerbose()) {
                            $this->output->writeln('<error>Value ' . $value . ' is above Alert level ' . $alertLevel . '</error>');
                        }
                        $badValue = $value;
                        $alert++;
                    }
                } else {
                    if ($value <= $warnLevel) {
                        if ($this->output->isVerbose()) {
                            $this->output->writeln('<error>Value ' . $value . ' is below Warn level ' . $warnLevel . '</error>');
                        }
                        $badValue = $value;
                        $warn++;
                    }
                    if ($value <= $alertLevel) {
                        if ($this->output->isVerbose()) {
                            $this->output->writeln('<error>Value ' . $value . ' is below Alert level ' . $alertLevel . '</error>');
                        }
                        $badValue = $value;
                        $alert++;
                    }
                }
            }

            $args = ['width' => 586, 'height' => 307, 'target' => "alias({$config['target']},'$metric')", 'from' => '-1h',];
            $t1   = ['target' => "alias(threshold($warnLevel),'Warn')"];
            $t2   = ['target' => "alias(threshold($alertLevel),'Alert')"];
            $url  = $this->options['graphite_url'] . "/render/?" . http_build_query($args) . '&' . http_build_query($t1) . '&' . http_build_query($t2);

            $args = ['name' => $metric, 'value' => $units->toUnit($config['unit'], $badValue), 'times' => 0, 'lookback' => $lookback, 'url' => $url];

            if ($alert > $threshold) {
                $args['times'] = $alert;
                // if we already have an outstanding alert don't send a duplicate
                if (isset($this->db[$metric]) && $this->db[$metric] == 'alert') {
                    if ($checkOrder == 'gt') {
                        $this->info($metric . ' still above Alert level ' . $args['value']);
                    } else {
                        $this->info($metric . ' still below Alert level ' . $args['value']);
                    }
                } else {
                    $this->alerter['alert']->trigger($metric, $args);
                    $this->info($metric . ' at Alert level ' . $args['value']);
                    $this->mark('alert', $metric);
                }
            } elseif ($warn > $threshold) {
                $args['times'] = $warn;
                // if we have an outstanding alert, or warn don't duplicate
                if (!empty($this->db[$metric])) {
                    if ($checkOrder == 'gt') {
                        $this->info($metric . ' still below Warn level ' . $args['value']);
                    } else {
                        $this->info($metric . ' still below Warn level ' . $args['value']);
                    }
                } else {
                    $this->alerter['warn']->trigger($metric, $args);
                    $this->info($metric . ' at Warn level ' . $args['value']);
                    $this->mark('warn', $metric);
                }
            } else {
                // all clear resolve any outstanding alerts
                if (isset($this->db[$metric])) {
                    $this->alerter[$this->db[$metric]]->resolve($metric, $args);
                    $this->info("$metric {$this->db[$metric]} resolved");
                    unset($this->db[$metric]);
                }
            }
        }
    }

    /**
     * @param string $msg
     */
    protected function info($msg)
    {
        $this->output->writeln("<info>$msg</info>");
    }

    /**
     * @param string $msg
     */
    protected function alert($msg)
    {
        $this->output->writeln("<error>$msg</error>");
    }

    /**
     * @param string $type
     * @param string $metric
     */
    protected function mark($type, $metric)
    {
        $this->db[$metric] = $type;
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        file_put_contents($this->options['db_file'], serialize($this->db));
    }
}
