<?php

namespace Graphiter\Alerter;

use Services_Twilio;

/**
 * Class TwilioAlerter
 *
 * @package Graphiter\Alerter
 */
class TwilioAlerter implements AlerterInterface
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

        $this->call();
    }

    /**
     * @param string $key
     * @param array  $args
     *
     * @return void
     */
    public function resolve($key, array $args)
    {
        return;
    }

    /**
     * Call
     */
    protected function call()
    {
        $client = new Services_Twilio($this->options['sid'], $this->options['token']);

        $client->account->calls->create(
            $this->options['from'],
            $this->options['to'],
            'http://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient'
        );
    }
}
