<?php

return [
    'db_file' => '/tmp/graphite-alert.db',
    'lookback' => 10, // number of minutes to look back
    'threshold' => 2, // number of points above warn or alert before, do anything

    'graphite' => [
        'url' => 'http://graphite.example.com',
        'user' => 'user',
        'pass' => 'password',
    ],

    'email' => [
        'from' => 'noreply@example.com',
        'to' => 'noreply@example.com',
        'subject' => '{action}: {name} has reached {type}.',
        'warn' => [
            'trigger' => "Metric {name} has reached its warning threshold.\n{times} times in the last {lookback} minutes, last bad {value}\n\n{url}",
            'resolve' => "Metric {name} has recovered.\n\n{url}"
        ],
        'alert' => [
            'trigger' => "Metric {name} has reached its alert threshold!\n{times} times in the last {lookback} minutes, last bad {value}\n\n{url}",
            'resolve' => "Metric {name} has recovered.\n\n{url}"
        ],
        'smtp' => [
            'host' => 'localhost',
            'port' => 1025,
        ],
    ],

    'twilio' => [
        'sid' => '',
        'token' => '',
        'from' => '+111111111',
        'call' => '+111111111',
    ],

    'prowl' => [
        'key' => '',
        'warn' => [
            'trigger' => "Metric {name} has reached its warning threshold. {times} times in the last {lookback} minutes, last bad {value}",
            'resolve' => "Metric {name} has recovered."
        ],
        'alert' => [
            'trigger' => "Metric {name} has reached its alert threshold! {times} times in the last {lookback} minutes, last bad {value}",
            'resolve' => "Metric {name} has recovered."
        ],
    ],

    'metrics' => [
        'metric1' => [
            'target' => 'derivative(x.y.server.bytes_sent)',
            'warn' => '1200M',
            'alert' => '1500M',
            'unit' => 'BtoM',
        ],
        'metric2' => [
            'template' => [
                'metric2-%s' => [
                    'target' => 'derivative(%1$s.y.server.bytes_sent)',
                    'warn' => 10000,
                    'alert' => 1000,
                    'unit' => null,
                ],
            ],
            'keys' => ['machine1', 'machine2', 'machine3']
        ],
    ]
];
