<?php

namespace Graphiter\Alerter;

use Prowl;

/**
 * Class ProwlAlerter
 *
 * @package Graphiter\Alerter
 */
class ProwlAlerter implements AlerterInterface
{
    protected $options;
    protected $config;
    protected $type;

    /**
     * @param array  $options
     * @param string $type
     */
    public function __construct(array $options, $type)
    {
        $this->options = $options;
        $this->config  = $options[$type];
        $this->type    = strtoupper($type);
    }

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed|void
     */
    public function trigger($key, array $args)
    {
        if ($this->options === false) {
            return;
        }

        $args['type']   = $this->type;
        $args['action'] = 'TRIGGERED';

        foreach ($args as $k => $data) {
            $args['{' . $k . '}'] = $data;
            unset($args[$k]);
        }

        $msg = strtr($this->config['trigger'], $args);

        $this->alert($msg);
    }

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed|void
     */
    public function resolve($key, array $args)
    {
        if ($this->options === false) {
            return;
        }

        $args['type']   = $this->type;
        $args['action'] = 'RESOLVED';

        foreach ($args as $k => $data) {
            $args['{' . $k . '}'] = $data;
            unset($args[$k]);
        }

        $msg = strtr($this->config['resolve'], $args);

        $this->alert($msg);
    }

    /**
     * @param string $msg
     */
    protected function alert($msg)
    {
        $oProwl = new Prowl\Connector();
        $oMsg = new Prowl\Message();

        $oFilter = new Prowl\Security\PassthroughFilterImpl();
        $oProwl->setFilter($oFilter);

        $oProwl->setIsPostRequest(true);
        $oMsg->setPriority(1);

        $oMsg->addApiKey($this->options['key']);
        $oMsg->setEvent($msg);
        $oMsg->setApplication('Graphiter');

        $oProwl->push($oMsg);
    }
}
