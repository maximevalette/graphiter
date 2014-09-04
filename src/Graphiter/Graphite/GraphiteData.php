<?php

namespace Graphiter\Graphite;

/**
 * Class GraphiteData
 *
 * @package Graphiter\Graphite
 */
class GraphiteData
{
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $target
     * @param string $lookback
     *
     * @return array
     * @throws \Exception
     */
    public function fetch($target, $lookback)
    {
        $args = ['target' => $target, 'format' => 'json', 'from' => "-{$lookback}min"];
        $url = $this->config['url'] . '/render?' . http_build_query($args);

        return $url;
    }

    /**
     * @param array       $data
     * @param string|null $fetch
     *
     * @return array
     * @throws \Exception
     */
    public function reformat(array $data, $fetch = null)
    {
        $out          = array();
        $selectedData = $data[0];

        if (!empty($fetch)) {
            foreach ($data as $i => $item) {
                if ($item['target'] == $fetch) {
                    $selectedData = $item;
                }
            }
        }

        if (!isset($selectedData['datapoints'])) {
            throw new \Exception("Bad data from graphite");
        }
        foreach ($selectedData['datapoints'] as $point) {
            if (is_null($point[0])) {
                continue;
            }
            $out[$point[1]] = $point[0];
        }

        return $out;
    }
}
