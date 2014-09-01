<?php

namespace Graphiter\Graphite;

use Guzzle\Http\Client;

/**
 * Class GraphiteData
 *
 * @package Graphiter\Graphite
 */
class GraphiteData
{
    protected $config;
    protected $client;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->client = new Client($config['url']);
    }

    /**
     * @param string      $target
     * @param string      $lookback
     * @param string|null $fetch
     *
     * @return array
     * @throws \Exception
     */
    public function fetch($target, $lookback, $fetch = null)
    {
        $args = ['target' => $target, 'format' => 'json', 'from' => "-{$lookback}min"];

        $request = $this->client->get('/render?' . http_build_query($args))->setAuth($this->config['user'], $this->config['pass'], 'Digest');

        $r    = $request->send();
        $data = $r->json();

        return $this->reformat($data, $fetch);
    }

    /**
     * @param array       $data
     * @param string|null $fetch
     *
     * @return array
     * @throws \Exception
     */
    protected function reformat(array $data, $fetch = null)
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
