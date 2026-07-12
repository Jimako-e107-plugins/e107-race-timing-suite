/*
 * racereports - DataTables init (racereports-owned copy; NOT the legacy
 * timetracker/datatables/init.js).
 *
 * Targets the stable racereports table hooks (#report_stu, #report_aktualne) and
 * uses the modern DataTables API. Minimal by design: no paging, no info line, no
 * Select extension - just sortable column headers (plus, for the full matrix, the
 * basic Search box). Initial order is the rank column (col 0) ascending.
 *
 * Time-column numeric sort: time <td> cells carry a native data-order="<raw
 * elapsed seconds>" attribute (emitted server-side). DataTables reads data-order as
 * the sort key while DISPLAYING the formatted HH:MM[:SS] cell text, so the row
 * order is stable regardless of the displayed precision (it sorts by seconds, never
 * by the formatted string). No custom sort plugin needed.
 *
 *   #report_stu       : finishers-only list - NO search box (searching:false).
 *   #report_aktualne  : the FULL per-race matrix - basic Search box ON
 *                       (searching:true), per the report screenshot. NO
 *                       SearchBuilder, no CDN - the bs5 build owned by racereports.
 *
 * Guarded on $.fn.DataTable so a missing/blocked library degrades to a plain
 * static table instead of a JS error.
 */
$(function () {
  if (!$.fn.DataTable) {
    return;
  }

  if ($('.table').length) {
    $(".table").DataTable({
        paging: false,
        searching: true,
        info: false,
        order: [[0, "asc"]],
    });
  }

 
});
