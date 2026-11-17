=== Banglish Slug ===
Contributors: iamsaifulbd
Tags: bangla, bengali, slug, permalink, transliteration
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turns Bangla titles into clean Banglish URL slugs. Works offline. Bulk tool fixes old Bangla slugs with automatic redirects.

== Description ==

WordPress keeps Bangla titles as long percent-encoded slugs that look broken when shared. Banglish Slug writes them in English letters instead:

* আমার সোনার বাংলা → amar-sonar-bangla
* ছুটির নিয়ম → chhutir-niyom
* ঢাকা ২০২৬ → dhaka-2026

Features:

* Offline, rule-based transliteration. No API key and no external requests.
* Posts, pages, custom post types, categories, tags and custom taxonomies (each can be switched off).
* Tools → Banglish Slug lists posts that still have Bangla slugs and converts the selected ones. Old URLs of published posts redirect to the new ones.
* A slug you type by hand in English is kept.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install the ZIP from Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Optional: open Tools → Banglish Slug to convert old slugs. Take a backup first.

== Frequently Asked Questions ==

= Will my old links break? =

When a published post's slug changes, WordPress stores the old slug and redirects visitors to the new URL.

= Is the spelling always perfect? =

No. Bangla does not always mark where the inherent vowel is dropped, so some words get an extra "o". You can edit any slug by hand.

== Changelog ==

= 1.0.0 =
* First release.
