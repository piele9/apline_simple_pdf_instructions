# Changelog

All notable changes to **APLINE Simple PDF Instructions for
PrestaShop 9** will be documented in this file. Format based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] – 2026-05-25

Initial public release.

### Added
- Configurable **PDF-download buttons** on the product page for
  PrestaShop **9.0.x**, each mapped to a product-attachment slot
  (1st, 2nd, … 10th attachment). Reads native
  `ps_product_attachment` / `ps_attachment` / `ps_attachment_lang` —
  the module does not manage PDF files itself.
- **Graceful skip**: a button without a matching attachment on the
  current product is silently omitted (no broken links, no empty
  blocks). Configure buttons globally once; the same set works across
  the whole catalogue.
- Configurable display location: product reassurance area, left or
  right column, product footer — or anywhere via
  `{widget name='apline_simple_pdf_instructions'}`.
- Per-button **icon image** (JPG/PNG/WEBP upload) **or** **icon
  entity** (unicode hex / HTML entity), with position none / left /
  right / both.
- Per-button **label source** (`label_source` ENUM):
  - *Custom text* — `own_string`, optionally with the product name
    appended
  - *Attachment file name* — `ps_attachment_lang.name` (per-language
    title from Catalog → Files), with fallback to the storage file
    name without extension
- Per-button **background color** (hex picker, default `#dc3545`),
  drag & drop ordering, enable/disable per button.
- **Remove current image** switch on the edit form — clear an icon
  without deleting the whole button.
- Strict English-only validation: slot range 1–10, hex color, icon
  entity whitelist (unicode hex / HTML entity), 255-char limit
  (rejected, never silently truncated), label-source-aware label
  XOR check (own_string OR append_product_name required only when
  label_source = 'own').
- Hardened icon upload: JPG / PNG / WEBP only, real MIME inspection
  (not just the extension), 2 MB size cap → blocks disguised
  executables.
- Crash-safe hooks and `WidgetInterface` rendering (`try/catch` →
  empty block + log, never a 500).
- Failed install rolls back to a clean state via `$this->uninstall()`;
  uninstall is idempotent (`DROP TABLE IF EXISTS`, guarded Tab
  cleanup, `@unlink` on uploads).
- *Back to configuration* breadcrumb button from the buttons
  management list.
- APLINE attribution block on the configuration page **and** under
  the buttons list, with a "Like this module?" call to action linking
  to https://apline.pl.
- Custom Attribution License v1.0 ([LICENSE.md](LICENSE.md)).

### Naming convention (SIMPLE family)
This module is part of the **APLINE SIMPLE** family of PrestaShop
modules. The convention is:

- folder / main `.php` file / PHP class / `$this->name`:
  `apline_simple_<feature>` (all four MUST match — otherwise the
  back-office upload rejects the zip)
- DB table: `<abbrev>_<entity>` (here: `aspd_button`)
- Configuration keys: `<ABBREV>_*` (here: `ASPD_HOOK`)
- Translation domain: `Modules.Aplinesimple<feature>.Admin`
  (underscores stripped, ucfirst — here:
  `Modules.Aplinesimplepdfinstructions.Admin`)
- ObjectModel class **must be ≤ 32 characters** (PrestaShop's
  `ps_log.object_type` is VARCHAR(32) and `get_class($this)` is
  written there on every ObjectModel validation error) — this module
  uses `AplineSimplePdfInstructionsBtn` (30) rather than
  `AplineSimplePdfInstructionsButton` (33).
- Repository: `https://github.com/piele9/apline_simple_<feature>`
