# Copilot Instructions

- **App purpose:** Single-page PHP tool that renders an order color/size matrix from Oracle data and lets users apply a multiplier to generate a second sheet. Main entry point is [index.php](index.php).
- **Data source:** Oracle instance accessed via `oci_connect`; credentials and host are defined at the top of [index.php](index.php). Keep bind variables intact to avoid SQL injection and to let Oracle reuse cursors. Default date filter auto-limits queries to the last 180 days when no range is provided.
- **Request flow (index.php):** POST search → build dynamic WHERE clause for `JOB_NO_MST`, `PO_BREAK_DOWN_ID`, `STYLE_REF_NO`, optional date range → `oci_parse`/`oci_bind_by_name`/`oci_execute` → fetch rows → pivot into `$matrix[$size][$color]` while capturing `$unique_sizes/$unique_colors` for ordering → render header metadata and matrix.
- **Fallback behavior:** If OCI8 is not available, the page shows a warning and generates dummy size/color data; use this for UI testing when Oracle drivers are missing. Preserve this branch when modifying bootstrap or JS logic.
- **UI stack:** Bootstrap 5.3 CDN + jQuery 3.6 (see [index.php](index.php)). The multiplier button clones the rendered sheet, multiplies `data-val` values in `.qty-cell`, recalculates row/column/grand totals, and injects the result into `#dynamicContainer`. Update both the base table and this clone logic when changing table structure.
- **Printing:** Print styles hide the search UI (`.no-print`) and strip borders/padding. Any new UI controls should respect the print view if they should be excluded.
- **Error handling:** Connection failures set `$error_msg` and render a Bootstrap alert. Keep user-facing errors non-fatal so the UI still loads.
- **HTML form inputs:** Names: `job_no`, `po_id`, `style_ref`, `date_from`, `date_to`. Server reads them directly from `$_POST`; keep names stable when altering the form or JS.
- **Ordering:** Color and size display order come from `COLOR_ORDER` and `SIZE_ORDER` values returned by the query. Do not sort alphabetically unless explicitly required.
- **Totals:** `$rowTotal`/`$colTotals`/`$grandTotal` are computed server-side for the base table and recalculated client-side after multiplication. Maintain both server and client calculations when adding columns.
- **Legacy code:** [old/index2.php](old/index2.php) uses a `Database` helper from [old/db_config.php](old/db_config.php) and includes Excel/print helpers; [old/download.php](old/download.php) outputs CSV from GET parameters. Treat these as references, not active routes, unless explicitly revived.
- **Testing tips:** Without Oracle, disable/omit the OCI extension to trigger the dummy branch. With Oracle available, ensure the OCI8 extension is loaded and connectivity to the configured host works; no build step is needed—serve via PHP’s built-in server or Apache.
- **Secrets:** Credentials are currently embedded in source for dev convenience; avoid committing alternative secrets or rotating them without coordination.
- **When updating queries:** Keep the base SELECT join structure (tables under `NFLERPLIVE`) and the `w.IS_DELETED = 0` guard. Add filters with binds; avoid string concatenation.
- **When adjusting UI:** Keep `#originalOrderSheet` and `#dynamicContainer` IDs; JS relies on them for cloning and insertion. Preserve the `data-val` attributes on quantity cells.
- **Extending exports:** For CSV/Excel needs, reuse the matrix/total arrays already built server-side or the DOM table; see the CSV pattern in [old/download.php](old/download.php).
