=== Media Sync Delete Multilanguage for WPML ===
Contributors: RELIEF Creation
Tags: wpml, media, images, synchronization, translation, multilanguage
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronizes media deletion across WPML translations automatically for multilingual WordPress sites.

== Description ==

Media Sync Delete Multilanguage for WPML ensures that when you delete media in WordPress, all its translations in other languages are automatically deleted as well. This helps maintain consistency across your multilingual website and prevents orphaned translated media files.

Features:

* Automatic synchronization of media deletions across all languages
* Works seamlessly with WPML Media Translation
* Prevents orphaned translated media files
* Enhanced logging system with user interface
* Version tracking and updates management
* Clean and efficient code
* Maintains media library consistency across languages

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/media-sync-delete-multilanguage` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. WPML must be installed and activated for this plugin to work

== Frequently Asked Questions ==

= Does this plugin require WPML? =

Yes, this plugin requires WPML to be installed and activated.

= Will this plugin delete all language versions of media files? =

Yes, when you delete a media file, all its translations will be automatically deleted.

= Is it safe to use in production? =

Yes, the plugin includes safety checks to prevent recursive deletions and maintains data integrity.

= Does it work with all types of media? =

The plugin is specifically designed to work with images in the media library that have translations in WPML.

= Where can I see the deletion logs? =

You can find the deletion logs in the WordPress admin under Tools > Media Sync Logs.

== Changelog ==

= 1.0.1 =
* Added version tracking system
* Enhanced logging interface with empty state message
* Added plugin version display in logs page
* Improved error handling and notifications
* General code optimization and cleanup

= 1.0.0 =
* Initial release
* Added automatic synchronization of media deletions
* Added WPML dependency check
* Added logging support
* Implemented safety checks for recursive deletions

== Upgrade Notice ==

= 1.0.1 =
This update adds version tracking and enhances the logging interface. Update recommended for all users.

= 1.0.0 =
Initial release of Media Sync Delete Multilanguage for WPML