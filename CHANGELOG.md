# Changelog

All notable changes to `Feed Consumer` will be documented in this file.

## v1.1.2

- Only update last run time on successful run.

## v1.1.1

- Fix timezone issue when displaying next feed run time.

## v1.1.0

- Bump minimum requirement to PHP 8.2.
- Allow opt-in sideloading of images for XML/RSS/JSON transformers.

## v1.0.2

- Reschedule feeds and record their last run time regardless of the outcome of the feed execution.

## v1.0.1

- Re-issuing release for an underlying update to [wp-block-converter](https://github.com/alleyinteractive/wp-block-converter/releases/tag/v1.5.0).
## v1.0.0

### Changed

- Imported images with the `Post_Loader` are properly attached to the parent post.
- RSS Transformer will use `content:encoded` first and then fallback to `description` if `content:encoded` is not available.
- Bumps minimum requirement to PHP 8.1.

## v0.1.2 - 2023-05-16

- Require `mantle-framework/http-client`.

## v0.1.1 - 2023-05-12

- Upgrades to WordPress 6.2
- Post Type Select Filter by @nikkifurls

## v0.1.0 - 2022

- Initial release
