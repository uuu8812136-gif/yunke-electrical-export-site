# YKE WordPress Site Package

This workspace contains a portable WordPress package under [wordpress](/F:/云科电气/wordpress) with:

- `wp-content/themes/yke-global`: custom FSE block theme
- `wp-content/plugins/yke-site-core`: custom site plugin for case studies, settings, and inquiry shortcodes
- `content/site-copy`: structured bilingual content source files for English and Chinese entry
- `content/assets-placeholders`: brochure-derived placeholder visuals, including selected named assets and a manifest

Worker 3 additions stay outside the theme and plugin code:

- static preview entry point: [preview-index.html](/F:/云科电气/preview-index.html)
- setup notes: [docs/preview-and-setup.md](/F:/云科电气/docs/preview-and-setup.md)
- asset helper scripts: `.tools/scripts/render_pdf_pages.py` and `.tools/scripts/build_placeholder_assets.py`

The actual WordPress site still requires an external WordPress runtime for live theme and plugin preview.
