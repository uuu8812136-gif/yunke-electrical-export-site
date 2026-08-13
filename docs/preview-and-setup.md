# Preview and Setup

## What is included

This workspace packages three handoff-friendly layers:

- a portable WordPress implementation under `wordpress/`
- structured bilingual copy source files under `content/site-copy/`
- placeholder visual assets and a static preview entry point for quick review

## Local placeholder asset rebuild

The asset pipeline stays inside the owned write scope and does not touch the theme or plugin code.

1. Render or refresh brochure pages:
   `py .tools/scripts/render_pdf_pages.py`
2. Rebuild the selected placeholder asset set and manifest:
   `py .tools/scripts/build_placeholder_assets.py`

Both scripts use the bundled PyMuPDF package under `.tools/python`.

## Static preview

Open the top-level preview file directly in a browser:

- [preview-index.html](/F:/云科电气/preview-index.html)

This page is intentionally simple. It helps reviewers inspect:

- the current English and Chinese content structure
- the named placeholder asset set
- the WordPress package location and live dependencies

## WordPress runtime dependency

The actual theme and plugin still require an external WordPress runtime to function. That means a real preview of the WordPress site still depends on:

- WordPress core
- PHP
- MySQL or MariaDB
- media uploads and final contact data

## Suggested next external inputs

- final product, factory, and project photography
- confirmed sales contacts and department names
- approved bilingual copy edits
- hosting/runtime details for a live WordPress preview
