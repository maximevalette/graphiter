<?php

namespace Graphiter\Alerter;

/**
 * Interface AlerterInterface
 *
 * @package Graphiter\Alerter
 */
interface AlerterInterface
{
    /**
     * @param array  $options
     * @param string $type
     */
    public function __construct(array $options, $type);

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed
     */
    public function trigger($key, array $args);

    /**
     * @param string $key
     * @param array  $args
     *
     * @return mixed
     */
    public function resolve($key, array $args);
}
