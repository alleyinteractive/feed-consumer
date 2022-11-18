# Feed Consumer

Contributors: srtfisher

Tags: alleyinteractive, feed-consumer

Stable tag: 0.1.0

Requires at least: 5.9

Tested up to: 6.0

Requires PHP: 8.0

License: GPL v2 or later

[![Coding Standards](https://github.com/alleyinteractive/feed-consumer/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/feed-consumer/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/feed-consumer/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/feed-consumer/actions/workflows/unit-test.yml)

Ingest external feeds and other data sources into WordPress.

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/feed-consumer
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
$plugin = Feed_Consumer\Feed_Consumer\Feed_Consumer();
$plugin->perform_magic();
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.co/careers/).

- [Sean Fisher](https://github.com/Sean Fisher)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.