=== LindaWP Download Manager ===
Contributors: lindawp
Tags: download, secure download, file protection, one-time link, digital downloads
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect digital file downloads with unique, temporary one-time-use links that expire immediately after download.

== Description ==

LindaWP Download Manager lets you securely distribute digital files through one-time-use download links. Each link is cryptographically generated, expires after 1 hour if unused, and is invalidated immediately after the file is downloaded — preventing sharing or hotlinking.

**Features:**

* Upload and manage files from a simple admin interface
* Cryptographically secure token generation for every download
* One-time-use links — expire immediately after download
* Automatic token expiry after 1 hour if unused
* Optional email capture before download
* Email list tracking with CSV export
* Download statistics — total downloads, top files, recent activity
* IP address and user agent logging
* .htaccess protection prevents direct file access
* Easy shortcode integration

**Shortcodes:**

`[secure_download id="123" text="Download Now"]` — renders a styled download button.

`[secure_download_url id="123"]` — returns just the URL, for use in custom HTML or page builders.

== Installation ==

1. Upload the `digital-download-link-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Linda Downloads** in the admin sidebar
4. Upload your files and copy the shortcode into any page or post

== Frequently Asked Questions ==

= Can a download link be used more than once? =
No. Each link is single-use. Once downloaded, the token is invalidated.

= How long are links valid? =
Links expire after 1 hour if not used. After download they expire immediately.

= Can I require an email before the file is downloaded? =
Yes. Enable "Require Email" per file from the admin panel. A secure link will be emailed to the user.

= What file types are supported? =
PDF, ZIP, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, GIF, MP3, MP4, AVI, MOV, TXT, CSV, EPUB, MOBI, PSD, AI, SVG. You can extend this list with the `ddlm_allowed_file_types` filter.

= Can I increase the maximum file size? =
Yes, use the `ddlm_max_file_size` filter. Default is 100MB.

== Screenshots ==

1. Admin file manager — upload, manage and copy shortcodes
2. Download statistics dashboard
3. Email list with CSV export

== Changelog ==

= 1.0.0 =
* Initial release
* File upload and management
* Secure one-time download token system
* Download statistics tracking
* Optional email capture before download
* Shortcode integration

== Upgrade Notice ==

= 1.0.0 =
Initial release.
