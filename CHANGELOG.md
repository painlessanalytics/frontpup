# FrontPup Changelog

This is the changelog for the FrontPup plugin. Version changes should be added to this document. Each release starts with a H2 (two hash tags) with the Version number. The top most H2 is the latest version. Developers would scroll to the bottom of the document to see the very first version.

All notable changes to this project will be documented in this file.

Each version H2 section should start with the version number in hard brackets followed by a dash then the release date in European format, *YYYY-MM-DD*. Proceeding the release date there should be a brief description followed by one of changes. Each list item should start with a minus (-) character. Tabs may be used to indent the lists.

The format a simplified version of the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this plugin adheres loosely to [Semantic Versioning](https://semver.org/).

## [Unreleased]

TBD

## [1.3.1] - 2026-03-06

Clear cache in admin bar waiting switched from gif to svg, much more lightweight.

- Replaced large gif with svg image.
- Created CSS animation to show that the request is waiting on a response with a spinner style.
- Updated JavaScript to use the new CSS classes.
- No longer need the loading icon gif.

## [1.2] - 2026-02-27

Clear cache in admin bar enhanced

- New: Non-intrusive admin bar clear cache functionality.
- JavaScript logic rewritten to not rely on jQuery.
- CSS created with loading.gif embedded directly into css for performance.
- Errors and success message simplified for display in admin bar menu.
- Icon source: [Loading icon.gif](https://commons.wikimedia.org/wiki/File:YouTube_loading_symbol_3_%28transparent%29.gif)
- Removed no longer used code in the frontpup-admin.class.php related to the clear cache functionality. It is now fully handled in the frontpup-admin-bar.class.php file.

## [1.1] - 2026-01-30

Clear cache functionality added.

- Added welcome page for the wp-admin
- Added clear cache settings page
- Reorganized admin class, new base class for future settings pages
- Moved views to subfolder of admin folder
- Added FrontPup admin bar menu bar option with "Clear CloudFront Cache" in sub menu (Made it a sub menu so you have to click twice to avoid accidental cache clearing)
- Ajax code for clearing cache created. For now only users who can manage settings can clear the cache (to be customizable in future versions)

## [1.0] - 2026-01-08

First version of this plugin

- Initial release with CloudFront optimization features
