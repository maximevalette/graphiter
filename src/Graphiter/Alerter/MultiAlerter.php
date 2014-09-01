<?php

namespace Graphiter\Alerter;

/**
 * Class MultiAlerter
 *
 * @package Graphiter\Alerter
 */
class MultiAlerter
{
    protected $alerters;

    /**
     * @param array $alerters
     */
    public function __construct(array $alerters)
    {
        $this->alerters = $alerters;
    }

    /**
     * @param string $key
     * @param array  $args
     */
    public function trigger($key, array $args)
    {
        /** @var AlerterInterface $alerter */
        foreach ($this->alerters as $alerter) {
            $alerter->trigger($key, $args);
        }
    }

    /**
     * @param string $key
     * @param array  $args
     */
    public function resolve($key, array $args)
    {
        /** @var AlerterInterface $alerter */
        foreach ($this->alerters as $alerter) {
            $alerter->resolve($key, $args);
        }
    }
}
