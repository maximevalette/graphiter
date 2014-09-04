Graphiter
=========

Monitors metrics from Graphite and sends alerts through different alerters. Requires PHP 5.4+.

Supported alerters:

- Email
- Twilio
- Prowl

Supported features:

- Warn and Alert levels
- Both ways detection (above or below)
- Templates
- Per-metric threshold
- Per-metric lookback
- Alerter Interface (PR welcome)

To-Do:

- Custom database interface instead of static file
- Parallel URL queries
- New alerters (PagerDuty? Twitter? IFTTT?)
- Different targets per alerters (and metrics)
- Adding a Pending status to wait for the next fetching
- Test everything

Setup
-----

Installing dependencies using composer:

```
composer install
```

Copy and edit sample config file:

```
cp ./resources/config.dist.php ./resources/config.php
```

Edit config, set different alerters and metrics, then run the console app.

```
./app/console monitor
```

Testing
-------

Only Code Sniffing is currently supported:

```
./bin/coke
```

Thanks
------

Thanks to the original project [graphite-alert](https://github.com/jeichorn/graphite-alert).
Didn't fork it because Graphiter is too different in a lot of ways.