# racers - developer notes

Registered competitors (pretekári) for the race-timing suite: the admin racer
CRUD plus the public **start list** (štartovacia listina). These notes live here
(not as XML comments in `plugin.xml`) by project convention.

## Two distinct "registrations" - do not confuse them

There are two unrelated registration concepts in the suite. They are decoupled;
do not merge their terminology.

- **On-site registration** (`racers/registracia.php`) - competitors who arrive
  at the event **without** prior registration (manual / walk-up entry). This is
  the `racers` plugin's own entry path and is gated by the "allow manual entry of
  participants" setting (`manualinput` pref). English term used here: **"on-site
  registration"**.
- **Pre-event sign-up** (`racereg` plugin) - a *different* plugin handling the
  pre-event sign-up + payment flow. Not part of `racers`.

## `racer_active` - manual bib-collection flag (NOT a start time, NOT DNS)

`racer_active` (admin column label **"Nastúpil"**, schema `racer_active int(1)`) is
a **MANUAL flag the admin sets when a racer collects their start number before the
start** (`prevzal štartové číslo`). Keep its meaning precise - it is a frequent
source of confusion:

- **It is NOT auto-derived from having a start time.** A racer can have a start
  time at the `start` checkpoint (the racetiming engine returns a non-null
  `race_clock::timeOfDay(number, 'start')`) while `racer_active` is still `0` -
  e.g. bibs were handed out in a hurry and nobody marked them. **This is a VALID
  state, not a data bug.** Do NOT "fix" it by inferring `racer_active` from a start
  crossing - the flag stays a deliberate, hand-set marker.
- **It is NOT the same as DNS.** "DNS" (did not start) is a *results-level* status
  with a different purpose (the finish/start/online reports use it to show a racer
  did not start). `racer_active` is the bib-collection flag. Some reports *trigger*
  their DNS bucket from `racer_active = false` (an un-marked racer is treated as DNS
  in the results - see `racereports/NOTES.md`), but that is a report-level reading
  of the flag, NOT its definition. Do not equate the two.
- **Start-list coloring is a "was the bib-collection marked?" check, NOT a "has a
  start time?" check.** `startlist.php` applies the per-category row colour ONLY when
  `racer_active` is set (`startlist.php:149`); an un-marked racer renders as a plain
  (uncolored) `<tr>`. This is correct and intentional: an uncolored row lets the
  admin spot at a glance who collected their bib and who did not - independent of
  whether a start time exists. The start-time CELL read is NOT gated on `racer_active`
  (`startlist.php:135`) - it is read whenever the racer HAS a `start` crossing
  (`timeOfDay('start') !== null`), regardless of the flag. So an **unmarked-but-started**
  racer (started, `racer_active = 0`) renders as an **uncolored row WITH a start time** -
  a visual "forgot to mark the bib" check: a row that has a time but no colour is one the
  admin still has to mark. The colour gate is the bib-mark signal described here; the time
  cell is the start-crossing signal.
- **Start generation gates on it.** The RFID start-list generator
  (`racetiming/admin/admin_generujstart.php`) only emits a start for a racer when
  `!empty($racer['racer_active'])`. So RFID users MUST mark `racer_active` (bib
  collected) or no start is generated for that racer.

## Public start list (štartovacia listina)

`startlist.php` renders **all** registered competitors for a track (`race_sef`) +
category (`race_category_sef`), *before* the start. It is reached via the
`startlist` route in `e_url.php` (SEF `racers/{race_sef}/{race_category_sef}/`)
and surfaced through `e_sitelink.php` (`racers_start_list`).

This was ported from the deleted `racetrack/page_start.php`. It uses racers' own
`getRacerName()` (`includes/racers.php`), **not** the old
`racetrack/functions.php` copies. Race/category ids are int-cast before the
`IN()` clauses; the racer number stays a **string**; every echoed racer field
goes through `$tp->toHTML()`/`toAttribute()`.

### Start-time column - now on the racetiming engine (decoupled from timetracker)

The list keeps the "Čas štartu" (start time) column on purpose: it shows
**everyone** registered, while the time cell is filled for every racer who has a
`start` crossing (`startlist.php:135`) - decoupled from `racer_active`, so an
**empty cell flags who has no start crossing yet** (truly not started). An
unmarked-but-started racer therefore shows a time on an uncolored row (the colour
gate at `:149` stays on `racer_active`), surfacing bibs the admin forgot to mark.
(`racer_active` is the manual bib-collection flag, NOT derived from the start time
and NOT a DNS flag - see the `racer_active` section above.)

