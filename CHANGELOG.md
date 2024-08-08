# Changelog

All notable changes to `Feed Consumer` will be documented in this file.

## Unreleased

### Changed

- Imported images with the `Post_Loader` are properly attached to the parent post.
- RSS Transformer will use `content:encoded` first and then fallback to `description` if `content:encoded` is not available.

## 1.0.0 - 2024-08-05

- Stable release.
- Bumps minimum requirement to PHP 8.1.

## 0.1.2 - 2023-05-16

- Require `mantle-framework/http-client`.

## 0.1.1 - 2023-05-12

- Upgrades to WordPress 6.2
- Post Type Select Filter by @nikkifurls

## 0.1.0 - 2022

- Initial release
