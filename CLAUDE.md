# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

Feed Consumer is a WordPress plugin by Alley Interactive that ingests external feeds (JSON, RSS, XML, or anything representable as a PHP array) into WordPress. Each feed is a `feed_consumer` post whose processor pairs an extractor, a transformer, and a loader. It is published to Packagist as `alleyinteractive/feed-consumer` and used by third-party sites, so the classes in `src/` are public API.

## Rules

- Classes live in `src/` and are autoloaded from the `Feed_Consumer\` namespace through `alleyinteractive/composer-wordpress-autoloader`, using WordPress file names (`class-*.php`, `interface-*.php`, `trait-*.php`).
- Processors, extractors, transformers, and loaders are extended by other plugins. Don't add parameter or return types to their public or protected methods, or change their signatures, without treating it as a breaking change.
- Settings UI depends on Fieldmanager at runtime; it is not a Composer dependency.
- Tests extend `tests/TestCase.php`, use Mantle Testkit, and follow PSR-4 (`Feed_Consumer\Tests\` → `tests/`).
- Record user-facing changes under "Unreleased" in `CHANGELOG.md`.

## Common commands

```sh
composer install
composer test        # phpcs then phpunit
composer phpcs       # alley-coding-standards
composer phpcbf      # auto-fix coding standards
composer phpunit     # PHPUnit only (SQLite, no WordPress install needed)
```

Run a single test: `vendor/bin/phpunit --filter RunnerTest`.
