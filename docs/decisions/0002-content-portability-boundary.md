# ADR-0002: Content portability boundary

| Field | Value |
|-------|-------|
| **Status** | Proposed |
| **Date** | 2026-05-11 |
| **Author** | VoxelSwarm core |
| **Related** | `template/voxelsite/v1.28.0/_studio/api/endpoints/export.php`, `views/landing.php` |

> **Historical note (2026-06-13):** An earlier draft of this ADR framed the export boundary differently. VoxelSite is now free and open source under [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.html), so the export boundary stands purely as a **code-access and portability** question: an export is the rendered website (standard HTML/CSS/PHP), not the AI builder, the same way Figma exports designs rather than Figma itself. The reasoning below reflects that framing; the decision is unchanged.

## Context

VoxelSite instances generate websites that users interact with through the Studio UI. The generated output -- pages, CSS, JS, assets, form handlers -- coexists in the same directory tree as the platform engine (`_studio/`), which contains the AI prompt engine, patch executor, revision manager, file manager, design manager, and all API endpoints.

When a user wants to take their site elsewhere (different host, different platform, or just a local backup), the question is: what do they get?

The landing page currently promises:

> "Every site is standard HTML, CSS, PHP, and JavaScript. Download it. Move it to any host. No proprietary format. No lock-in, ever."

Meanwhile, operators provisioning VoxelSite instances for clients have a legitimate concern: if users can download the entire codebase, they can stand up their own copy of the full builder and bypass the operator who provisioned them.

The question: where is the boundary between "your content" and "our platform"?

## Current decision

**Export content, strip the engine.** The existing [`export.php`](../../template/voxelsite/v1.28.0/_studio/api/endpoints/export.php) endpoint already implements this boundary. It builds a ZIP containing:

**Included (user content):**
- Published PHP pages from the document root (lines 82-89)
- Partials from `_partials/` (lines 100-106)
- Referenced assets: CSS, JS, images, fonts, library files (lines 130-192)
- Remote images fetched and localized (lines 194-230)
- Standalone form handler (`submit-standalone.php`) -- self-contained, no dependencies (lines 323-340)
- `robots.txt` (stripped of `_studio` references), `llms.txt`, `mcp.php` (lines 342-376)
- `.htaccess` with basic rewrite rules (lines 378-382)

**Excluded (platform code):**
- `_studio.php` -- the Studio entry point (line 85, explicit exclusion)
- The entire `_studio/` directory -- engine, API, UI, tests, snapshots, demo data
- `_data/` -- SQLite database, settings, revision history
- `vendor/` -- Composer dependencies
- `i18n/` -- Studio localization files
- `LocalValetDriver.php`, `test_list.php` (line 85)
- Agentic action bar assets (lines 156-161)

The export also supports two formats:
- **PHP** -- working PHP site with partials, form handler, and `.htaccess`. Runs on any shared host.
- **HTML** -- static HTML with all PHP rendered to `.html`, links rewritten, paths made relative. Works from a desktop or static CDN.

Both formats produce a site that works independently but cannot self-edit. The AI builder, revision system, and Studio UI are gone.

## Why this boundary exists

The boundary is drawn between "can render and serve your site" and "can build and edit your site." The reasoning:

1. **The marketing promise is about the output, not the tool.** "Your files, your rules" means the pages, styles, and assets you created are standard web technologies you can host anywhere. It does not mean you get a copy of the authoring tool. This is consistent with how every other builder works -- Figma lets you export your designs, not Figma itself.

2. **The export is the output, not the builder.** Including `_studio/` would hand every end user a fully functional copy of the builder, not just their site. The boundary keeps the builder with the operator who runs it and gives end users a portable, self-contained site, the same code-access reasoning as point 1.

3. **The export is self-contained by design.** The standalone form handler ([`submit-standalone.php`](../../template/voxelsite/v1.28.0/_studio/static/submit-standalone.php)) exists specifically so exported sites don't need the Studio's database or vendor directory. Forms keep working after export. This is a deliberate investment in making the export actually portable, not a stripped-down version that breaks.

4. **Content isn't trapped.** Users can re-export at any time. The PHP export preserves the full page structure with partials, so a developer can pick it up and extend it. The HTML export works from a thumb drive. Neither format is proprietary.

## The tension

The landing page copy is slightly misleading. "Download it. Move it to any host." is true. "No lock-in, ever" is true -- you can leave with your content. But the implication is that you get everything, when you actually get the output minus the builder.

This is worth being honest about, both in marketing and in documentation. The export is genuinely useful and genuinely portable. It's not the full platform.

## Alternatives considered

### A: Full instance export (everything)

Include `_studio/`, `_data/`, `vendor/`, `i18n/` -- the complete installation. User gets a working VoxelSite they can host independently.

- **Upside:** Maximum portability. No ambiguity about what "your files" means.
- **Downside:** Every export is a full copy of the builder. End users could leave with the entire platform, removing the code-access boundary the operator provisions behind.
- **Verdict:** Removes the operator's code-access boundary entirely. Would only fit a hosted-only model where the software is never distributed.

### B: Content-only export with no PHP (current HTML mode)

Static HTML only. No PHP, no form handler, no dynamic behavior. Pure files.

- **Upside:** Nothing executable is distributed, so there is no code-access concern at all.
- **Downside:** Sites with forms, dynamic content, or server-side logic stop working. The "no lock-in" promise rings hollow if the export is functionally degraded.
- **Verdict:** Too restrictive. Already offered as an option (`format: html`), but shouldn't be the only option.

### C: Current approach (content + standalone handler, strip engine)

This is what's implemented. Working PHP site with forms, minus the builder.

- **Upside:** Export is genuinely useful. Users can host it, extend it, modify it. Forms work. SEO works. No lock-in on the content layer.
- **Downside:** Users can't self-edit via AI after export. Some may feel this is "lock-in" even though it's "lock-in to the editing tool, not the output."
- **Verdict:** Best balance. Chosen.

## What would change the decision

- **A hosted-only model** where VoxelSite is never distributed as a ZIP (pure SaaS). Full exports would be moot, since nothing is self-hosted to begin with.
- **An open-core split** that separates the rendering engine from the AI editing layer. The export could then include the rendering engine and keep only the editing layer with the operator.
- **Significant user demand** for self-hosted editing after export, backed by a clear maintenance model. This would be a distribution change, not an architecture change.

## Related reading

- [Export endpoint](../../template/voxelsite/v1.28.0/_studio/api/endpoints/export.php) -- the implementation
- [Standalone form handler](../../template/voxelsite/v1.28.0/_studio/static/submit-standalone.php) -- self-contained submission handler for exported sites
- [Landing page portability claim](../../views/landing.php) (line 631)
- [ADR-0001](0001-cpanel-subdomain-vs-full-account.md) -- related code-access reasoning
