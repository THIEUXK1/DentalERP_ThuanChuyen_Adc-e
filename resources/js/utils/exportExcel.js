/**
 * Posts the rows a list screen is currently showing to /export/table and downloads the
 * styled .xlsx that comes back.
 *
 * These screens filter, search and sort in the browser, so the visible rows are the only
 * accurate description of what the user wants exported — the server just formats them.
 *
 * @param {Object}   opts
 * @param {string}   opts.heading   Big title in the sheet banner.
 * @param {string}  [opts.subtitle] Second banner line (branch, date range, …).
 * @param {string}  [opts.meta]     Third banner line; the export time is appended server-side.
 * @param {string}  [opts.filename] Slugified into the download name.
 * @param {string}  [opts.sheetTitle] Worksheet tab name (max 31 chars).
 * @param {string}  [opts.accent]   ARGB accent, e.g. 'FF0F766E'.
 * @param {Array}    opts.columns   [{ key, label, type, width, total, bold, colors }]
 *                                  type: text | code | date | number | money | status
 *                                  colors: { '<cell text>': 'green' } for status chips.
 * @param {Array}    opts.rows      Plain row objects keyed by the column keys.
 * @param {Array}   [opts.bands]    [{ label, first, last, color }] merged header bands.
 */
export async function exportTableToExcel(opts) {
    const response = await window.axios.post(
        route('export.table'),
        {
            heading: opts.heading,
            subtitle: opts.subtitle ?? '',
            meta: opts.meta ?? '',
            filename: opts.filename ?? '',
            sheet_title: opts.sheetTitle ?? 'Dữ liệu',
            accent: opts.accent ?? 'FF312E81',
            columns: opts.columns,
            rows: opts.rows,
            bands: opts.bands ?? [],
        },
        { responseType: 'blob' },
    );

    // Prefer the server's filename (it carries the export date); fall back to the caller's.
    const disposition = response.headers['content-disposition'] ?? '';
    const match = disposition.match(/filename="?([^";]+)"?/i);
    const name = match ? match[1] : `${opts.filename || 'bao-cao'}.xlsx`;

    const url = URL.createObjectURL(response.data);
    const a = Object.assign(document.createElement('a'), { href: url, download: name });
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
