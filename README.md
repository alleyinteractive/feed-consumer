# Feed Consumer

Contributors: srtfisher

Tags: alleyinteractive, feed-consumer, wordpress-plugin

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

### Requirements

This plugin requires
[Fieldmanager](https://github.com/alleyinteractive/wordpress-fieldmanager)
to be installed and activated. It is also recommended to use
[Alley Logger](https://github.com/alleyinteractive/logger) as well but that is not
required.

## Usage

Activate the plugin in WordPress and you will see the new Feeds post type
available for use:

![Screenshot of feed post type](https://user-images.githubusercontent.com/346399/208514114-06f0cc86-b4a4-42aa-b48c-b57eb84fe8fa.png)

Feed Consumer is a plugin that allows you to ingest external feeds and other
data sources into WordPress. It is built to be extensible and can be used to
ingest data from any source that can be represented as a PHP array.

### Creating a Feed

To create a feed, navigate to the Feeds post type and click Add New. You will
see a form that allows you to configure the feed. The feed configuration
includes the following fields:

- **Title**: The name of the feed. This is used to identify the feed in the
  admin.
- **Processors**: The processors to run on the feed. Processors are used to
  are classes that define the extractor, transformer, and loader to use for
  the feed. For more information, see [Processors](#processors).

The selected processor will also display any settings for the processor's
extractor, transformer, and loader.

## Processors

Processors are the core of Feed Consumer. They define the extractor,
transformer, and loader to use for the feed.

Out of the box, Feed Consumer includes the following processors:

- **JSON Processor**: Extracts data from a JSON feed into WordPress posts.
- **RSS Processor**: Extracts data from an RSS feed into WordPress posts.
- **XML Processor**: Extracts data from an XML feed into WordPress posts.

### Creating a Processor

```php
add_filter( 'feed_consumer_processors', function ( array $processors ) {
	$processors[] = My_Plugin\Processor::class;

	return $processors;
} );
```

### Extractors

Extractors will take extract data from a remote source and return it as data to
be passed to a transformer. Out of the box, Feed Consumer includes JSON, XML,
and RSS transformers among others that can be used to extract data from a
variety of sources. You can also create your own extractor by implementing the
`Feed_Consumer\Contracts\Extractor` interface.

### Transformers

Transformers will take extracted data and transform it into a format that can be
loaded into WordPress. Out of the box, Feed Consumer includes a `Post_Loader`
that will take transformed data and load it into WordPress as posts. You can
also create your own transformer by implementing the
`Feed_Consumer\Contracts\Transformer` interface.

### Loaders

Loaders will take transformed data and load it where configured. Out of the box,
the `Post_Loader` will be the most common loader used to take transformed data
and load it into WordPress as posts.

You can also create your own loader by implementing the
`Feed_Consumer\Contracts\Loader` interface.

#### Post Loader

The post loader will take transformed data and load it into WordPress. It will
also transform the content into Gutenberg blocks by default via
[wp-block-converter](https://github.com/alleyinteractive/wp-block-converter/).

## Integrations

### Byline Manager

The plugin includes an integration with [Byline
Manager](https://github.com/alleyinteractive/byline-manager) to automatically
set the byline for a post based on the feed item's author. Once the Byline
Manager plugin is enabled, the settings fields will appear on the feed to set
the default byline and to optionally use the feed item's author as the byline.

## Hooks

### `feed_consumer_run_complete`

Fires when a feed has completed running. The feed ID, loaded data, and processor
class are passed to the hook.

```php
add_action( 'feed_consumer_run_complete', function ( int $feed_id, $loaded_data, string $processor ) {
	// Do something.
}, 10, 3 );
```

### `feed_consumer_extractor_error`

Fires when an extractor encounters an error. The extractor response and
extractor class are passed to the hook.

```php
add_action( 'feed_consumer_extractor_error', function ( $response, \Feed_Consumer\Contracts\Extractor $extractor ) {
	// Do something.
}, 10, 2 );
```

### `feed_consumer_pre_feed_fetch`

Fires when a feed is about to be fetched.

```php
use Mantle\Http_Client\Pending_Request;
use Feed_Consumer\Contracts\Processor;

add_action( 'feed_consumer_pre_feed_fetch', function ( Pending_Request $request, Processor $processor, array $settings ) {
	// Do something.
}, 10, 3 );
```

### `feed_consumer_feed_fetch`

Fires when a feed has been fetched.

```php
use Mantle\Http_Client\Response;
use Feed_Consumer\Contracts\Processor;

add_action( 'feed_consumer_feed_fetch', function ( Response $response, Processor $processor, array $settings ) {
	// Do something.
}, 10, 3 );
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

- [Sean Fisher](https://github.com/srtfisher)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