The start time is read from the timing layer via the **racetiming engine**
(`race_clock::timeOfDay()` at the `race_time` point `start`, formatted with
`race_clock::formatTimeOfDay()`). The read is **guarded** by
`e107::isInstalled('racetiming')`:

- racetiming installed -> `(new race_clock())->build()` once per page, then the
  column is filled (`formatTimeOfDay($epoch, 3)`);
- racetiming absent -> the column is left empty (racer not started; no error).

The previously deliberate, temporary **timetracker** coupling
(`timetrackerStart::get_starttime_from_point()` + `ISO8601ToMicrotime()` /
`microtimeToSeconds()`, guarded by `isInstalled('timetracker')`) is now **done**:
startlist no longer touches timetracker at all. `<dependencies>` in `plugin.xml`
stays `raceevent` + `racetrack` only - the engine read is `isInstalled`-guarded,
not a hard dependency.

**Display is unchanged.** The legacy `microtimeToSeconds` - despite its name -
emitted a wall-clock **TIME-OF-DAY** string (`date('H:i:s', floor(epoch))` + an
rtrim'd `.mmm` / `.000` suffix), NOT a count of seconds. `formatTimeOfDay($e, 3)`
reproduces that string byte-for-byte (it ROUNDS the ms field, matching the legacy
start-time formatter, which is why `.700` shows `.7` and not the truncated
`.699`). Parity is asserted in `racereports/parity/` (engine self-test +
DB-backed comparator) - see racetiming/NOTES.md.

startlist stays a **racer-roster** page: it still iterates the `racer` table and
renders every registered competitor; only the start-time CELL source changed.

### Security (start-time read)

The engine's crossing read is parametrised (no raw concatenation): the legacy
`get_starttime_from_point()` built `WHERE TagID=`/`race_time_racer_number='".$num`
by hand (SQL-injection risk); `race_clock` reads `race_time` with the racer
number passed as a string through the engine's split map, never interpolated. The
racer number is a string and must never be cast to `(int)`.

## B3 cleared - race_categories_menu.php de-coupled from timetracker_class::$racecats

`race_categories_menu.php` previously did
`e107::getSingleton('timetracker', e_PLUGIN.'timetracker/classes/timetracker_class.php')`
and read `timetracker_class::$racecats` (a category x track join). That was the
LAST timetracker coupling in this file (a **class** dependency, separate from LAN).

The legacy `$racecats` was built in `timetracker_class::__construct()` as:

```sql
SELECT * FROM #race AS r, #race_category AS rc
WHERE FIND_IN_SET(r.race_id, rc.race_category_race) ORDER BY race_id, race_category_sef DESC
```

retrieved via `e107::getDb()->retrieve($query, true)` - one row per (track x
category) pair, each row carrying the track fields (`race_name`, `race_sef`) and
the category fields (`race_category_name`, `race_category_sef`,
`race_category_color`), linked through the CSV `race_category_race` column.

That exact join is now reproduced in racers itself as
`plugin_racers_racers::getCategoriesWithTracks()`
(`racers/includes/racers.php`) - same `FIND_IN_SET` join over `#race` /
`#race_category`, same SELECT, same `race_id, race_category_sef DESC` ordering, so
the tile order is unchanged. The menu loads the racers class the racers way
(`e107::getSingleton('plugin_racers_racers', ...)`, mirroring `startlist.php`) and
calls the new method; the `$title`, `$category_color`, START LIST (`racers/startlist`)
and FINISH (`racereports/finish`) urls and the feature-tile markup are byte-for-byte
unchanged. The menu renders identically with timetracker uninstalled.

No `getSingleton('timetracker')` and no `timetracker/*` require remains in the file.

(Earlier in this strangler: the dead `e107::lan('timetracker')` load was removed and
the category admin-menu captions moved to racers' own `LAN_RACERS_ADMIN_CATEGORIES`
/ `_ADD`.)

## racers/list page (`racers.php`) - hardening pass

The SEF entry point for `/racers/list/` (e_url `index` route -> `racers.php`). Fixed
in place (kept as the SEF entry point; the dispatcher refactor + an `e_admin_ui`
list mode are deliberately deferred):

- **`"0000"` bib blanking (DATA LOSS).** The row loop used
  `isset($racer[$key]) && $racer[$key] != 0` - a loose numeric compare that emptied
  any special bib (`"0"`, `"00"`, `"0000"`) because it equals `0`. Now every field is
  cast to string and tested with `!== ''`; the bib renders verbatim. Genuine int
  checkbox columns (`racer_local`, `racer_active`) keep their "blank when `0`" look,
  handled **per-field** (`type == 'checkbox'`) so the list display is otherwise
  unchanged.
- **XSS: race / category names emitted RAW.** The override branches resolved
  `racer_race_id` -> `$race_array[...]` (race name) and `racer_category_id` ->
  `$tmp[...]` (category name) and wrote them to the `<td>` unescaped. Both now go
  through `$tp->toHTML(..., false, 'defs')` and the lookups are `isset()`-guarded so a
  racer pointing at a deleted race/category id falls back to `''` instead of a PHP-8
  warning. (The duplicate `racer_category_id` assignment line was removed.)
- **DataTables: dropped the CDN, use the shared local loader.** The two
  `https://cdn.datatables.net/searchbuilder/1.7.0/...` lines were removed (external
  dependency AND dead weight - `init.js` never referenced SearchBuilder; basic
  search+sort only is the suite policy). The hand-rolled base css/js loads were
  replaced with `race_report_load_datatables()` (racereports-owned LOCAL bs5 build,
  declares the jQuery dep), guarded on `isInstalled('racereports')` so the table still
  renders without sort/search if racereports is absent. `racers/init.js` is **kept**
  (it targets `table.racers`; the shared `init.js` targets the racereports report
  tables, so it would not init this one).
- **Entry-point bootstrap.** The old `if (!defined('e107_INIT')) { require '../../class2.php'; exit; }`
  guard (the bootstrap-then-halt include idiom) blank-exited on direct file access.
  Replaced with an unconditional `require_once(__DIR__.'/../../class2.php')`; it is a
  no-op when e107 is already loaded via the SEF route, so `/racers/list/` resolution is
  unchanged.
- **Small defects.** `$tp = e107::getParser()` is now explicit (no longer relies on the
  legacy global); `toHtml` -> canonical `toHTML`; the field-def key typo `'date '`
  (trailing space) -> `'data'` on `racer_city/local/team/tags/active` so the str/int
  hints apply; `$racers` is initialised so edit mode does not PHP-8-warn; the
  `addInfo("Listing all existing racers.")` debug banner was removed; the tablerender
  heading `"Racers List"` is now `LAN_RACERS_ADMIN_033` (EN + SK).

Cosmetic sweep left for later: the **SK-only** `language:{}` block in `racers/init.js`
is not LAN-driven (JS, out-of-band).

## Admin menu: Štartovacie listiny + Prehľad pretekárov (link directory)

The racers admin dispatcher (`admin/admin_menu.php`, `racers_adminArea`) gained two
new menu entries, mirroring how **racereports** exposes its report outputs (one output
= one admin file + one dispatcher mode, via the shared dispatcher):

- **Štartovacie listiny** (mode `startlists` -> `admin/admin_startlists.php`,
  `racers_startlists_ui`). A link directory to racers' OWN front start list
  (`startlist.php`): an all-tracks/all-categories link, then **per track** a panel with
  the track's "all categories" link plus **one link per category** of that track
  (categories resolved via `FIND_IN_SET(race_id, race_category_race) ORDER BY
  race_category_name` - the same fetch racereports' `renderFinishLinks()` uses). Every
  link is built with `e107::url('racers','startlist', array('race_sef'=>..,
  'race_category_sef'=>..))`.
- **Prehľad pretekárov** (mode `racerlist` -> `admin/admin_racerlist.php`,
  `racers_racerlist_ui`). A **single** reach-link (one button, new tab) to the
  racers/list front page via `e107::url('racers','index')` - it exists only so admins
  can reach that page from the menu, NOT a per-track list.

Mechanics / conventions worth knowing:

- **`startlist` "all" sentinel is `'all'`, NOT `'komplet'`.** racereports uses
  `komplet`/`overview` as its all-categories/all-tracks tokens; racers' own
  `startlist.php` (and its in-page nav) matches the literal token **`'all'`** for both
  `race_sef` and `race_category_sef`. Same mechanism, different sentinel string - the
  admin links use `'all'` to match what `startlist.php` actually reads.
- **Routing — SUPERSEDED 2026-06-30 (see "Admin separation FINISHED" below).** These
  screens were initially served the single-entry way (no `'url'` on the menu items; pure
  class defs `require_once()`d by `admin_config.php`). They are now FULL self-contained
  entry files, each routed via its own `$adminMenu` `'url'`
  (`admin_startlists.php` / `admin_racerlist.php`), mirroring racereports / the racetrack
  archive fix. The per-file split still mirrors racereports' one-output-per-file structure.
- **Readable link table.** The link rows are plain `<a>` inside `<td>` cells in a
  `table-striped table-bordered` panel - deliberately NOT `a.list-group-item`, whose
  admin-theme color (#c6c6c6) is unreadable - replicated from racereports' shared
  `admin_report_ui.php` link table (compact, readable rows).
- **Prehľad pretekárov is a controller, not the bare `$adminMenu` 'url' mechanism.**
  The dispatcher's `'url'` renders an in-frame admin link with no per-item `target`
  support; pointing it at a FRONT SEF route would navigate the admin frame into the
  public page (no new tab). A tiny controller screen renders a proper `target='_blank'`
  button instead.
- New LAN constants `LAN_RACERS_ADMIN_034`..`042` (EN + SK). Existing modes (`main`,
  `cat`) and the `init()` cross-plugin shortcuts are unchanged.

## Admin menu: Registrácia na mieste (on-site registration)

A THIRD link-directory entry, built the same one-output-per-file way as the two above.
The on-site registration info used to live in `admin_config.php`'s `renderHelp()` (the
prefs-page help box), where it cluttered the help alongside the icon snippet. It now has
its own clean admin item.

- **Registrácia na mieste** (mode `registration` -> `admin/admin_registration.php`,
  `racers_registration_ui`). A single info/link screen, conditional on the
  `racers/manualinput` pref:
  - `manualinput == 1` -> the "enabled" caution text (`LAN_RACERS_ADMIN_032`) plus a
    **new-tab** link button to racers' own `registracia` route
    (`e107::url('racers','registracia')`), captioned `LAN_RACERS_GLOBAL_029`.
  - else -> the "not enabled" notice (`LAN_RACERS_ADMIN_031`).
- **Constants reused, not invented.** The message strings are the SAME constants the old
  `renderHelp()` block used (`ADMIN_031`/`ADMIN_032`/`GLOBAL_029`). Only the menu-item
  caption is new: `LAN_RACERS_ADMIN_043` ("On-site registration" / "Registrácia na
  mieste"), EN + SK.
- **renderHelp() now carries only the icon help.** The `if (manualinput==1){…}else{…}`
  registration block was removed from `racer_ui::renderHelp()`; the first part (the
  `LAN_RACERS_ADMIN_008` + `fa-map-marker-alt` `<pre>` icon snippet) stays untouched, and
  `renderHelp()` still returns a valid `array('caption'=>.., 'text'=>..)` with that icon
  part intact.
- Routing — SUPERSEDED 2026-06-30 (see "Admin separation FINISHED" below): originally a
  pure class def `require_once()`d by `admin_config.php` with no `'url'`; now a FULL entry
  file routed via its own `$adminMenu` `'url' => 'admin_registration.php'`, mirror of
  `admin_racerlist.php` (the closest sibling - a single-purpose info/link screen). Existing
  modes/screens and the `init()` cross-plugin shortcuts are unchanged.

## Admin separation FINISHED: three screens made full entry points (2026-06-30)

The three link/info screens above (`startlists`, `racerlist`, `registration`) were each
served the "single-entry" way: `admin_config.php` `require_once()`d their files (pure class
defs) and their `$adminMenu` items carried NO `'url'`, so clicking any of them routed
through `admin_config.php` instead of its own file. This mirrored the SAME anti-pattern the
racetrack archive had, and is now fixed the IDENTICAL way (`racetrack/admin/admin_archive.php`).

- Each of `admin_startlists.php`, `admin_racerlist.php`, `admin_registration.php` is now a
  FULL self-contained admin ENTRY POINT, structured exactly like `admin_config.php` and the
  racetrack `admin_points.php` / `admin_archive.php`: head = `require_once("../../../class2.php")`
  + `require_once("admin_menu.php")` (the shared dispatcher, which loads class2 + the admin
  LAN) + the `getperms('P')` gate; the controller + `form_ui` class defs are unchanged
  (same screens, verbatim); tail = `new racers_adminArea(); require_once(e_ADMIN."auth.php");
  e107::getAdminUI()->runPage(); require_once(e_ADMIN."footer.php"); exit;`. The old
  "PURE class def, e107_INIT-guarded, admin_config require's me" headers are gone.
- `admin_menu.php`: the three `$adminMenu` items now carry their own `'url'`
  (`'url' => 'admin_startlists.php'` / `'admin_racerlist.php'` / `'admin_registration.php'`),
  exactly like racetrack's `archive/*` items now carry `'url' => 'admin_archive.php'`. Each
  mode's `'path'` stays `null` (the dispatcher does NOT include controllers; `e_admin_ui`
  only instantiates the ACTIVE mode, so listing a mode whose class lives in another file is
  safe). The mode comments that said "required by admin_config.php (the single entry)" were
  updated to "its OWN entry file".
- `admin_config.php`: the three `require_once("admin_startlists/racerlist/registration.php")`
  lines were removed. `admin_config.php` is now the entry point for ONLY the racer (`main` ->
  `racer_ui`) + category (`cat` -> `race_category_ui`) CRUD it owns; `main/*` + `cat/*` keep
  NO `'url'` (they legitimately belong to `admin_config.php`) and are unchanged.
- No double-run of the dispatcher (matches the working archive fix): only one entry file runs
  per request, each requires `admin_menu.php` once and calls `runPage()` once.
- Pure structural change — every screen renders exactly as before (start-list links per
  track/category; racer-overview reach-link; registration info conditional on `manualinput`).
  `php -l` clean on all five touched files.

## Age + gender -> category matcher - Phase 1 REVERTED (2026-06-30)

Phase 1 (`pickCategory()` + CSRF on the on-site category filter) broke on-site registration (403 on the filter endpoint, dropdown stopped filtering) and was fully reverted; the feature will be redone from scratch.

### Redo step 1 (2026-06-30)
- New standalone `includes/registration.php` created: `plugin_racers_registration::getCategories($birthday,$gender,$raceId=0)` with the on-site age+gender match copied 1:1 from the working (post-revert) handler; dormant/unwired; `includes/racers.php` untouched; redo proceeding in small steps.

### Redo step 2 (2026-06-30) — handler now delegates to the shared method
- `racer_category_handler.php` no longer matches inline. Its age compute +
  `$where1`/`$where2`/`$where3` + `$sql->retrieve('race_category', …)` block was removed and
  replaced by `require_once(e_PLUGIN.'racers/includes/registration.php')` +
  `plugin_racers_registration::getCategories($birthday, $gender, $race)`. The category-match
  logic now lives in ONE place (`includes/registration.php`). `registration.php` is now CALLED
  (no longer dormant).
- **Pref-typo divergence check (before switching):** read both copies line by line. Both the
  handler AND `getCategories()` already read the FIXED pref `e107::pref('racers','startforage')`
  — the `'racer'→'racers'` fix had been applied to BOTH, so they were already in sync. No
  reconciliation was needed. Every WHERE clause, the `$where2 . $where1 . $where3`
  concatenation order, the FIND_IN_SET-only-when-track guard, the verbatim age computation
  (undefined `$calculationMethod` falling through to the today-diff), and the `retrieve(…, true)`
  call are byte-identical. The only cosmetic diffs — `$where3` left uninitialised in the handler
  (undefined→`""` in concat) and `getCategories()` normalising a `false` return to `[]` — produce
  the exact same query string and the exact same row set/order, which the handler's
  `empty()`/`if($categories)` already treated identically.
- **On-site output is byte-identical.** The handler keeps everything else unchanged: the POST
  read (`birthday`/`gender`/`race`), the incomplete-input `"<option value=''>Vyberte
  kategóriu</option>"` prompt, the empty-result `"<option value='x'>Zadám neskôr</option>"`
  sentinel, and the same `<option value=race_category_id>race_category_name</option>` loop in the
  same order. The endpoint's external behaviour (what `racers.js` receives) is unchanged; the
  on-site registration dropdown filters exactly as before.
- **Scope:** racers ONLY. No CSRF / bound params / validation / gender whitelist added (those are
  separate later steps). `racers.js`, `registracia.php`, `includes/racers.php` byte-unchanged.
  `php -l` clean on both files.
