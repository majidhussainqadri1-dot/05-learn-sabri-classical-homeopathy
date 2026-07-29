=== Learn Sabri Classical Homeopathy ===
Contributors: sabrihomeopathy
Tags: classical homeopathy, learning, books, lessons, progress
Requires at least: 6.1
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Governed public books, structured lessons, private progress, bookmarks, and knowledge checks for the Sabri Social Homeopathy Platform.

== Description ==

File 05 provides the hardened learning foundation for Learn Sabri Classical Homeopathy.

Core controls:
* File 00 is the mandatory and sole membership/doctor-verification authority.
* File 01 owns the central Learn page.
* File 20 owns the global application shell and navigation.
* Custom post capabilities isolate books and lessons from generic WordPress post editors.
* Founder and learning administrators may publish directly; currently verified doctors submit for independent review.
* Moderation prohibits self-review and uses explicit state transitions plus optimistic concurrency control.
* Patient Case Learning requires a versioned consent record, source, evidence reference, scope, and withdrawal status.
* Private progress and bookmarks are filtered to currently published lessons.
* Image upload checks MIME, dimensions, pixel count, embedded active-content signatures, and a security-scan filter.
* Privacy export, erasure, anonymization, legal-hold handling, and guarded uninstall are included.
* Lesson catalog search, filters, popularity, and pagination are included.

== Dependencies ==

The plugin stops safely unless these active contracts are available:
1. File 00 — Sabri Membership Core 1.0.1 or later.
2. File 01 — Sabri Platform Foundation.
3. File 20 — Sabri Unified Application Shell 1.0.0 or later.

== Installation ==

1. Back up the database and files.
2. Keep Files 00, 01, and 20 active and healthy.
3. Upload the release ZIP through WordPress Admin > Plugins > Add New > Upload Plugin.
4. Activate Learn Sabri Classical Homeopathy.
5. Run the complete staging acceptance protocol before production.

== Privacy and retention ==

Learning progress and bookmarks may be erased. Unpublished authored lessons are removed during a valid erasure request unless a legal hold applies. Published lessons may be retained for editorial integrity with personal attribution removed. Destructive uninstall requires both the SLC_PURGE_ON_UNINSTALL constant and the slc_allow_destructive_uninstall option.

== Changelog ==

= 1.0.0 =
* Hardened architecture, permissions, dependencies, moderation, consent, privacy, database lifecycle, shell integration, catalog pagination, upload security, safe DOM rendering, reproducible packaging, and corrective CI.

= 0.1.0 =
* Original modular baseline preserved on the immutable baseline branch.
