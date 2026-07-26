=== Publish Gate – Publishing Checklist & Content Rules ===
Contributors: mirmpro
Tags: gutenberg, publishing, quality-control, editorial, pre-flight
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The pre-publish checklist you actually control. Lock the Publish button until authors meet your exact content standards, from required blocks to word counts.

== Description ==

Stop fixing the same editorial mistakes over and over. **Publish Gate** adds a native pre-flight checklist to the Block Editor and physically disables the Publish button until the post passes your rules.

Whether you need to enforce featured images, ban placeholder text, or ensure specific custom fields are filled, your rules are evaluated live as the author types.

= What it does =

* **Native Editor Lock** — The Publish button stays disabled until all active checks turn green. No popups, no separate screens.
* **Jump to Block** — Click any failed rule (like "Missing Alt Text") in the sidebar to instantly scroll to and highlight the broken block.
* **Server-Side Guard** — Even if someone tries to bypass the browser, the backend intercepts the publish request and forces the post back to draft.
* **Role Overrides** — Admins and Editors can bypass the lock by providing a written reason. Authors cannot.

= The 13 Built-in Rules =

Turn these on or off, and tweak their thresholds, directly from Settings > Publish Gate.

1. **Featured Image** — The post must have a featured image set.
2. **Image Alt Text** — Every single image block must have alt text.
3. **Minimum Word Count** — Body text must hit your specified minimum (e.g., 300 words).
4. **Maximum Word Count** — Body text cannot exceed your hard limit.
5. **No Placeholder Text** — Rejects "Lorem Ipsum" and generic test phrases.
6. **Title Required** — The post title cannot be empty.
7. **Excerpt Required** — A manual excerpt must be written.
8. **Require Text** — The content must contain a specific phrase or regex pattern.
9. **Ban Text** — The content cannot contain specific banned words.
10. **Minimum Categories** — Ensure posts are categorized before publishing.
11. **Minimum Tags** — Require a baseline number of tags.
12. **Required Meta Field** — Ensure specific custom fields (like SEO titles) are filled.
13. **Required Blocks** — Force the inclusion of specific blocks (e.g., `core/heading`).

== Installation ==

1. Upload the `publish-gate` folder to `/wp-content/plugins/`.
2. Activate it through the 'Plugins' menu.
3. Go to **Settings > Publish Gate** to choose and configure your rules.
4. Open the Block Editor. You will see the new Publish Gate icon in the top right.

== Frequently Asked Questions ==

= Does this work with the Classic Editor? =

No. Publish Gate is built strictly for the Block Editor (Gutenberg). The backend guard will still block empty posts, but the checklist UI requires Gutenberg.

= Will this slow down the editor? =

No. Validation runs client-side using highly optimized checks. It takes milliseconds, even on massive posts.

= What if a rule is broken but the post needs to go live? =

Administrators and Editors can click "Override" in the sidebar. They must type a reason to unlock the Publish button. Authors and Contributors do not have this ability.

== Changelog ==

= 1.0.0 =
* Initial release.
* 13 built-in rules with customizable settings.
* Real-time sidebar integration for Gutenberg.
* Jump to Block utility.
* Role-based override flow.
* Double-layer validation (JavaScript frontend + PHP backend).
* Settings page under Settings > Publish Gate.

== Upgrade Notice ==

= 1.0.0 =
First public release. Install and activate to start enforcing your editorial standards.

== Screenshots ==

1. Publish Gate sidebar showing the live pre-publish checklist in the Block Editor.
2. Failed checks featuring the "Jump to Block" button and visual highlight.
3. Override dialog flow allowing Administrators to bypass rules with a reason.
4. The main Settings page where rules can be enabled and configured.
