# racereports - developer notes

_Created: 2026-06-24 08:04 UTC._

Reports + archive plugin for the single-event race-timing suite (e107 Lite
2.4.x). These notes live here, not as XML comments in `plugin.xml`, by project
convention.

_2026-06-26: `includes/aktualne_build.php` now loads its own front LAN
(`e107::lan('racereports', '', true)`) so it is self-contained for the archive
generate path (racetrack calling `racereports_aktualne_build()`), not only the
front page - prevents an undefined `LAN_RACEREPORTS_AKT_*` fatal._

_2026-06-27: **finish/start "all tracks on one page" mode - FIRST param renamed
`komplet` -> `overview`.** The finish and start reports have a special stacked-
tables mode (every track rendered in turn, site chrome suppressed via `e_IFRAME`).
It used to be reached with the FIRST url param `r=komplet`, which was confusing
because the SECOND param `c=komplet` already means "all categories". The
all-tracks trigger is now `r=overview`:_
- _`/finish/overview/komplet/` = ALL tracks, all categories (the stacked page)._
- _`/finish/sm-50km/komplet/`  = one track, all categories (**`c=komplet`
  UNCHANGED**)._
- _`/finish/sm-50km/muzi/`     = one track, one category. Same for `start`._

_Only `report_finish.php` + `report_start.php` have this mode (verified). The
trigger declaration and ALL callers were moved together: the PARSE side
(`$raceSef === 'overview'` in both report files - chrome/e_IFRAME, all-tracks
render, and the export-scope exclusion) and the sole GENERATE callers
(`admin/admin_finish.php` + `admin/admin_start.php`, via a new
`racereports_reports_ui::OVERVIEW = 'overview'` constant; the second param still
uses `self::KOMPLET`). The SECOND-param `c=komplet` (= all categories) was NOT
touched anywhere - `e_sitelink.php`, `admin_report_ui.php`
`renderFinish/StartLinks()`, and `raceevent/includes/event_overview.php` all keep
`race_category_sef => 'komplet'`. `event_overview` builds only per-race
`c=komplet` links (no first-param all-tracks link), so it was NOT a caller and was
left untouched. The `{race_sef}` route token in `e_url.php` is free (`.*`), so
`overview` passes through with no route change. The misleading admin/LAN label
("... všetky preteky (komplet)" / "... all races (komplet)";
`LAN_ADMIN_RACEREPORTS_082` finish, `_092` start) is now "... všetky trate na
jednej strane" / "... all tracks on one page". Behaviour of the mode is
byte-identical; only the trigger value and the label changed._

## State: skeleton only

This is the **plugin shell**. There is no reporting logic, no ranking, no
snapshot/freeze code, no front-end, no menus, no `e_admin_ui` model, and no data
migration. The admin entry (`admin/admin_config.php`) renders a single
placeholder string behind the plugin's own admin permission (`getperms('P')`).
The **read-only result reports, ranking and the archive snapshot/freeze logic are
NOT yet implemented** - they are extracted from `timetracker` in a later issue.

`racereports` is the planned replacement for the reports + archive half of
`timetracker`, following the strangler pattern: the new owner is created first
(this issue), the behaviour moves later, and the old code is removed last.

## Table ownership

> **CORRECTION (2026-06-24, see the dated section at the foot of this file).**
> An earlier draft said racereports owns `race_result` + `race_archive` and copied
> their `CREATE TABLE` verbatim into `racereports_sql.php`. That was wrong:
> `race_result` is **terminovka-owned** and `race_archive` is **raceevent-owned**.
> Per the corrected architecture **racereports declares NO tables of its own yet**
> — `racereports_sql.php` is now an explanatory no-op. The text below is kept for
> history; the no-op `racereports_sql.php` is authoritative.

`racereports` declares **no tables**. The two `CREATE TABLE` statements that were
previously here have been **removed** — declaring a table another plugin owns is a
duplicate-ownership install conflict. racereports will add its OWN ranking/results
table when that logic is implemented.

### Explicitly NOT owned by racereports

- `race_result` - belongs to **terminovka** (result staging / export log).
- `race_archive` - belongs to **raceevent** (frozen archive snapshots).
  (Both currently still live in `timetracker/timetracker_sql.php` (legacy),
  pending the later extraction to their owners.)
- `race_time` - belongs to **racetiming** (the checkpoint timing engine).
- `race_tracking` - belongs to **racerfid** (the RFID import plugin).

## Dependencies

`raceevent` (event base) + `racetrack` (tracks/checkpoints) + `racers` +
`racetiming` (the compute engine the front report pages `require_once`). Declared
in `plugin.xml` `<dependencies>`, mirroring the working format in
`racereg/plugin.xml`. (`racetiming` was added 2026-06-24 — see the dated section
at the foot of this file; an earlier note said no `racetiming` dependency was
needed because the engine is loaded by path, but a hard dependency is the correct
declaration since the pages cannot run without it.)

## Language files

Sub-folder layout (`languages/English/`, `languages/Slovak/`), array-return
format (`return array(...)`, never `define()`), loaded with the 3-arg loader
`e107::lan('racereports', true, true)` (admin) and
`e107::lan('racereports', 'global', true)` (global). English is the canonical,
complete set; Slovak overrides per key and falls back to English for any missing
key. LAN key pattern: `LAN_<SCOPE>_RACEREPORTS_<NAME>`.

## Out of scope (this issue)

No `e_url.php` / SEF routes, no front-end pages or menus, no front LAN files
(`English_front` / `Slovak_front`), no reporting / ranking / snapshot / freeze
logic, no model/sql handler classes, no changes to `timetracker` or any other
plugin, no data migration.

---

## State: live reports (PART B) — point + online IMPLEMENTED

_Updated: 2026-06-24 — clean point/online reports built on the racetiming engine._

Two thin live-read report pages now consume the racetiming compute engine and
reproduce the legacy timetracker output. timetracker is **untouched** and keeps
running for the parity check (strangler). All time math comes from the engine
(`race_clock` + `race_format`); these pages do ONLY fetch → bucket/rank → render.

- `report_online.php` — ONLINE overall standings (clean equivalent of
  `timetracker_online.php`). Buckets racers by furthest checkpoint reached
  (checkpoints walked finish-first = `race_point_order DESC`), consumes each
  racer at that furthest point, sorts elapsed ASC within the bucket. Rank is the
  cumulative `$global_racer_order` across buckets, except the finish bucket shows
  its local rank. Columns: rank, bib(string), name+club, nationality, time,
  checkpoint-code (`race_point_code`). Opt-in `?refresh=<int>` meta refresh.
  Supports `r=komplet` (all races, DESC) and `c=komplet` (all categories, ASC).
- `report_point.php` — POINT / per-checkpoint report (clean equivalent of
  `timetracker_point.php`). All racers timed to ONE chosen checkpoint, sorted
  elapsed ASC. `p=komplet` loops all checkpoints except start/finish. Columns:
  rank-at-point, bib(string), name, time.
- `includes/race_report.php` — shared helper: clean `getRacerName`, the
  fetch helpers (race/category/point/racers), and `diffOnPoint()` which
  reproduces the legacy `get_racer_time_on_point` "point"-type return shape from
  the engine (the single time-cell source of truth both pages format from).

### New SEF routes (NEW keys only)

`e_url.php` adds two NEW plugin-scoped keys — `point` and `online` (renamed
2026-06-24 from `point_times`/`live`; see the dated restructure section at the
foot of this file). The keys are plugin-scoped (`e107::url('racereports', …)`),
so the names do not collide with timetracker's own keys. timetracker's `e_url`
keys and every caller are left UNTOUCHED (callers keep pointing at timetracker;
re-homing the front-end links is a later phase). Route integrity of the two new
keys is asserted by `parity/engine_selftest.php` and `parity/parity_check.php`.

The `online` **SEF alias** now uses the clean `online` segment (timetracker's
`online` SEF route is commented out, so there is no collision); the `point` SEF
alias stays DISTINCT as `point-times` because timetracker still owns the active
`point` SEF segment during the strangler transition. The clean `point` SEF alias
can be claimed once timetracker's route is retired.

### Engine loaded by path — racetiming dependency now DECLARED

The pages `require_once(e_PLUGIN.'racetiming/includes/…')` to use the engine.
~~No `<plugin name="racetiming"/>` dependency is added.~~ **Superseded
(2026-06-24):** `<plugin name="racetiming"/>` IS now declared in `plugin.xml` —
the pages cannot run without the engine, so a hard dependency is the correct
declaration. Deps are now `raceevent + racetrack + racers + racetiming`.

### Intentional fixes vs legacy (flagged, see PARITY-RESULTS.md)

- **DSG → DSQ** typo corrected in the time cell (the one sanctioned divergence;
  the comparator lists it, never auto-passes).
- **ENDED rows shown in the POINT report.** The confirmed principle is "render
  NORMAL (ranked) first, then ENDED (DNF/DSQ/DNS/no-time) after". The legacy
  point page hid the ENDED group from non-admins via a trailing-space comparison
  bug (`$difftext == "DNF"` never matched because `$difftext` is
  `text." ".ended`). The clean point report shows the ENDED group per the
  principle; this is an intentional fix, flagged in PARITY-RESULTS.md.
- **Name escaping.** `getRacerName` routes the admin-entered surname/firstname
  through `$tp->toHTML()` (legacy left them raw — a stored-XSS surface). No-op
  for plain-ASCII names, so display parity holds; for names with HTML special
  chars the comparator would flag the (safe) difference.

### Admin-only cells OMITTED (out of scope)

The legacy point page drew admin-only cells: a crossing ✅/❌ probe and an edit
icon linking the timetracker/`race_time` admin (admin CRUD is a separate
effort). Those are intentionally NOT reproduced — the new pages render the public
display columns only. No new admin actions are wired.

### SCOPE held

No timetracker changes. No writes / result-freeze / terminovka / archive — the
online page is a pure live READ (the legacy `genresults=1` freeze is NOT
replicated or moved). No `race_time` admin/CRUD. No `<dependencies>` added.

### Security (pages)

The legacy raw-`$_GET`-into-SQL concatenation is NOT carried over: every `$_GET`
(`r`/`c`/`p`), the point code, and any SQL value pass through `$tp->toDB()`;
race/category ids are `(int)`-cast; the bib number stays a quoted string. Output
goes through `$tp->toHTML()`/`toAttribute()`.

## Parity harness (PART C)

- `parity/engine_selftest.php` — CLI, NO database, NO e107. Proves the pure
  compute/format against hand-computed legacy values + a byte-parity fuzz of the
  formatter, over all required scenarios (normal; leading-zero bib; missing
  intermediate; Cinko DNF marker + later valid crossing; DSQ; DNS; no start; two
  crossings same point). Run: `php eplugins/racereports/parity/engine_selftest.php`.
- `parity/parity_check.php` — admin-gated (`getperms('P')`), runs INSIDE e107.
  Builds the clean engine and the legacy timetracker class over the SAME
  race_time rows and asserts the time cell is string-equal, classifying the
  DSG→DSQ difference as an intentional fix. Also runs the new-key route check.
  Legacy stays untouched; the result-freeze is disabled for the run.

## State: admin landing page + architecture fixes

_Updated: 2026-06-24 — working admin landing page; two architecture defects fixed._

The admin entry is no longer a placeholder. Earlier notes describing
`admin/admin_config.php` as "a single placeholder string" and the
`admin/admin_menu.php` redirect are **superseded** by this section.

### `admin/admin_menu.php` — redirect loop removed

e107 **includes** `admin_menu.php` while building the admin chrome (the per-plugin
left menu), so a top-level `e107::redirect()` fired on every menu build — a
redirect loop. The file is now a **safe no-op stub**: guarded by
`if (!defined('e107_INIT')) exit;`, no redirect, no output at include time. The
landing screen is reached via the `adminLinks` entry in `plugin.xml`. A real
dispatcher/left-menu can drop in here unchanged when one is added.

### `admin/admin_config.php` — links landing page

Plain native-e107 admin page (class2.php bootstrap → `getperms('P')` gate →
`e107::lan(...)` → `e_ADMIN/auth.php`). It **reads** the event structure via the
db class and renders ready-to-click links into the two front reports, grouped per
race:

- **online** (section "Zostavy Online"): an "online - all categories" link
  (`race_category_sef = komplet`) plus one link per category of the race.
- **point** (section "Časy po kontrolných bodoch"): an "all checkpoints" link
  (`race_point_sef = komplet`) plus one link per checkpoint, skipping start/finish.
- a clearly-labelled **parity check** link
  (`e_PLUGIN_ABS.'racereports/parity/parity_check.php'`, which stays admin-gated);
  `engine_selftest.php` is CLI-only and is mentioned in text but NOT linked.

Every link is built with `e107::url('racereports', <key>, array(...))` (never a
hand-built query string) so SEF/non-SEF both resolve. All DB output is escaped
(`$tp->toHTML()` for text, `$tp->toAttribute()` for hrefs). Empty tables degrade
to a short "no races/categories/checkpoints yet" line instead of erroring. No
prefs, no `e_admin_ui`, no `e_admin_dispatcher`, no writes.

#### Linking columns + url tokens (confirmed)

Confirmed against `racers/racers_sql.php`, `racetrack/racetrack_sql.php` and
`racereports/e_url.php`:

- `#race`: `race_id`, `race_sef`, `race_name`.
- `#race_category` (owned by **racers**): tied to a race via
  `FIND_IN_SET(race_id, race_category_race)`; sef = `race_category_sef`.
- `#race_point` (owned by **racetrack**): tied via
  `FIND_IN_SET(race_id, race_point_race)`, ordered by `race_point_order`. **There
  is no `race_point_sef` column** — the identifier is `race_point_code`. The
  `e_url` `point` route's `{race_point_sef}` token therefore carries the
  **`race_point_code`** value (`report_point.php` reads `?p=` and looks it up via
  `fetchPointByCode` on `race_point_code`).
- `e107::url()` token names match the `e_url.php` sef strings:
  `online` → `array('race_sef'=>…, 'race_category_sef'=>…)`;
  `point` → `array('race_sef'=>…, 'race_point_sef'=>$race_point_code)`.

### Architecture defects fixed (racereports-only)

1. **`plugin.xml`** — added the missing `<plugin name="racetiming"/>` dependency
   (the front pages `require_once` the racetiming engine; deps are now
   `raceevent + racetrack + racers + racetiming`).
2. **`racereports_sql.php`** — removed BOTH `CREATE TABLE` statements. racereports
   must not declare `race_result` (terminovka-owned) nor `race_archive`
   (raceevent-owned); declaring another plugin's table is a duplicate-ownership
   install conflict. The file is now an explanatory no-op (a clean no-op in e107 —
   `XmlTables()` finds no `CREATE TABLE` to run). The live tables are NOT dropped;
   their owners declare them. racereports owns no table yet. See "Table ownership".

### SCOPE held

racereports-only diff. No changes to racetiming, timetracker, race_time admin/CRUD,
any write/freeze/terminovka path, or the front report pages' compute logic.

---

## State: cosmetic + admin restructure (terminology, file/key renames, nav)

_Updated: 2026-06-24 — racereports-only cosmetic pass; no engine/compute changes._

A folder-neatness + terminology + admin-consistency pass. **No** engine/compute,
timetracker, racetiming, race_time, write/freeze or terminovka changes; the report
pages' compute/render logic is byte-for-byte unchanged (only headers/comments and
the file names changed).

### File renames (consistent `report_` prefix)

- `live.php` → **`report_online.php`**
- `point_times.php` → **`report_point.php`**

Every reference was updated in the same change: the `e_url.php` redirect targets,
the `includes/race_report.php` doc comment, the parity comparator/self-test route
checks (`parity/parity_check.php`, `parity/engine_selftest.php`), the
`languages/English/English_front.php` header, and the admin links.

### `e_url.php` key renames (plugin-scoped; callers updated atomically)

- key `live` → **`online`**, redirect → `report_online.php?r=$1&c=$2`
- key `point_times` → **`point`**, redirect → `report_point.php?r=$1&p=$2`

These keys are plugin-scoped (`e107::url('racereports', KEY, …)`), so there is
**no collision** with timetracker's same-named keys. ALL callers were updated in
the same change — the admin overview links and the parity route-integrity check
(`parity/parity_check.php` now checks `array('point', 'online')`,
`engine_selftest.php` checks the `point`/`online` keys + the new redirect targets).
Each `e107::url('racereports', KEY, …)` resolves to an active array key.

### SEF aliases — `online` now clean, `point` still DISTINCT

The `online` **SEF alias** now uses the clean `online` segment — timetracker's
`online` SEF route is commented out, so there is no collision. The `point` SEF
alias is still `point-times` (deliberately NOT the clean `point` segment),
because timetracker still OWNS the `point` SEF segment during the strangler
transition; reusing it now would create a SEF routing collision. The clean
`point` SEF alias can be claimed in `e_url.php` once timetracker's route is retired.

### Terminology: "online" not "live"

All admin UI strings now say "online" (English `English_admin.php`, Slovak
`Slovak_admin.php`). The admin section labels are "Zostavy Online", "Časy po
kontrolných bodoch", "Parity test".

### Admin: "Prehľad preteku" left-nav + sections

- `admin/admin_menu.php` stays a **guarded no-op stub** (e107 includes it to build
  the menu; no dispatcher yet, no side effects).
- `admin/admin_config.php` builds the left admin-nav ITSELF via the native
  `admin_config_adminmenu()` hook (e107's admin chrome calls
  `<page-basename>_adminmenu` if defined, else includes `admin_menu.php`), so the
  nav renders **once** from this file. ONE item, labelled "Prehľad preteku", via
  `e107::getNav()->admin($caption, $current, $var)`.
- The page body is the event overview (modeled on the prior landing page): reads
  `race` / `race_category` / `race_point` via the db class; builds links with
  `e107::url()`; escapes via `$tp->toHTML()`/`toAttribute()`; empty-state lines.
  Sections in order: **Zostavy Online** (`online` links: per-race komplet + per
  category), **Časy po kontrolných bodoch** (`point` links: per-race komplet + per
  checkpoint, skipping start/finish), **Parity test** (admin-gated link to
  `parity/parity_check.php`, `target=_blank`). The class2.php +
  `e107::lan(raceevent/racereports)` + `e_ADMIN/auth.php` bootstrap and the
  `getperms('P')` gate are kept.

### RUNTIME step (not a code change)

After deploying, the live install needs a **URL-config refresh in Admin → URLs**
so e107 re-reads the renamed `e_url.php` keys/aliases. This is an operator runtime
step, not something this code change performs.

### `e_sitelink.php` — ONLINE report in the site navigation

`e_sitelink.php` (e107's singular addon convention; class `racereports_sitelink`)
re-homes the ONLINE report into the site navigation, modelled on timetracker's
`e_sitelink.php::race_online()` but pointed at racereports:

- Links are built with `e107::url('racereports', 'online', …)` (the renamed
  plugin-scoped key), NOT timetracker's key. The config entry + builder method are
  racereports-scoped (`racereports_online`) so they do not collide with
  timetracker's still-active sitelinks during the strangler transition.
- Same data source as the legacy `race_online()`: a per-race "all categories"
  (komplet) entry from `#race`, plus one entry per `#race_category` (the
  `#race` ⋈ `#race_category` FIND_IN_SET join). Read **directly via the db class**
  — NO dependency on timetracker's singleton. Read-only; the href is escaped with
  `$tp->toAttribute()`.
- **e107 dedup pitfall (handled).** `manage_link()`/`compile()` collapse
  function-based sitelinks from one plugin that all share `url="#"` to the first
  entry. The workaround (as in the legacy builder) is to give EACH generated link
  a DISTINCT `link_url` via `e107::url()` with distinct sef params — the per-race
  komplet entry and every per-category entry resolve to different URLs, so all of
  them install rather than collapsing.
- Route integrity: every `e107::url('racereports', 'online', …)` here is covered
  by the part-2 route-integrity check. The SEF alias is NOT shared with
  timetracker.

---

## State: checkpoint sitelinks + admin checkpoint screen + info-only overview

_Updated: 2026-06-24 — racereports-only nav pass; no engine/compute, timetracker,
racetiming, race_time or write changes._

Three additive/cosmetic changes, all inside racereports.

### `e_sitelink.php` — CHECKPOINT report in the site navigation (added alongside online)

A second builder, `racereports_points`, joins the existing `racereports_online`
builder (both kept). It is modelled EXACTLY on timetracker's
`e_sitelink.php::race_points_list()` — same data source and loop
(`SELECT * FROM #race AS r, #race_point AS rc WHERE FIND_IN_SET(r.race_id,
rc.race_point_race) ORDER BY race_point_order`), the same skip of the
`start`/`finish` `race_point_code`s, the same `$point['race_point_sef'] =
race_point_code` mapping, and the same sublink field shape (`link_name` =
`race_name . " - " . race_point_name`, `link_open` `_blank`, etc.) — but pointed
at racereports:

- the link is built with `e107::url('racereports', 'point', $point)` (the renamed
  plugin-scoped key from the e_url restructure), NOT timetracker's `point` key;
- the function name (`racereports_points`) and its `config()` sitelink entry
  ("Časy na kontrolách") are racereports-scoped, so they do NOT collide with
  timetracker's still-active `race_points_list` sitelink during the strangler
  transition. timetracker is UNTOUCHED — Jimmi removes its `race_points_list` by
  hand separately.
- **e107 dedup pitfall (handled).** Each checkpoint resolves to a DISTINCT
  `e107::url()` (distinct `{race_sef}/{race_point_sef}` tokens), so `manage_link()`
  installs ALL checkpoints rather than collapsing function-based links that would
  otherwise share `url="#"` to the first entry. Read-only via the db class; the
  href is escaped with `$tp->toAttribute()`.

### Admin: checkpoint reports reachable natively (new `point` dispatcher mode)

The per-checkpoint report links are now reachable from the admin the SAME native
way as the online links: a new dispatcher mode `point` (`admin/admin_menu.php`
`$modes` + `$adminMenu`) routes to a new `racereports_point_ui` controller in
`admin/admin_config.php` whose `pointPage()` renders per-race checkpoint links via
the shared base's `renderCheckpointLinks()` — mirroring `racereports_online_ui` /
`onlinePage()`. New menu caption `LAN_ADMIN_RACEREPORTS_006` ("Časy na
kontrolách" / "Checkpoint times").

### Overview screen ("Prehľad preteku") = INFO ONLY

`overviewPage()` no longer renders the per-race/category/checkpoint live links (nor
the parity link). It now shows ONLY a short static, non-clickable list of the
supported report types — heading "Zoznam podporovaných výsledkov", items "Online"
and "Časy na kontrolách" (`LAN_ADMIN_RACEREPORTS_050`–`052`). No `e107::url()` on
this screen. The actual clickable links live on the "Online výsledky" and "Časy na
kontrolách" screens. (The parity comparator stays reachable by its direct
admin-gated URL `parity/parity_check.php`; it is simply no longer linked from the
info-only overview.)

### POINT report ENDED-row color fix

In `report_point.php` the ENDED loop (DNF/DSQ/DNS/no-time) no longer applies the
per-category background color — ENDED rows render as a plain `<tr>`. Only
ranked/normal racers are colored, matching the already-fixed `report_online.php`
ENDED loop. Presentation-only; no compute/ranking change.

---

## State: SUT report (per-track finishers-only results list)

_Updated: 2026-06-25 — racereports-only; clean SUT report on the racetiming engine,
new `stu` e_url key, new admin `stu` screen. No timetracker / racetiming /
racetrack / race_time / write / freeze changes._

### Phase 0 verdict — is legacy `timetracker/stu.php` still capable of correct times?

**YES on a normal race that already has its `start` crossing rows in `race_time`.
The compute path is intact; the failure mode is purely data (missing start
crossings), not a severed dependency.**

- The stu elapsed = `timetracker::get_racer_time_on_point($number, 'finish',
  'archive')` (`timetrackerStu_class.php:214`). That derives **start from the
  `'start'` CROSSING ROW** in `race_time` —
  `self::$times[$number]['start']['race_savedtime']`
  (`timetracker_class.php:255`), built in the constructor (`:57-87`) from
  `race_time`. It is **NOT** read from the `starttime` pref and not directly from
  the bulk generator. `$startpoint == 0` (no start row) ⇒ returns `text="-",
  time=""` ⇒ blank (`:260-264`).
- **`starttime` pref relocation does NOT break stu.** `timetrackerStu_class` never
  reads `starttime` (grep confirms — it only uses `display_local`/`text_local`/
  `display_team`). The pref moved to racetiming with no value migration
  (`racetiming/NOTES.md:304-322`), but stu doesn't consult it. `generujStart` was
  never a pref.
- **Bulk start-generation (the PRODUCER of the `start` rows) WAS relocated** to
  `racetiming/admin/admin_generujstart.php` (`timetracker/NOTES.md:32-53`); it uses
  `starttime` only as the default fill time. Rows it already wrote stay in
  `race_time`.
- **No dangling requires / deleted methods.** `stu.php` pulls only class2 / HEADERF
  / `timetrackerStu_class.php` / FOOTERF; every method it calls
  (`get_racer_time_on_point`, `ISO8601ToMicrotime`, `secondsToTime`,
  `get_dnf/dsq_racers`) is present.
- **Net:** parity is trustworthy ONLY on a race that already has `start` crossings.
  A race lacking starts shows blanks in BOTH legacy and the new report — a data
  gap, not a parity failure. After the migration, re-generating starts requires the
  admin to re-enter `starttime` in racetiming first.

### `report_stu.php` — finishers-only per-track results

- `?p` selects a **TRACK by race_id** (legacy parity: `stu.php:36`
  `WHERE race_id=$_GET['p']`; legacy `stu` sef `{alias}/{race_id}/`). `?p` is
  `(int)`-cast; race lookup via the new `race_report::fetchRaceById()`.
- **FINISHERS ONLY.** A racer is listed iff
  `race_clock::elapsedToPoint($number, 'finish')` returns a float (engine status OK
  at finish — has start, has finish crossing, not DNF/DSQ/DNS). DNF/DSQ/DNS and
  any no-finish racer are excluded. This is the documented **scope difference** vs
  legacy stu, which also printed DNF/DSQ blocks (`timetrackerStu_class.php:64-87`).
- **One final time = elapsed start→finish**, via the existing engine op
  `elapsedToPoint` — **NO new engine op added**. Sorted by that full-precision
  float **ASC**.
- **Time cell = legacy byte-for-byte.** Legacy stu cell =
  `substr(secondsToTime(elapsed), 0, 8)` = `"HH:MM:SS"`
  (`timetrackerStu_class.php:221`). `race_format::formatElapsed($elapsed, 0)` is
  exactly that 8-char cut. (Display-only; sort key is the float.)
- **Columns in legacy order** (`timetrackerStu_class.php:234-253` / `:269-290`):
  Ranking · Time · Family Name · First Name · Gender · Birthdate · Nationality ·
  Bibnumber(string). Identity columns from the `racer` table; surname/firstname are
  separate columns (not `getRacerName`), matching legacy. Birthdate reproduces the
  legacy `"19.2.1996" → "1996-02-19"` normalisation (`:110-122`); an unparseable
  value passes through escaped instead of the legacy `"Zlý formát"` literal.
- **Coloring** uses the shared `race_report_color()` path (same as
  online/point) — every row is a finisher (ranked) so all are colored.
- **Security:** `?p` `(int)`-cast; SQL values via `$tp->toDB()` in the helper; bib a
  quoted string; output via `$tp->toHTML()`/`toAttribute()`. No writes / freeze.

### New `stu` e_url key — SEF alias kept DISTINCT (deferral)

`e_url.php` adds a plugin-scoped `stu` key →
`{e_PLUGIN}racereports/report_stu.php?p=$1`, sef `{alias}/{race_id}/`,
mirroring legacy's `?p=race_id` contract EXACTLY. The **SEF alias is
`stu-results`**, deliberately NOT the clean `stu` segment, because timetracker
still OWNS the `stu` SEF alias during the strangler transition; reusing it now
would be a SEF routing collision. The clean `stu` alias can be claimed once
timetracker's route is retired.

### Deferred: the racetrack SUT button still points at timetracker

`racetrack/admin_config.php:321` builds the per-track SUT button with
`e107::url('timetracker', 'stu', $data, ['mode' => 'full'])`. Per scope this is
**NOT touched / NOT repointed** — the racetrack link keeps pointing at the legacy
`timetracker/stu.php` for now. Re-homing that caller to
`e107::url('racereports', 'stu', …)` is a later phase (racetrack is out of scope
for this change).

### Parity — already asserted by the existing comparator

No change to `parity/parity_check.php` was needed. It already asserts the `finish`
time cell is string-equal (clean `diffOnPoint` vs legacy
`get_racer_time_on_point`) for every racer. The stu cell is that same finish
elapsed truncated to 8 chars, so its parity follows from the existing 10-char cell
equality (truncating both sides preserves equality). Per the Phase 0 verdict,
this parity is meaningful only on races that already have `start` crossings; a
no-start race shows blanks on both sides (data gap, not a failure).

### Admin: native `stu` screen (one link per track)

A new dispatcher mode `stu` (`admin/admin_menu.php` `$modes` + `$adminMenu`,
caption `LAN_ADMIN_RACEREPORTS_007`) routes to a new `racereports_stu_ui`
controller in `admin/admin_config.php` whose `stuPage()` renders ONE panel with
one link per race(track), built via `e107::url('racereports', 'stu',
array('race_id' => …))` — the same native render (shared base `linkItem`/`panel`)
as the online/point screens. New `racereports_stu_form_ui` registered. stu is
per-track only, so there are no komplet/category/checkpoint sub-links.

### SCOPE held

racereports-only diff: `report_stu.php` (new), `e_url.php` (new `stu` key),
`includes/race_report.php` (new `fetchRaceById`), `admin/admin_menu.php` +
`admin/admin_config.php` (new `stu` mode/controller), the four LAN files (new
keys). No timetracker / racetiming / racetrack / race_time changes; no
writes/freeze/terminovka; no new engine ops.

---

## State: admin restructure — one admin file per report area + SETTINGS page

The admin was reshaped so the entry files mirror the dispatcher modes 1:1.

### RULE: one admin file per report area (mode-per-file)

`admin/admin_config.php` is the **SETTINGS page and nothing else** — the
preferences controller (`racereports_main_ui`, a native `e_admin_ui` `$prefs`
form, no DB table). It must not host report-link controllers.

**Every report area lives in its OWN admin entry file**, never lumped together
(there is no `admin_reports.php`):

| mode       | action     | entry file                  | controller                  |
|------------|------------|-----------------------------|-----------------------------|
| `main`     | `prefs`    | `admin/admin_config.php`    | `racereports_main_ui`       |
| `overview` | `overview` | `admin/admin_overview.php`  | `racereports_overview_ui`   |
| `online`   | `online`   | `admin/admin_online.php`    | `racereports_online_ui`     |
| `point`    | `point`    | `admin/admin_point.php`     | `racereports_point_ui`      |
| `stu`      | `stu`      | `admin/admin_stu.php`       | `racereports_stu_ui`        |

The shared dispatcher class stays the single class def in
`admin/admin_menu.php`; the shared render helpers (link/panel/data accessors)
moved out of `admin_config.php` into the pure-class-def
`admin/admin_report_ui.php`, which each report entry file requires after
bootstrapping `class2.php` + the LAN. `_initController()` only instantiates the
**current** mode's controller, so each file only needs its own class defined.

### Future areas follow the SAME pattern

When a new report/admin area is added it MUST get its own `admin/admin_<area>.php`
(its own mode), not be folded into an existing file. (Example: a future stu
*settings/admin* screen beyond today's link list would be `admin/admin_stu.php`'s
own concern — the per-area file already exists.)

### Menu order + default = SETTINGS first

`$adminMenu` lists `main/prefs` FIRST (caption core `LAN_MANAGE`), then
`overview` / `online` / `point` / `stu`. Each entry carries an explicit `'url'`
to its own file; `renderMenu()` appends `?mode=..&action=..`. `$defaultMode` =
`main`, `$defaultAction` = `prefs`, so a bare `admin_config.php` load (no
`?mode=`) lands on `main/prefs` — whose controller IS defined in that file —
instead of fataling on a report controller defined elsewhere.

### New pref `stu_decimals` (default 0 at the consumer)

`racereports_main_ui` exposes one pref: `stu_decimals` (`type=number`,
`data=int`, `writeParms min=0`, title `LAN_ADMIN_RACEREPORTS_070`, help
`LAN_ADMIN_RACEREPORTS_071`). `report_stu.php` reads it as
`$dec = max(0, (int) e107::getPlugConfig('racereports')->get('stu_decimals', 0))`
and passes `$dec` to `race_format::formatElapsed($elapsed, $dec)`. Unset pref =>
0 => byte-for-byte the legacy whole-seconds (HH:MM:SS) cut.

### New pref `stu_colors` (default 0 at the consumer)

`racereports_main_ui` exposes `stu_colors` (`type=boolean`, `data=int`, title
`LAN_ADMIN_RACEREPORTS_072`, help `LAN_ADMIN_RACEREPORTS_073`). `report_stu.php`
reads it as `$useColors = (int) e107::getPlugConfig('racereports')->get('stu_colors', 0)`
and gates the row render: ON => `<tr style='background-color: …'>` via the shared
`race_report_color()`; OFF (the default, enforced HERE at the consumer, not in the
`$prefs` definition) => plain `<tr>`. Unset pref => 0 => SUT renders clean (no
category backgrounds) — stu's new default and intended look. SUT lists finishers
only, so the "ended rows never coloured" rule holds trivially regardless of the toggle.

Only SUT has the toggle today. The same pattern — a `report_colors` boolean pref
read with a `get(..., 0)` default, used to gate the `race_report_color()` call —
is the TEMPLATE when `report_online.php` / `report_point.php` need it. Those stay
unconditionally coloured for now; no `online_colors` / `point_colors` pref is added
yet and their coloring is unchanged.

### SCOPE held

racereports-only diff: new `admin/admin_report_ui.php`, `admin/admin_overview.php`,
`admin/admin_online.php`, `admin/admin_point.php`, `admin/admin_stu.php`;
rewritten `admin/admin_config.php` (prefs only) + `admin/admin_menu.php`
(reordered modes/menu); `report_stu.php` (read `stu_decimals`); the four LAN
files (keys `_070`/`_071`). No timetracker / racetiming / racetrack / race_time
changes; no engine/timetracker/racetiming touches.

---

## State: sortable columns (DataTables) on SUT — racereports-owned assets + central loader

_Updated: 2026-06-25 — racereports-only; sortable column headers on the SUT report
via a racereports-OWNED copy of the DataTables bs5 build, wired through ONE central
opt-in loader. No engine/compute changes. Native e107 (`e107::js`/`css`, core jQuery)._

### racereports now OWNS its DataTables assets (moved in from timetracker)

The bs5 pair SUT actually uses was COPIED from timetracker into racereports so the
report no longer depends on the timetracker copy (that copy is removed separately in
the timetracker SUT decommission — NOT touched here):

- `timetracker/datatables/css/datatables-bs5.min.css` →
  `racereports/assets/datatables/css/datatables-bs5.min.css`
- `timetracker/datatables/js/datatables-bs5.min.js` →
  `racereports/assets/datatables/js/datatables-bs5.min.js`

(DataTables 1.13.1 + Responsive 2.4.0, bs5 build.) The bs3/bs4/plain variants, the
dead per-language `languages/*.json` wiring, and the SearchBuilder CDN add-on were
**deliberately NOT carried over** — the legacy loaded SearchBuilder but its init
never used it, so it is dropped. A NEW racereports init replaces the legacy one:
`racereports/assets/datatables/init.js`.

### Central, opt-in loader — `race_report_load_datatables()` (in `includes/race_report.php`)

ONE procedural helper registers all three assets (css + datatables js + init js),
so report pages opt in with a single call instead of copy-pasting the
`e107::css/js` lines:

```php
race_report_load_datatables();   // once-guarded; loads css + datatables js + init.js
```

- **jQuery dependency DECLARED, not assumed.** e107 ships core jQuery and auto-loads
  it on the front-side, but both `e107::js()` registrations pass `'jquery'` as the
  declared dependency so the plugin and the init run after jQuery is ready (no
  reliance on implicit load order).
- **Once-guard** (static flag) makes repeated calls register the assets exactly once.

### Opt-in policy — SUT only today

- `report_stu.php` calls `race_report_load_datatables()` — the ONLY report enabling
  DataTables now.
- `report_online.php` does **NOT** call it: online is live/auto-refresh and
  DataTables would fight the meta-refresh.
- `report_point.php` is left as-is. **It can opt in later with a single call to
  `race_report_load_datatables()`** — no other change needed (this is the documented
  extension point).

### SUT table hook + init

- The finishers table now opens as
  `<table id="report_stu" class="table … race-report">` — a stable hook for the
  init. The existing 8-col thead/tbody is unchanged; only the `id`/class were added.
- `init.js` targets `#report_stu` with the modern API, minimal config:
  `{ paging:false, searching:false, info:false, order:[[0,'asc']] }`. Guarded on
  `$.fn.DataTable` so a missing library degrades to a plain static table. No Select,
  no Responsive (the `responsive` class is not applied).

### CRITICAL — Time column sorts NUMERICALLY (the stu_decimals trap)

The Time cell is a formatted string (`HH:MM:SS[.mmm]`). Lexicographic sort breaks
once widths differ (decimals from `stu_decimals`, or sub/over-hour lengths). Fix:
each Time `<td>` carries a native `data-order="<raw elapsed seconds>"` — the
full-precision engine float already held in `$racer['stu_sort']` (from
`race_clock::elapsedToPoint($number,'finish')`). DataTables sorts by `data-order`
while DISPLAYING the formatted cell text — no custom sort plugin needed. A `(float)`
cast guarantees a numeric attribute value (no escaping needed). Defensive sentinel
(`PHP_INT_MAX`) sorts any blank time last — N/A in practice since SUT is
finishers-only, but handled. **Result: with `stu_decimals=0` vs `>0` the row ORDER
is identical** (it sorts by seconds, never the formatted string).

### SCOPE held

racereports-only diff: new `assets/datatables/css/datatables-bs5.min.css`,
`assets/datatables/js/datatables-bs5.min.js`, `assets/datatables/init.js`; new
`race_report_load_datatables()` in `includes/race_report.php`; `report_stu.php`
(loader call + `#report_stu` hook + Time-cell `data-order`). timetracker is
**untouched** here (its DataTables copy is removed in the separate SUT
decommission). No engine/compute/ranking changes; no new engine ops; no writes.

## Online report — pref-driven auto-refresh (2026-06-25)

The Online report (`report_online.php`) gained an organizer-set default
auto-refresh, plus a top-of-page help note. racereports-only; no engine /
timetracker / terminovka change.

### New pref: `online_refresh_interval`

Added to the `$prefs` form in `admin/admin_config.php` alongside `stu_decimals`
/ `stu_colors` (native `e_admin_ui` $prefs — `type=number`, `data=int`,
`writeParms` `min=0`). Titles/help in LAN (`LAN_ADMIN_RACEREPORTS_074` /
`_075`, EN + SK, next in the existing sequence). Read back via
`e107::getPlugConfig('racereports')` — racereports' **own** pref.

> racereports does **NOT** read timetracker's `refresh_interval`. That is
> terminovka/timetracker territory; cross-plugin pref reads are forbidden.
> racereports gets its own `online_refresh_interval`.

### Resolution: URL `?refresh` always wins, even 0

In `report_online.php`, the pre-HEADERF block now resolves:

```php
$refresh = isset($_GET['refresh'])
    ? (int) e107::getParser()->filter($_GET['refresh'], 'int')          // URL present -> wins, even 0
    : (int) e107::getPlugConfig('racereports')->get('online_refresh_interval', 0); // else pref
if ($refresh > 0) { e107::meta(null, $refresh, array('http-equiv' => 'refresh')); }
```

- `?refresh=10` (any pref) → 10s; `?refresh=0` (any pref) → no refresh (pref
  ignored); no `?refresh`, pref=30 → 30s; no `?refresh`, pref=0/unset → none.
- **`isset()` not `?:`** is REQUIRED: `?refresh=0` must mean "disable on THIS
  window", not "fall back to pref". `?:` would collapse `0` to the pref and break
  the override.
- Default `get(..., 0)` at the consumer = unset pref + no URL param ⇒ **exactly
  today's no-refresh behaviour** (no install seed needed).

### `e107::meta()` MUST precede HEADERF

The `e107::meta(...,'http-equiv'=>'refresh')` call stays in the **pre-HEADERF**
spot (before `require_once(HEADERF)`), so the `<meta http-equiv="refresh">` lands
in `<head>`. A later `e107::meta()` call (after the header rendered) would
silently no-op — this is the whole reason the block is placed where it is.

### Page help (collapsible note)

racereports front pages had no existing help mechanism, so the online report
renders a short native `<details>`/`<summary>` note at the top (no JS, no
DataTables conflict). Copy lives in LAN (`LAN_RACEREPORTS_HELP_REFRESH_TITLE` /
`_BODY`, EN + SK); the BODY is trusted static HTML echoed raw (headings, list,
`<code>`), the TITLE goes through `toHTML()`. Guarded so a failed LAN load
(empty body) skips the block instead of rendering an empty panel.

## Shared report export (CSV + fake-XLS) — `includes/race_export.php` (2026-06-25)

Reports can now be downloaded as **CSV** and a **fake-XLS** (Excel-readable HTML
table — no external library, no PhpSpreadsheet). The format lives in ONE shared,
GENERIC module so every report reuses it instead of copy-pasting an export file
(the legacy approach: a per-report `timetracker_export.php`). racereports-only; no
engine / timetracker / racetiming / racetrack change — export is presentation, it
reuses the already-formatted display cells and recomputes nothing.

### The module: `race_export`

Two static renderers, modelled on the proven eadmin `users.php` RenderCsv/RenderXls
header/output pattern. They are GENERIC — not tied to any table or column set:

```php
race_export::csv($filename, array $headers, array $rows, array $textCols = array());
race_export::xls($filename, array $headers, array $rows, array $textCols = array());
```

- `$filename` — base name WITHOUT extension (the renderer appends `.csv` / `.xls`).
- `$headers` — ordered column **labels** = the report's OWN display labels.
- `$rows` — array of rows; each row = ordered array of cell values **ALREADY
  FORMATTED as displayed** (e.g. a time already run through
  `race_format::formatElapsed` with the `stu_decimals` pref). The module NEVER
  computes or reformats — it streams the string it is handed, so the file is
  byte-identical to the page.
- `$textCols` — indices (or keys) of columns to force to **TEXT** so a spreadsheet
  does not mangle them (a start number with leading zeros like `0042` must not
  become `42`; a time string like `12:17:55.48` must not be reinterpreted).

Output behaviour (mirrors `users.php`):

- **CSV**: `ob_clean()` any buffered chrome → `Pragma/Expires/Cache-Control` +
  `Content-Type: text/csv; charset=utf-8` + `Content-Disposition: attachment` →
  **PREPEND a UTF-8 BOM** (`\xEF\xBB\xBF`) so Excel reads Slovak diacritics →
  `fputcsv` the header row + every data row to `php://output` → `exit`.
- **XLS**: `ob_clean()` → `Content-Type: application/vnd.ms-excel` +
  `Content-Disposition` → `<meta charset=utf-8>` + `<table border=1>`; `<th>` per
  header; per cell `<td>` with `htmlspecialchars($value, ENT_QUOTES)`. A `$textCol`
  cell is emitted as `<td style="mso-number-format:'@'">…</td>` (`@` = force Excel
  **Text**; the legacy `users.php` did this only for dates — here it is the start
  number + time columns). Then `exit`.

Each renderer **ENDS the request** (`exit`) right after streaming — no
header/footer, no other output. Call it at the TOP of a report page, before
`HEADERF` / any echo.

### Contract for adding export to ANY report (the template)

`race_export` is generic so `report_online` / `report_point` / a future finish list
adopt it with **no new export file**. For each report:

1. Build the report's rows **ONCE** into a neutral structure: `$headers` (the
   report's display labels) + `$rows` (each row = ordered array of cells **already
   formatted exactly as displayed**). Mark the indices of any leading-zero /
   string-that-looks-numeric columns as `$textCols`.
2. Feed BOTH the HTML table and the export from that SAME structure — do NOT build a
   second, divergent row set, and do NOT define columns twice.
3. At the TOP of the page (before `HEADERF`): if `?export` is `csv`|`xls`, require
   `includes/race_export.php` and call `race_export::csv|xls($filename, $headers,
   $rows, $textCols)` — it streams and exits. Otherwise render the HTML as before.
4. Add a small `CSV` / `XLS` button group linking to the same URL with
   `&export=csv` / `&export=xls` appended.

### First consumer: `report_stu.php`

- Row-building was hoisted out of the old `render_stu_list()` into
  `stu_build_rows()`, which returns `headers` + `rows` (the 8 display cells:
  rank, time, surname, first name, gender, birthdate, nationality, bib) +
  `textCols = array(1, 7)` (time + start number) + `meta` (HTML-only decoration:
  the DataTables numeric `data-order` sort key, and the per-category row colour).
- `render_stu_list()` now renders the table FROM that structure; the visible table
  cells are **byte-for-byte the pre-refactor output** (same per-cell
  `toHTML(..., 'TITLE')` calls, same `data-order`, same opt-in colour). The only
  new markup is the export button group ABOVE the table (an intended UI addition,
  not part of the parity-protected cells).
- `?export=csv|xls` is handled at the top, before `HEADERF`: it builds the same
  `stu_build_rows()` structure and streams it via `race_export`. Filename is
  `stu_<race_sef>_<Y-m-d>.csv|.xls` (the `race_sef` is slug-sanitised so it cannot
  inject into the `Content-Disposition` header).
- **Parity:** the engine / `race_report::diffOnPoint` / `race_clock` / `race_format`
  are untouched and the stu time cell is still
  `race_format::formatElapsed($elapsed, $stu_decimals)`, so the DB-backed
  `parity/parity_check.php` (clean engine vs legacy time cell) is unaffected by
  construction — the refactor only reorganised where the already-formatted cells
  are built, not how they are computed.

### Text protection (the two columns)

- **Start number** stays a string: the bib is taken as `(string) racer_number`
  (leading zeros preserved, e.g. `0042` ≠ `42`) and its column index (7) is in
  `textCols`, so XLS stamps it `mso-number-format:'@'`.
- **Time** stays the exact formatted string (`12:17:55.48`); its column index (1)
  is in `textCols` too, so Excel will not reinterpret it as a time/number.

### Export buttons

- Front `report_stu` page: a `CSV` / `XLS` button group above the table
  (`btn-csv` / `btn-excel`, mirroring the `users.php` export-button look), linking
  to `report_stu.php?p=<race_id>&export=csv|xls` on the plugin path so BOTH the
  track and export params survive regardless of SEF.
- Admin SUT list (`admin/admin_stu.php` via the shared `renderStuLink()` in
  `admin/admin_report_ui.php`): each track's row now carries per-track `CSV` + `XLS`
  buttons pointing at that track's report `?export=` URL ("v riadku tlačítka na
  export zostavy").

### Deferred: CSV-as-Excel-text toggle

CSV cells are written **verbatim** (the BOM + raw value is already correct in the
file; `fputcsv` quotes/escapes per RFC 4180). We deliberately do **NOT** wrap cells
in `="…"` to force Excel-text, because that pollutes machine imports. If an
Excel-open-safe CSV is wanted later, add it as an **explicit toggle** on
`race_export::csv()` (e.g. a `$forceExcelText` flag honouring `$textCols`) — not the
default. `$textCols` is already accepted by `csv()` for a uniform signature, so the
toggle has somewhere to land without a signature change.

---

## Terminology: `racer_active` vs DNS — two different concepts (do NOT conflate)

These reports read `racer_active`, but the flag and the DNS status are NOT the
same thing. Keep them apart when reading or editing the notes below.

- **`racer_active` = a MANUAL bib-collection flag.** The admin sets it when a
  racer collects their start number before the start (`prevzal štartové číslo`;
  the admin column label is "Nastúpil"). It is a deliberate, hand-set marker —
  it is **NOT auto-derived from having a start time.** A racer can have an engine
  `start` crossing (`race_clock::timeOfDay(number,'start') !== null`) while
  `racer_active` is still `0` — e.g. bibs handed out in a hurry and never marked.
  That is a **VALID state**, not a data bug; the start list deliberately surfaces
  it (see `racers/NOTES.md` / `racers/startlist.php`) so the admin can spot who
  was not marked. Do NOT "fix" it by inferring `racer_active` from a start time.
- **DNS = "did not start"**, a results-level status used by the finish/start/online
  reports. It is a SEPARATE concept with a different purpose (reporting that a
  racer did not start), not a synonym for `racer_active`.
- **How they relate in THIS plugin:** the legacy reports — faithfully reproduced
  here — use `racer_active = false` as their *trigger* for the DNS bucket
  (`pointStatus()` precedence rule #1; `report_online.php`,
  `report_finish.php`, `report_start.php`). So "unmarked bib → shown as DNS in the
  results" is a report-level **interpretation** of the flag, NOT the definition of
  the flag. When the notes below say a racer is bucketed as DNS, read it as "the
  report treats an un-marked (`racer_active = false`) racer as DNS", never as
  "`racer_active` *means* DNS".

## State: FINISH report (post-race results list) — finishers + DNF/DSQ/DNS

_Updated: 2026-06-25 — racereports-only; clean FINISH report on the racetiming
engine (`race_clock::elapsedToPoint` + `pointStatus`), new `finish` e_url key, new
admin `finish` screen, new `finish_colors` pref. NO timetracker / racetiming /
racetrack / race_time / engine / write / freeze changes._

### `report_finish.php` — clean equivalent of `timetracker/timetracker_finish.php`

- Post-race RESULTS LIST. Four groups, in this order: **finishers, then DNF, then
  DSQ, then DNS**.
- **Engine only, NO new op.** The single final time per finisher =
  `race_clock::elapsedToPoint($number, 'finish')` (ranking-safe elapsed, only when
  the racer is OK at the finish checkpoint). Per-racer status comes from
  `race_clock::pointStatus($number, 'finish', $racerActive)` →
  `OK | DNF | DSQ | DNS | NO_START | NO_CROSSING`. Identity (name / bib /
  nationality) from the `racer` table.
- **Finisher = status OK and `elapsedToPoint('finish') > 0`.** Sort by that elapsed
  float: **ASC normally, DESC for `r=komplet`**. The DESC for komplet is
  **intentional (historical reason)** — flagged in a code NOTE; do NOT "fix" it to
  ASC. Rank: ASC counts up from 1; DESC counts down from the finisher count (mirrors
  the legacy `$racer_order`).
- **Time cell parity.** Display string = `race_format::formatElapsed($elapsed, 1)`
  = `substr("HH:MM:SS.mmm", 0, 10)` = tenths. The legacy finish page shows
  `substr($this->secondsToTime($diff['time']), 0, 10)` over
  `get_racer_time_on_point($number, 'finish')` (`timetracker_class.php:270`) — the
  SAME 10-char tenths cut. So the time cell is **string-equal to legacy**, asserted
  row-by-row by `parity/parity_finish.php`.
- **NO_START / NO_CROSSING racers are not rendered** — they match the legacy
  `racers3` bucket, which the legacy page collected but never echoed.
- **Status glyph column** reproduces legacy: finisher WITH a `start` crossing → ✅,
  finisher WITHOUT → 🏁, ended (DNF/DSQ/DNS) → ❌. A clean finish implies a start, so
  the 🏁 branch is effectively unreached for finishers — reproduced for fidelity.
- **Modes** (legacy `?r=<race_sef>&c=<race_category_sef>` contract):
  `r=komplet` → ALL races, each rendered in turn, finishers DESC, **defines
  `e_IFRAME`** (e107 = suppress the site header/footer chrome to save space, NOT an
  actual `<iframe>` — kept exactly as legacy did, and defined PRE-`HEADERF` so the
  chrome read sees it); `r=<race>, c=komplet` → that race, all categories, ASC;
  `r=<race>, c=<cat>` → that race + category, ASC.

### `finish_colors` pref — DEFAULT 1, FINISHERS ONLY

- New plugin pref `finish_colors` (admin SETTINGS page, `admin/admin_config.php`):
  `type=boolean`, `data=int`. Read via
  `(int) e107::getPlugConfig('racereports')->get('finish_colors', 1)`.
- **DEFAULT 1** (enforced at the consumer): finish is **coloured by default**,
  UNLIKE `stu_colors` (default 0). ON applies the per-category background
  (`race_report_color()` path, same as online/point) to **FINISHER rows only**.
- **ENDED rows (DNF/DSQ/DNS) are NEVER coloured** (plain `<tr>`) — an **INTENTIONAL
  difference from legacy**, which coloured ended rows too via a buggy
  `$cats['race_category_color']` lookup (a missing key that resolved to no colour
  anyway). Same fix already applied to the online/point reports. The time-cell
  parity is unaffected.
- LAN `LAN_ADMIN_RACEREPORTS_076/077` (EN + SK).

### `finish_categ` pref — DEFAULT 0, adds a CATEGORY-NAME column (screen + export)

- New plugin pref `finish_categ` (admin SETTINGS page, `admin/admin_config.php`):
  `type=boolean`, `data=int`. Read via
  `(int) e107::getPlugConfig('racereports')->get('finish_categ', 0)`.
- **DEFAULT 0** (enforced at the consumer): OFF renders the finish list **exactly as
  today** (no category column). ON adds **ONE** column carrying the racer's category
  **NAME** (`race_category.race_category_name`, looked up by `racer_category_id` via
  the new `race_report_category_name()` helper — the same static-cached `race_category`
  read as `race_report_color()`). **Name only** — the legacy export had a category
  id+name pair; only the human-readable name is reproduced. (An id column, if ever
  wanted, would be a SEPARATE second pref.)
- **Placement: after nationality** (before the status glyph), in BOTH the on-screen
  HTML table AND the CSV/XLS export. The column is built **once** into the neutral
  `racereports_finish_build()` structure: the category cell is inserted into the same
  `$rows` cells the export streams (header spliced after `…_NATIONALITY`, the
  `time/status` cell shifts from index 5 → 6, so `textCols` becomes `array(1, 6)`),
  and `$built['showCat']` drives the render. There is **no second, divergent row
  set** — screen and file stay byte-aligned, same as the rest of the export contract.
- The category cell holds the **RAW** name; escaping happens at the consumer exactly
  like the nationality cell — `toHTML(…, 'TITLE')` on screen, `htmlspecialchars()` on
  XLS, verbatim (BOM-protected) in CSV.
- Header label reuses the existing front LAN `LAN_RACEREPORTS_COL_CATEGORY` (EN
  "Category" / SK "Kategória"); the pref title/help are admin LAN
  `LAN_ADMIN_RACEREPORTS_095/096` (EN + SK).
- **Closes the `timetracker_export` gap:** the legacy `timetracker_export.php` did one
  thing the new `report_finish` export did not — emit the category column. With
  `finish_categ` on, `report_finish`'s export now covers that too, so
  `timetracker_export.php` is **safe to delete at the timetracker decommission**
  (separate task — NOT touched here; this note only records that the gap is closed).

### Grouping nuance vs legacy (status-driven, not set-membership)

The clean report buckets each racer by `pointStatus()` at the finish checkpoint
(point-local status). The legacy page bucketed by the racer-table `racer_active`
flag (an un-marked racer → the report's DNS bucket — see the terminology note
above; `racer_active` is the manual bib-collection flag, not a DNS flag) and by
DNF/DSQ **set membership anywhere** in `race_time`, checked BEFORE computing the
finish time. So a handful of edge cases bucket differently (e.g. a racer with no
start crossing but a DNF flag → legacy DNF, clean `NO_START` → not rendered; a racer
with a clean finish crossing but a DNF flag stamped at a LATER point → legacy DNF,
clean finisher). This is the deliberate consequence of using the cleaner engine
status, and the **finisher TIME-CELL parity is unaffected** (asserted per finisher).
Ended-group parity is **not** asserted — only that the groups are present.

### Deferred: per-row admin actions (edit / PDF icons) — commented, not dropped

The legacy finish page drew, for ADMIN, an extra per-row cell with an **edit icon**
(→ racetiming OR timetracker `race_time` admin) and a **PDF print icon** (→
`timetracker/racer_print.php`). Both are OUT OF SCOPE here (race_time admin = a
separate effort; `racer_print` = timetracker), so they are NOT wired. A clearly
commented `TODO (deferred)` block marks where they would go in `report_finish.php`,
so the trace remains in the code.

### New `finish` e_url key — clean `finish` SEF alias

- `e_url.php` key `finish` → `report_finish.php?r=$1&c=$2` (same contract as
  `online`). The key is plugin-scoped (`e107::url('racereports', 'finish', …)`), so
  it does NOT collide with timetracker's own `finish` key.
- The **SEF alias uses the clean `finish` segment** — timetracker declares no
  `finish` SEF route, so there is no collision.

### Admin: native `finish` screen (one screen, no export / no DataTables)

- New dispatcher mode `finish` (`admin/admin_menu.php` `$modes` + `$adminMenu`
  `finish/finish`), its OWN entry file `admin/admin_finish.php` (one-admin-file-per-
  report rule), extending the shared `racereports_reports_ui`.
- Lists, via the new shared `renderFinishLinks()`: an all-races komplet link
  (`r=komplet&c=komplet`) plus, per race, the all-categories link and one link per
  category — all built with `e107::url('racereports', 'finish', …)`.
- **NO export, NO DataTables** for finish (not needed). LAN
  `LAN_ADMIN_RACEREPORTS_008/080/081/082` (EN + SK).

### Security — legacy holes NOT reproduced

- The legacy `WHERE race_sef='{$race_sef}'` raw-`$_GET`-into-SQL and `extract($racer)`
  are NOT carried over. All input goes through `$tp->toDB()`; race/category ids are
  `(int)`-cast; the **bib/start number stays a STRING** (never `(int)`); racer fields
  are read by explicit array access (no `extract()`); output via
  `$tp->toHTML()/toAttribute()`.
- The legacy start-crossing probe
  (`SELECT … WHERE … race_time_racer_number LIKE '{$racer_number}'`) is replaced by
  the **parametrised** engine call `$clock->hasStart($number)` (no string-concat SQL
  at all).

### Parity — `parity/parity_finish.php`

Admin-gated. Runs the clean engine and the UNTOUCHED legacy `timetracker` over the
same `race_time` rows; for every FINISHER (clean status OK at finish) asserts the
clean time cell (`formatElapsed(elapsedToPoint,1)`) is string-equal to the legacy
finish cell (`substr(secondsToTime(get_racer_time_on_point('finish')['text']), 0,
10)`). Reports DNF/DSQ/DNS counts (ended groups present). The three scenarios
(single category ASC, c=komplet ASC, r=komplet DESC) differ only in order/rank, not
the per-racer time string, so the same comparisons hold in each.

### SCOPE held

racereports ONLY. Engine (racetiming) untouched. No timetracker / racetrack /
race_time / write / freeze changes. Lint clean. LAN EN/SK complete.

## State: finish report in the site navigation

_Updated: 2026-06-26 — racereports-only nav pass; no engine/compute, timetracker,
racetiming, race_time or write changes._

### `e_sitelink.php` — FINISH report in the site navigation (added alongside online + points)

A third builder, `racereports_finish` ("Výsledková listina"), joins the existing
`racereports_online` and `racereports_points` builders (all three kept; the existing
two are UNTOUCHED). It mirrors `racereports_online()` EXACTLY — same data sources
(per-race `SELECT * FROM #race ORDER BY race_name DESC` for the "all categories"
komplet entry, plus `#race AS r, #race_category AS rc WHERE FIND_IN_SET(r.race_id,
rc.race_category_race) ORDER BY race_id, race_category_sef DESC` for one entry per
category), the same per-race vs per-category `link_order`/`link_open` values, and the
same sublink field shape — but pointed at the `finish` e_url key:

- the link is built with `e107::url('racereports', 'finish', …)` carrying
  `race_sef` + `race_category_sef` (the finish route shares the online query
  contract `?r=<race_sef>&c=<race_category_sef>` per `e_url.php`), NOT timetracker's
  key;
- the function name (`racereports_finish`) and its `config()` sitelink entry are
  racereports-scoped (PLUGIN-SCOPED), so they do NOT clash with timetracker's
  still-active finish sitelink during the strangler transition. timetracker is
  UNTOUCHED — Jimmi removes its finish sitelink by hand separately. The `finish`
  SEF alias in `e_url.php` is likewise NOT shared with timetracker.
- **e107 dedup pitfall (handled).** Each entry resolves to a DISTINCT `e107::url()`
  (distinct `{race_sef}/{race_category_sef}` tokens), so `manage_link()` installs
  ALL entries rather than collapsing function-based links that would otherwise share
  `url="#"` to the first entry. Read-only via the db class; the href is escaped with
  `$tp->toAttribute()`.
- **Open question (NOT added):** mirrors `racereports_online` — per-race komplet +
  per-category only, NO single all-races (`race_sef=komplet`, DESC) entry. The
  finish route does support `r=komplet`; left out for online parity unless wanted.

### SCOPE held

racereports ONLY, `e_sitelink.php` only. Engine (racetiming) untouched. No
timetracker / racetrack / race_time / write / freeze changes. Lint clean.

## State: START report (start-point standings / štartová listina) — 2026-06-26

New front report `report_start.php` plus its admin screen, e_url route and sitelink
builder. A clone of `report_finish.php`, but the single per-racer time cell shows the
START TIME-OF-DAY (wall clock at the `start` checkpoint), NOT an elapsed/diff.
racereports ONLY; the racetiming engine is UNTOUCHED (REUSES the existing
`race_clock::timeOfDay()` + `race_clock::formatTimeOfDay()` — the same pair
`racers/startlist.php` uses; no engine method added or changed).

### `report_start.php` — started racers, showing their start time-of-day

- A racer is a **STARTER** when `racer_active` is set (bib marked as collected) AND
  the engine returns a `start` crossing for them
  (`$clock->timeOfDay($number,'start') !== null`). The `racer_active` guard mirrors
  `racers/startlist.php`, which only reads a start time when `$racer['racer_active']`.
  An un-marked racer (`racer_active = false`) is bucketed by this report as DNS — a
  report-level interpretation of the bib-collection flag, NOT a claim that
  `racer_active` *means* DNS (see the terminology note above).
- Time cell = `race_clock::formatTimeOfDay($clock->timeOfDay($number,'start'), 1)` —
  **1 decimal, HARDCODED**, exactly like finish hardcodes `formatElapsed(.,1)`. A
  central, cross-report decimals pref is a LATER change (the `stu_decimals` pattern),
  intentionally NOT introduced here.
- The point is **FIXED to `start`** — no point param (unlike report_point).
- Crossings loaded ONCE via `$clock = (new race_clock())->build();` (the
  `startlist.php` pattern; `build()` returns `$this`).
- **SORT: ALPHABETICALLY BY NAME (ascending) — NOT by time.** This is an INTENTIONAL
  difference from report_point / report_finish (which sort by the time value): a mass
  start gives every racer the same start time-of-day, so a time sort would be
  meaningless. The sort key is the raw `racer_surname + ' ' + racer_firstname`
  (lower-cased) — NOT the rendered HTML name (which may carry team/local markup).
- **Not-started group:** racers with no `start` crossing, or un-marked
  (`racer_active=false`, which this report buckets as DNS — same report-level
  interpretation as finish; the flag itself is the manual bib-collection marker, not
  a DNS flag), are NOT starters and render AFTER the
  starters with an empty time cell, NON-ranked — same grouping approach as finish's
  ended block.
- **Columns:** rank, bib (STRING), name, gender, nationality, start time — the
  start-list/finish shape ("keep it simple"; no per-row admin glyph/actions).
- Modes mirror finish (`?r=<race_sef>&c=<race_category_sef>`): `r=komplet` → all
  races, each rendered, `define('e_IFRAME', true)` pre-HEADERF to suppress the site
  chrome; `r=<race>, c=komplet` → that race, all categories; `r=<race>, c=<cat>` →
  that race + category.

### New pref `start_colors` (DEFAULT 1 at the consumer)

- `admin/admin_config.php` `$prefs['start_colors']` (boolean, `data=int`), next in
  the LAN sequence (`LAN_ADMIN_RACEREPORTS_078`/`079`, EN + SK).
- DEFAULT **1** (coloured), enforced at the consumer:
  `(int) getPlugConfig('racereports')->get('start_colors', 1)`. Its OWN per-report
  pref — same default as `finish_colors` (and unlike `stu_colors`, which defaults 0).
- Per-category background on **STARTER rows only** when on; the not-started rows are
  NEVER coloured (plain `<tr>`) — same rule as online/point/finish.

### `e_url.php` — new `start` key, CLEAN `start` SEF alias

- `start` → `report_start.php?r=$1&c=$2` (same query contract as online/finish).
- The legacy report's OLD URL was `/category/<race>/<cat>` (timetracker's still-active
  `category` alias). We use a CLEAN racereports `start` SEF alias: timetracker's own
  `start` SEF route is COMMENTED OUT and its `category` route uses a DIFFERENT alias,
  so `start` collides with NEITHER. timetracker's commented routes are intentional
  traces — NOT flagged, NOT touched.

### Admin: native `start` screen (its OWN dispatcher mode/file)

- `admin/admin_start.php` — one-admin-file-per-report (like `admin_finish.php`):
  per-race start links (komplet + all-categories + one per category) via the shared
  base's new `renderStartLinks()` (mirrors `renderFinishLinks()` on the `start`
  route). New `start` `$modes` + `start/start` `$adminMenu` entry
  (`LAN_ADMIN_RACEREPORTS_009`). NO export, NO DataTables (like finish).

### `e_sitelink.php` — `racereports_start` builder

- Mirrors `racereports_finish()` EXACTLY (same data sources, same dedup-safe
  distinct-URL-per-entry approach, same sublink field shape) but pointed at the
  `start` e_url key. Function name + `config()` entry ("Štartová listina") are
  racereports-scoped (`racereports_start`) so nothing collides during the strangler
  transition. e107 dedup pitfall handled (distinct `{race_sef}/{race_category_sef}`).

### Security

`$tp->toDB()` on r/c; parametrised db-class queries; NO `extract()`; bib stays a
STRING (never `(int)`); start-crossing read is the engine's `timeOfDay()` (no
string-concat SQL); output via `$tp->toHTML()`/`toAttribute()`.

### SCOPE held

racereports ONLY. Engine (racetiming) untouched — REUSES `timeOfDay` /
`formatTimeOfDay`. No timetracker / racetrack / race_time / write / freeze changes.
LAN EN/SK (start_colors + start strings). Lint clean.

## State: POINT report — THREE visibility groups + admin edit icon (2026-06-26)

`report_point.php` now buckets racers into **THREE** visibility groups (was two,
shown to everyone) and grows an **ADMIN-only edit column** linking the racetiming
`race_time` admin. This consumes the new engine getter `race_clock::crossingId()`
(see racetiming/NOTES.md). racereports + racetiming only; the engine's TIME
behaviour is unchanged (the id is carried additively).

### The three groups (was: NORMAL + ENDED, both shown to everyone)

1. **PASSED** — a usable crossing at the point (`isRanked($diff)`): ranked by
   elapsed ASC, coloured, shown to **EVERYONE**.
2. **ENDED** — DNF/DSQ/DNS (a real ended marker). Shown to **EVERYONE** after the
   ranked block, NOT coloured, no rank.
3. **WAITING** — no crossing here AND no ended marker ("still waited for"). Shown
   **ONLY to ADMIN**; non-admin visitors do not see waiting racers at all.

ENDED vs WAITING is distinguished via the engine, no re-query: ENDED when
`endedAt($number,$point) !== ''` OR `isDnf` OR `isDsq`; otherwise (not ranked, no
marker) WAITING. This is exactly the separation `diffOnPoint` already encodes
(ended markers vs the bare `---` / no-crossing case). "DNF" in the doc applies
equally to DSQ and DNS.

### Admin edit icon (resolves the earlier deferral — it BELONGS)

For ADMIN only, an extra trailing column on every row that **HAS a stored crossing**
(`crossingId($number,$pointId)` non-null — PASSED rows, and any ended/waiting row
that does have a stored crossing) carries an edit icon linking, in a NEW window:

```
e_PLUGIN_ABS . "racetiming/admin/admin_config.php?action=edit&id=" . (int) $raceTimeId
```

Built with `toGlyph('fa-edit')` + `toAttributes()` (`target=_blank`, `title=LAN_EDIT`).
Rows with no stored crossing (pure waiting / ended-elsewhere, `crossingId` null) get
an empty cell — nothing to edit. The whole column is wrapped in `if (ADMIN)`, so
non-admins see the public columns only (rank, bib, name, time) and only the
PASSED + ENDED rows. The id is the ONLY value placed in the URL and is `(int)`-cast
there; the bib stays a quoted string, all cells go through `$tp->toHTML()`/
`toAttribute()`.

### SUPERSEDES the earlier point-page notes

- **Supersedes** the "**ENDED rows shown in the POINT report**" intentional-fix note
  (and the in-file comment that called showing the waiting racers an "intentional
  fix"). Per the documentation that was WRONG: the legacy page bucketed into
  NORMAL + ENDED and showed BOTH to everyone, conflating genuinely-ended racers with
  those still being waited for. The correct behaviour is the three groups above —
  PASSED + ENDED public, WAITING admin-only. The DSG→DSQ time-cell fix is unaffected
  and still stands.
- **Supersedes** the "**Admin-only cells OMITTED (out of scope)**" note: the admin
  edit icon now BELONGS and is wired (per the documentation that resolved the
  deferral). The legacy ✅/❌ crossing probe is still not reproduced — the edit icon
  is the admin affordance.

### Parity

`crossingId` parity is asserted in both harnesses (engine self-test + DB-backed
`parity_check.php`, which now compares `crossingId` to the legacy
`SELECT race_time_id … LIMIT 1` per (racer, point) and reports matched/null counts).
The time-cell parity is unchanged (the engine time methods were not touched).

### SCOPE held

racereports (`report_point.php`) + racetiming (additive `crossingId`) + the parity
harness. Engine TIME behaviour unchanged. Security: bib stays a string,
`$tp->toDB/toHTML/toAttribute`, the id is `(int)`-cast in the URL only. Lint clean.
No new front LAN string (reuses core `LAN_EDIT`, with a defensive fallback).

---

## State: FINISH + START reports gain CSV/XLS export (2026-06-26)

`report_finish.php` and `report_start.php` now export via the shared
`includes/race_export.php` module, and their admin link screens
(`admin_finish.php` / `admin_start.php`, via the shared base
`renderFinishLinks()` / `renderStartLinks()`) grow per-row **CSV + XLS** buttons.
This mirrors EXACTLY how `report_stu` already does it (the `?export=csv|xls` handler
at the top + `race_export::csv|xls` + per-row admin buttons). `race_export` itself is
**reused untouched**. racereports ONLY — no engine / timetracker / racetiming change.

### PART A — `?export` on the two reports (mirrors stu)

Each report's row build was refactored into a NEUTRAL structure consumed by BOTH the
HTML table and the export, so there is no second/divergent row set:

- `racereports_finish_build()` / `racereports_start_build()` return
  `headers` + `rows` (already-formatted display cells) + `meta` (per-row HTML-only
  decoration: `ended` flag, per-category `color`) + `textCols` (and finish also
  `tableStyle`/`tdStyle` for the DESC komplet view).
- `racereports_finish_render_table()` / `racereports_start_render_table()` re-emit
  the **byte-for-byte** pre-refactor HTML from those same cells (finisher/starter
  rows keep their `<td $tdStyle>` / coloured-`<tr>` form; ended / not-started rows
  keep the plain `<td>` empty-rank form). The on-screen output is unchanged — modes
  `r=komplet`, `c=komplet`, `c=<cat>` all render exactly as before. The finish/start
  tables still have **no on-screen header row**; `headers` is used ONLY for the
  CSV/XLS header line.
- Export is handled at the TOP, before `HEADERF` / any output / `e_IFRAME`: if
  `?export` is `csv`|`xls` it builds the same structure for the requested scope and
  streams it via `race_export::csv|xls`, which exits. To keep that possible, the LAN
  load + engine/report requires + `$report`/`$clock` setup were moved ABOVE `HEADERF`
  (the same ordering stu uses); `e_IFRAME` is still defined pre-`HEADERF` for the
  HTML `r=komplet` path.
- `$textCols` forces the **start number (bib)** and the **time column** to TEXT, so a
  spreadsheet keeps `"0042"` and the formatted time string verbatim (same as stu).
  finish columns: rank, bib, name, nationality, status-glyph, time/DNF/DSQ/DNS
  (`textCols = [1,5]`). start columns: rank, bib, name, gender, nationality, start
  time-of-day (`textCols = [1,5]`). The exported rows include the finishers + the
  DNF/DSQ/DNS rows (finish) and the starters + the not-started rows with an empty
  time cell (start) — matching exactly what the screen shows.
- **Name cell:** finish/start render the name via `getRacerName()` (pre-built,
  escaped HTML, echoed raw today). To keep the HTML byte-identical AND use ONE cell
  set, that same string is the export cell, so a name carries markup only when a team
  suffix is present. Two new front LAN keys were added for the export header line:
  `LAN_RACEREPORTS_COL_NAME`, `LAN_RACEREPORTS_COL_STATUS` (English + Slovak, with
  defensive fallbacks in each report).

### EXPORT SCOPE choice (stated)

Export is offered for a **single race** only:
- `r=<race>&c=<cat>` → that category (rows concatenated if the sef resolves to more
  than one category, in screen order);
- `r=<race>&c=komplet` (or empty) → that race, all categories (one table).

`r=komplet` (**ALL races**, many tables) is **NOT** exported: a single CSV/XLS for
many race tables is awkward, so the admin "all races (komplet)" link carries **no**
export buttons and a manual `report_finish.php?r=komplet&export=csv` simply falls
through to the normal HTML page. This matches stu's single-scope behaviour.

### PART B — export buttons in the admin link screens

The shared base `admin_report_ui.php` gained three helpers (mirroring
`renderStuLink`'s flex row + `btn-csv`/`btn-excel` look):
- `exportButtons($exportBase)` — the CSV + XLS `btn-group`;
- `reportExportBase($reportFile, $params)` — builds the plugin-path report URL with
  `r`/`c` via `http_build_query` (so both params survive regardless of SEF; the
  report pages read `$_GET['r']`/`$_GET['c']` directly), the dedup-safe way (distinct
  params per race / per category) — the caller appends `&export=csv|xls`;
- `linkItemWithExport($url, $label, $sef, $exportBase)` — a flex list row: report
  link (still `e107::url('racereports','finish'|'start', …)`) on the left, the
  export buttons on the right.

`renderFinishLinks()` / `renderStartLinks()` now emit those rows for the per-race
"all categories (komplet)" link AND each per-category link (all single-race scopes).
The per-race panels in `admin_finish.php` (`finishPage`) / `admin_start.php`
(`startPage`) are unchanged — they already call these base helpers. `renderStuLink`
is left untouched as the reference. The admin screens stay gated on `getperms('P')`.

### SCOPE / security held

racereports ONLY. `$tp->toDB` on r/c, bib stays a STRING, ids `(int)`-cast, output
escaped (`toHTML`/`toAttribute`), export cells already escaped/formatted by the
report, no `extract()`. The export filename slug is sanitised
(`preg_replace('/[^A-Za-z0-9_-]/','', …)`) before the Content-Disposition header.
`race_export` reused as-is. Lint clean.

---

## report_aktualne — the FULL per-race results matrix (Phase 1: on-screen report only)

Clean equivalent of `timetracker/aktualne.php` + `timetracker/classes/
timetrackerArchive_class.php`, ported onto the racetiming engine. **Phase 1 is the
on-screen report ONLY** — no archive writing yet (Phase 2). ONE race, EVERY racer ×
EVERY checkpoint in one wide table.

### Columns / sections (faithful to the legacy archive)

`Por. | Meno | Kat. | Čas | <one column per checkpoint except 'start', in
race_point_order ASC — the last of which is the 'finish' checkpoint> | Rank v
kategórii`. Three row-blocks in ONE table, rendered NORMAL → DNF → DSQ (legacy
`printrows` 0/1/2). Rows are emitted in **racer_number order** (as the legacy
`get_table_content` does); the `Por.` value carries the computed rank and DataTables
sorts the visible table by col 0 (Por.) asc on load.

### The two distinct truncations (kept EXACTLY as legacy — proven by parity)

- **every per-checkpoint cell**, *including the `finish` checkpoint column*, =
  elapsed start→checkpoint **truncated to HH:MM** (legacy `substr(text, 0, 5)`);
- the dedicated **`Čas` column** = the finish elapsed **truncated to HH:MM:SS**
  (legacy `substr(text, 0, 8)`), or `DNF` / `DSQ`.

NOTE: the legacy archive truncates the *finish CHECKPOINT cell* to HH:MM like all
the other checkpoint columns — only the dedicated `Čas` column is HH:MM:SS. The task
brief's phrase "finish HH:MM:SS" refers to that **Čas** column (which holds the
finish time), NOT the finish *checkpoint* column. We reproduce the legacy exactly:
finish checkpoint column = HH:MM, `Čas` = HH:MM:SS. (If these had been "fixed" to
match the brief's literal wording, the parity harness would FAIL — legacy is the
contract.)

### DNF / DSQ

- DNF racers ranked by the **furthest checkpoint reached**:
  `ordertime = (checkpointCount - dnf_point_order) * 1e6` (furthest sorts first);
  after the DNF marker point their later mid-time cells are **BLANK** (legacy
  `$ended` flag); `Čas` = `DNF`. The marker point comes from the engine's new
  additive `race_clock::endedPoint($number, 'DNF')` (see racetiming/NOTES.md).
- DSQ: `Čas` = `DSQ`.
- `Rank v kategórii` (catorder) is **BLANK** for DNF/DSQ.

### Architecture — RETURNS html (Phase-2 ready)

The table building lives in the PURE include `includes/aktualne_build.php`:
`racereports_aktualne_build($raceId, $clock, $report)` **RETURNS**
`['html' => <table html>, 'data' => <structured rows>]` — it does NOT echo. The page
`report_aktualne.php` only bootstraps + echoes `['html']`; **Phase 2** (archive
writing) will capture the SAME string and persist `['data']` (mirrors
`raceevent_event_overview()`'s return-the-HTML shape, and the legacy `aktualne.php`
which captured `$text` into `race_archive_html`). The same pure include is reused by
`parity/parity_aktualne.php` — no page chrome runs there. `e_IFRAME` is defined
BEFORE `HEADERF` (legacy parity).

### Param + name prefs

- `?p` = **race_id** (int-cast), NOT a sef — same contract as `report_stu.php`
  (legacy `WHERE race_id = $_GET['p']`). Unknown/missing → error panel (mirrors the
  legacy "Neznámy / Neurčený pretek").
- The name format is the archive-specific `"Surname Firstname (number) (team/local)"`
  where the **bib number is part of the name**. The `display_team` / `display_local`
  / `text_local` prefs the legacy archive read from `e107::pref('timetracker')` now
  live in the **`racers` plugin** store (`e107::pref('racers')`, same store
  `race_report::getRacerName` uses — the racer model moved). Surname/firstname/number
  are escaped via `toHTML` (legacy left surname/firstname raw — a stored-XSS surface;
  for plain-ASCII names `toHTML` is a no-op so display parity holds).

### DataTables — basic search ON

Uses the racereports CENTRAL loader `race_report_load_datatables()` (the bs5 build
racereports already owns — NO SearchBuilder, NO CDN). `assets/datatables/init.js`
gained a `#report_aktualne` init: `paging:false, info:false, searching:TRUE`
(the basic Search box per the screenshot), `order: [[0,'asc']]`. Time columns (`Čas`
+ each checkpoint cell) carry a numeric `data-order` (raw elapsed seconds) so HH:MM /
HH:MM:SS text never sorts lexicographically (same trick as `report_stu`); blank /
DNF / DSQ / `---` cells get a large sentinel so they sort last. **Not colored** (the
legacy archive has no per-category background — none is added).

### e_url + admin + sitelink

- `e_url.php` key **`aktualne`** → `report_aktualne.php?p=$1` ({race_id} token, like
  stu). **SEF alias is `aktualne-results`, NOT the bare `aktualne`**: contrary to the
  task brief's premise, `timetracker/e_url.php` STILL declares an **ACTIVE** `aktualne`
  SEF alias (→ `timetracker/aktualne.php`), so claiming the bare `aktualne` alias here
  would create an active SEF collision. This follows the SAME strangler pattern as
  `stu` (`stu-results`) / `point` (`point-times`): the clean `aktualne` SEF alias can
  be claimed once timetracker's route is retired. The plugin-scoped KEY stays
  `aktualne`. (timetracker's *commented* routes elsewhere are intentional traces — not
  flagged; this one is genuinely active.)
- `admin/admin_aktualne.php` — own dispatcher mode `aktualne` (one admin file per
  report), per-race links to `report_aktualne.php?p=race_id` via the shared base's
  new `renderAktualneLink()`. Phase 1 = plain link (no export buttons — CSV/XLS +
  archive are Phase 2). Registered in `admin_menu.php` (`LAN_..._100`).
- `e_sitelink.php` — `racereports_aktualne` builder: one entry per race, DISTINCT URL
  each ({race_id} token), racereports-scoped name so it does NOT clash with
  timetracker's still-active aktualne route during the strangler transition.

### Parity (proven, not assumed)

`parity/parity_aktualne.php` (DB-backed, admin-gated) runs the clean build AND the
LEGACY `timetrackerArchive` (imported UNTOUCHED) over the SAME `race_time` rows and
asserts, per racer: each per-checkpoint mid-time cell (incl. blank-after-DNF), the
`Čas`/finish cell, the `Por.` order, the DNF furthest-point order, the DSQ section,
and catorder. The legacy archive reads `$_GET['p']` directly and never resets its
public statics, so the harness sets `$_GET['p']` per race and resets those statics
between races. Two divergences are flagged as **INTENTIONAL FIXES** (never
auto-passed):
- the legacy `'DSG'` typo (a DSQ racer's no-crossing mid-cell) → clean `'DSQ'`;
- a racer flagged BOTH DNF and DSQ is double-printed by the legacy (in both blocks);
  the clean build lists it once (DNF) — the legacy-only DSQ duplicate is reported,
  not counted as a mismatch.

### Scope / security held

racereports + ONE thin ADDITIVE racetiming accessor (`endedPoint`, see
racetiming/NOTES.md — no existing engine method changed). `?p` `(int)`-cast; bib
stays a STRING; SQL via `$tp->toDB()`; output escaped (`toHTML`/`toAttribute`); NO
`extract()`, NO raw-`$_GET`-into-SQL. Engine reads `race_time` only; no writes. LAN
EN/SK added (front: `LAN_RACEREPORTS_AKT_*`; admin: `LAN_ADMIN_RACEREPORTS_100/101`).
Lint clean.
## State: DOBEH report (checkpoint ARRIVALS board) — 2026-06-26

Clean port of `timetracker/timetracker_dobeh.php` into racereports. A LIVE board
for checkpoint staff / a big screen: the racers with a valid crossing at ONE
checkpoint, sorted by ARRIVAL (latest crossing FIRST), the rank column counting
DOWN (newest row = current total, first arrival = 1), each row's category cell
showing `"<category> — <Nth>"` where Nth is the in-category rank by ELAPSED time at
that checkpoint. This is DISTINCT from finish/online (which sort by elapsed and
count UP). Legacy left untouched.

### `report_dobeh.php` — clean equivalent of `timetracker/timetracker_dobeh.php`

`?r=<race_sef>&p=<race_point_sef>`. `r=komplet` → every race × each checkpoint
(skip start/finish); `p=komplet` → every checkpoint of the race (skip
start/finish); otherwise the one board. `e_IFRAME` defined + the refresh `<meta>`
emitted BEFORE `HEADERF` (same pre-header spot the online report uses), so the all
view renders without site chrome.

Presentation only — all time math is the racetiming engine:
- **Arrived set** = `race_report::isRanked($diff)` over `diffOnPoint($clock,
  $number, $point)` (a usable, non-ended crossing here) — exactly the legacy
  `time>0 && text!='' && empty(ended)` test.
- **Time cell** = `$diff['text'] . " " . $diff['ended']` (trailing space when no
  flag), i.e. the ELAPSED start→point string formatted at tenths — identical to the
  point/finish reports. (Confirmed against legacy `get_racer_time_on_point`, which
  `substr(secondsToTime,0,10)`-cuts the same elapsed; the finish parity run already
  proves that cut equals `formatElapsed(elapsed,1)`.) NOT the time-of-day.
- **Arrival sort key** = `race_clock::timeOfDay($number,$point)` — the ABSOLUTE
  wall-clock crossing epoch (the legacy page sorted on the same `race_savedtime`).
  **No engine change was needed**: `timeOfDay()` already exposes exactly this
  absolute crossing time, so the task's "thin additive accessor" was confirmed
  unnecessary and `timeOfDay` is REUSED (no racetiming edit).
- **In-category rank** = `dobeh_compute_category_ranks()`: group arrived by
  `racer_category_id`, sort each group by elapsed (`difftime`) ascending, number
  1..N — keyed by bib (legacy `compute_category_ranks` parity). Category name +
  colour come from a static `race_category` lookup (`dobeh_category_info`), the same
  source the legacy `timetracker::$cats` held.
- **Category cell** = `name . ' — ' . rank . '.'` (legacy em-dash + trailing dot).
- **Count-down rank** = column 1 starts at `count($arrived)` and decrements (newest
  row = total, oldest = 1), reproducing the legacy `$display_order--`.
- **Colour**: legacy DID colour every arrived row by its category colour — REPRODUCED
  (row `background-color` via `toAttribute`). (Unlike point/finish, which leave ENDED
  rows uncoloured — dobeh shows ONLY arrived rows, all coloured.)

### Refresh pref CHOICE — own `dobeh_refresh_interval` (NOT reuse online's)

The dobeh board gets its OWN racereports pref `dobeh_refresh_interval` (default 0 =
no auto-refresh, `?refresh` URL param always wins, even `?refresh=0`). Chosen over
reusing `online_refresh_interval` because the arrivals board and the online
standings board are independent live surfaces an operator paces differently, and
because the codebase convention is per-report prefs (`stu_colors`, `finish_colors`,
`start_colors`). It is a racereports pref — the legacy page read another plugin's
pref (`racerfid.refresh_interval`); that cross-plugin read is NOT carried over.

### New `dobeh` e_url key — DISTINCT `dobeh-board` SEF alias

`config['dobeh']` → `report_dobeh.php?r=$1&p=$2` (same contract as `point`).
timetracker's `dobeh` SEF route is **STILL ACTIVE** (NOT commented — the task's
"confirm commented/absent" assumption did not hold), so the clean `dobeh` alias
would COLLIDE. The route therefore uses a DISTINCT alias `dobeh-board`, exactly as
`point`→`point-times` and `stu`→`stu-results` do during the strangler transition;
the clean `dobeh` SEF alias can be claimed once timetracker's route is retired.

### Admin: native `dobeh` screen (its OWN dispatcher mode/file)

`admin/admin_dobeh.php` (mode `dobeh`) — per race, the per-checkpoint arrivals-board
links (komplet + one per checkpoint, skip start/finish) via the shared base's new
`renderDobehLinks()`. Wired into `admin_menu.php` (`$modes['dobeh']`,
`'dobeh/dobeh'`). NO export, NO DataTables (live board).

### `e_sitelink.php` — `racereports_dobeh` builder

Per race+point (one entry per checkpoint, skip start/finish), modelled on
`racereports_points` but on the `dobeh` route, distinct URL per checkpoint
(dedup-safe). Function/entry name plugin-scoped so it does not clash with
timetracker's still-active dobeh sitelink.

### Parity — `parity/parity_dobeh.php`

Admin-gated. For every race × checkpoint (skip start/finish) it asserts the clean
report reproduces the legacy `timetracker_dobeh` board on: (1) the arrival-DESC bib
SEQUENCE, (2) the per-bib in-category rank MAP. The count-down rank column (total..1)
is a pure function of the sequence, so equal sequence ⇒ equal rank column. Both sides
are driven from the SAME `fetchRacers` iteration order so equal-key ties resolve
identically — the comparison isolates the sort/rank LOGIC, not PHP's tie handling.
Legacy `get_racer_time_on_point` + `timetracker::$times[...]['race_savedtime']` are
read UNTOUCHED; `GEN_RESULTS=0` disables the legacy freeze. The two crossing-time
parsers differ in ABSOLUTE epoch but are monotonic over the same timestamps, so only
the ORDER is asserted (not the epoch). Includes the `dobeh` e_url route-integrity
check.

### SCOPE / security held

racereports ONLY (racetiming untouched — `timeOfDay` reused, no accessor added).
`$tp->toDB` on r/p, bib stays a STRING, race ids `(int)`-cast, output escaped
(`toHTML`/`toAttribute`), no `extract()`. No export, no DataTables. Lint clean. LAN
EN/SK (admin menu/pref/panel + front category column).

### LAN admin dup-key cleanup (100/101/102)

The aktualne / custom / dobeh admin work each grabbed the same LAN numbers in the
admin language files, unaware of each other, so `LAN_ADMIN_RACEREPORTS_100/101/102`
were each DEFINED MORE THAN ONCE in BOTH `English_admin.php` and `Slovak_admin.php`.
PHP's array build silently keeps the LAST definition, so the shadowed strings were
lost and several admin labels rendered the wrong text (all three of the aktualne/
custom/dobeh nav captions resolved to `_100`="Full results"; the aktualne and dobeh
panel headings resolved to `_101`="Segment report").

Fixed by renumbering the colliding strings into the free range (highest used was
104) so EVERY `LAN_ADMIN_RACEREPORTS_NNN` is defined EXACTLY ONCE, grouped in clean
per-screen blocks — aktualne 100-101, custom 102-106, dobeh 107-109:

| New | String (EN)                              | Screen          | Was (dup) |
|-----|------------------------------------------|-----------------|-----------|
| 100 | Full results                             | aktualne nav    | 100 kept  |
| 101 | Full per-race results (all checkpoints)  | aktualne heading| 101 kept  |
| 102 | From (Od)                                | custom          | 102 kept  |
| 103 | To (Do)                                  | custom          | 103 kept  |
| 104 | Open segment report                      | custom          | 104 kept  |
| 105 | Segment (Od-Do)                          | custom nav      | dup 100   |
| 106 | Segment report (between two points)      | custom heading  | dup 101   |
| 107 | Arrivals (dobeh)                         | dobeh nav       | dup 100   |
| 108 | Arrivals board                           | dobeh heading   | dup 101   |
| 109 | arrivals - all checkpoints               | dobeh           | dup 102   |

Same numbers renumbered in BOTH EN and SK (Slovak mirrors the keys 1:1; values
unchanged, just renumbered). All callers moved with their string: `admin_menu.php`
custom caption `_100→_105` and dobeh caption `_100→_107`; `admin_custom.php` panel
heading `_101→_106`; `admin_dobeh.php` panel heading `_101→_108`;
`admin_report_ui.php` dobeh-komplet link `_102→_109`. The aktualne nav `_100`,
aktualne heading `_101`, and custom `_102/_103/_104` callers were already correct
and stayed. Owning screen for each string was determined from its code usage
(e.g. `admin_report_ui.php:466` `_102` lives in `renderDobehLinks()` "komplet: all
checkpoints", so it owns the dobeh string, not custom's "From (Od)").

Verified: `uniq -d` on each admin LAN file returns nothing; EN and SK have the
IDENTICAL key set; every `LAN_ADMIN_RACEREPORTS_*` referenced in racereports admin
code resolves to exactly one definition; front LAN files were already clean
(unaffected). `php -l` clean on all touched files. Labels only — no behaviour change.

- Admin report-link screens render link lists as TABLES, not Bootstrap list-groups.
  The admin theme's `a.list-group-item { color:#c6c6c6 }` rule made the report links
  unreadable (light-grey on a light panel). Single central change in the shared base
  `admin/admin_report_ui.php`: `linkItem()`/`linkItemWithExport()`/`emptyItem()`/
  `renderStuLink()` return `<tr><td>` rows with the link as a PLAIN `<a>` (no
  `list-group-item` class → theme's normal link color), and `panel()` wraps them in
  `<table class="table table-striped table-bordered">` instead of `<div class="list-group">`.
  Covers online/point/finish/start/stu/dobeh/aktualne at once (they all build links via
  this base). Same URLs/targets/labels/export buttons — only the container changed.
  `panel()` got an `$asTable=false` flag for the `custom` screen (its body is a GET
  picker form, not a link list). Theme untouched.

## State: central `result_decimals` pref (display precision for RESULT reports)

New plugin pref `result_decimals` (`admin/admin_config.php`, `type=number`,
`data=int`, `writeParms min=0&max=3`) controls the displayed sub-second decimal
places for the RESULT reports. DEFAULT 2 is enforced at the consumer
(`get('result_decimals', 2)`), so an unset pref renders two decimals everywhere.
LAN keys `_110` (title) / `_111` (help), EN+SK, next in sequence after `_109`.

DISPLAY-ONLY, TRUNCATE not round: the underlying timing DATA always stays at 3
decimals (ms). `race_format::formatElapsed`/`race_clock::formatTimeOfDay` were NOT
changed — they already TRUNCATE the displayed string via the same `substr(HH:MM:SS.mmm,
0, 8 + 1 + N)` cut (verified in racetiming/includes). So a smaller `result_decimals`
just cuts trailing digits off the rendered string; it never rounds and never reaches
the data. (formatTimeOfDay's only `round()` is the 3rd-decimal ms field reproducing
the legacy start-time formatter byte-for-byte — i.e. the "data at 3 ms" itself — the
`$decimals` reduction on top is a plain substr truncation, same as formatElapsed.)

Reports switched OFF the hardcoded `1` to this pref (read once per render function via
`$resDec`, the same place stu reads `$dec`):
- online / point / dobeh: pass `$resDec` as the explicit 4th arg to
  `diffOnPoint($clock,$number,$point,$resDec)`. The engine helper's own default is
  LEFT at `1` (`includes/race_report.php:diffOnPoint`) — reports pass the value
  explicitly so the helper stays pref-agnostic.
- finish: `report_finish.php` `formatElapsed($elapsed, $resDec)` (was `1`).
- custom: `report_custom.php` `formatElapsed($seg, $resDec)` (was `1`).
- start: `report_start.php` `formatTimeOfDay($epoch, $resDec)` (was `1`). start shows
  time-of-day, so the decimals apply to the sub-second part (truncated by the same cut).

LEFT ALONE:
- stu keeps its OWN `stu_decimals` (default 0) — independent, NOT switched to
  `result_decimals`.
- aktualne (`includes/aktualne_build.php`) STAYS hardcoded `1` — it is the overview
  matrix (mid-times / finish cells of the live grid), not a result list. A code comment
  there records why it is deliberately excluded from `result_decimals`.

SORT SAFETY (unchanged, verified): `result_decimals` is display-only and never feeds a
sort key. usorts rank by the raw `difftime`/`segtime` float; DataTables `data-order` on
stu/aktualne carries the full-precision raw seconds. Changing the displayed decimals
leaves every numeric sort key untouched, so row order is identical at any setting.

`php -l` clean on all touched files. No PR.
_2026-06-27: `report_finish.php` now renders an ADMIN-only trailing edit icon (fa-edit, target=_blank, opens racetiming race_time admin) mirroring report_point.php — keyed on the FINISH crossing (`crossingId(number,'finish')`), HTML-only (not in the CSV/XLS export), empty cell where no crossing across finisher/DNF/DSQ/DNS groups; engine untouched._

_2026-06-27: `report_point.php` restored the legacy ADMIN-only ✅/❌ status column (between name and time, before the edit cell) via `point_admin_status_cell()` — literal UTF-8 emoji (NOT FontAwesome), driven purely off `race_time_id`/`crossingId` already on each row (no extra query), present on every admin row across all three groups (passed/ended/waiting); non-admins unchanged; engine untouched._

## Admin export-button layout — compact `link | CSV | XLS` row (2026-06-27)

The FINISH and SUT admin link screens both render per-row CSV/XLS export buttons.
After the earlier `ul/li` → `<table>` change they showed **tall empty rows** with
the CSV/XLS buttons misaligned beside the report link: the buttons sat together in a
single `btn-group` cell whose block stretched the link cell's height. Both screens
build their export markup ONLY through the shared base `admin/admin_report_ui.php`
(finish → `renderFinishLinks()` → `linkItemWithExport()` → the export cells; stu →
`renderStuLink()`), so the fix is **central, in that one base file** — no per-screen
markup is built outside it. (`start` shares `linkItemWithExport()` and inherits the
same compact layout; the orphan `admin/admin_reports.php` is NOT wired into the
dispatcher `$adminMenu` and is left untouched.)

### The fix

- `exportButtons()` (a single `<span class='btn-group'>` cell) was replaced by
  **`exportButtonCells($exportBase)`**, which returns **TWO SEPARATE `<td>` cells** —
  the CSV button in its own column, the XLS button in its own column. Each cell is
  narrow (`width:1%;white-space:nowrap;`) and `vertical-align:middle`, so the row
  collapses to the normal admin-table height.
- `linkItemWithExport()` and `renderStuLink()` now both emit a **three-column row**:
  `<tr><td>{link}</td>{CSV td}{XLS td}</tr>` (the link cell is also
  `vertical-align:middle`). Same buttons, same `btn-csv`/`btn-excel` look, same export
  URLs (`…&export=csv` / `…&export=xls`) — only the MARKUP layout changed.
  `renderStuLink()` no longer hand-builds its own button span; it reuses the shared
  `exportButtonCells()` (single source of truth for both screens).

### Link-only rows stay clean — chosen approach: **Option B**

Link-only screens (online / point / dobeh / aktualne, plus the finish/start
"all races (komplet)" link, which uses `linkItem()` in its OWN single-column panel)
are **kept as single-column tables** — `linkItem()` is unchanged. Only the export
rows get the 3-column layout. The one mixed case is a finish/start per-race panel
that has the export-`komplet` row but a race with **no categories**: there the muted
"no categories" fallback now spans all 3 columns via `emptyItem($label, 3)`
(the `$colspan` arg defaults to 1, so every link-only caller is byte-unchanged), so
that table's columns stay aligned. The readable plain-`<a>` link color from the prior
`ul/li`→`table` fix is preserved (no `list-group-item` grey).

### Scope / verify

racereports admin ONLY; no theme changes; `php -l` clean. finish admin: each
category row = link + CSV + XLS on one compact row, no tall empty rows, export still
downloads. stu admin: each track row = same compact `link | CSV | XLS`. `r=komplet` /
link-only rows stay clean (single column). Other link-only admin screens
(online/point/start/dobeh/custom/aktualne) unchanged and readable — `start`'s export
rows additionally inherit the same compact layout as finish, by construction.

## State: settings screen grouped into 4 prefs tabs (2026-06-27)

`admin/admin_config.php` ONLY — pure VISUAL grouping of the existing 8 prefs into 4
native e_admin_ui prefs tabs. No logic change: same titles/help/type/data/writeParms,
same defaults, same native `PrefsSaveTrigger` save path, same read-back via
`e107::getPlugConfig('racereports')`.

### Lite prefs-tab mechanism (confirmed against the vendored Lite 2.4 handlers, not guessed)

- **Property:** `protected $preftabs` (lowercase). `ehandlers/admin_ui.php:3050`
  `protected $preftabs = array();` and `:3328` `public function getPrefTabs() { return
  $this->preftabs; }`. The prefs form consumes it at `:7951`
  `'tabs' => $controller->getPrefTabs()`.
- **Entry shape:** flat `'key' => 'Caption'` (caption may be a LAN constant).
  `form_handler.php:7818` `foreach($data['tabs'] as $tabId => $label)` →
  `defset($label,$label)`. Live precedent: `eplugins/timetracker/admin/admin_config.php`
  `protected $preftabs = array(LAN_TR_PREFTAB_GENERAL);`.
- **Per-pref opt-in:** `'tab' => '<key>'` on each pref. `form_handler.php:7886`
  `if($tab !== false && varset($att['tab'], 0) !== $tab) { continue; }` — a STRICT
  `!==` match, which is exactly why string keys (a pref bound to a tab's identity) are
  the right choice. (`$tabs`/`addTab()` is the separate EDIT-form mechanism used by
  table screens like `racetrack`; the prefs-only screen here uses `$preftabs`.)

### Grouping (string tab keys — bound to tab IDENTITY, not order)

Tab list order (= display order) defined on `racereports_main_ui::$preftabs`:

| key       | caption SK / EN          | prefs                                        |
|-----------|--------------------------|----------------------------------------------|
| `dec`     | Desatiny / Decimals      | `result_decimals`, `stu_decimals`            |
| `colors`  | Podfarbenie / Coloring   | `stu_colors`, `finish_colors`, `start_colors`|
| `refresh` | Obnovenie / Refresh      | `online_refresh_interval`, `dobeh_refresh_interval` |
| `custom`  | Ostatné / Other          | `finish_categ`                               |

Each pref carries `'tab' => '<key>'`. Because the keys are STRING identities (not
`'tab' => 0`), reordering the tabs later only edits the `$preftabs` list — no pref is
touched. Captions are NEW LAN constants `LAN_ADMIN_RACEREPORTS_112..115` (EN + SK, next
free range; highest previously in use was 111).

### Verify

`php -l` clean (all 3 files). Settings screen shows 4 tabs in order
**Desatiny | Podfarbenie | Obnovenie | Ostatné**, each holding exactly its assigned
prefs. Saving from any tab persists ALL prefs (the native form posts every field across
tabs in one submit). No pref changed behaviour; defaults intact.

---

## report_number — single-racer progression (one racer across ALL points)

_2026-06-27._ NEW report: how ONE racer passed the WHOLE course. Input is a single
bib (`?n=<bib>`); the racer has exactly one track (`racer.racer_race_id`), so the bib
alone determines the track and its checkpoints — **no race param, no komplet, no track
param**. racereports-only; **no engine change** (all needed reads already existed).

### Pieces

- **e_url** (`e_url.php`): plugin-scoped KEY `number`, SEF alias the CLEAN singular
  **`racer`** → `racer/{race_number}/` → `report_number.php?n=$1`. No collision:
  timetracker's own `number` route is under the DISTINCT plural alias `racers`
  (`racers/number/...`), and the racers plugin's routes are also plural — the singular
  `racer` segment is unused. The `{race_number}` token carries the bib STRING (leading
  zeros preserved — never int-cast).
- **Admin** (`admin/admin_number.php` + dispatcher mode `number/number`): ONE screen
  listing EVERY bib from `#racer` (`ORDER BY racer_number`), each row `<bib> — <name>`
  → a LINK to `report_number.php?n=<bib>` opening in a NEW TAB. Name via the shared
  `race_report::getRacerName` (the one place). Built on the shared
  `admin_report_ui.php` base (readable-table styling) via two NEW base helpers,
  `getRacersByNumber()` + `renderNumberLink()`. NO export buttons (navigation list
  only). Menu caption `LAN_ADMIN_RACEREPORTS_116`, panel heading `_117`. The name is
  already-safe `getRacerName` HTML, so `renderNumberLink()` inserts it raw (does NOT
  re-escape, unlike `linkItem()`) and escapes only the bib.
- **Report** (`report_number.php`): bootstraps like the other report pages (engine by
  path + `race_report`, `$clock = new race_clock(); $clock->build();`). Resolves the
  racer from `#racer` by `racer_number = bib` (parametrised, bib STRING). Unknown bib →
  error panel (`LAN_RACEREPORTS_NUM_UNKNOWN_BIB` = "Neznáme štartové číslo") + FOOTERF.
  Heading `<h2><bib> — getRacerName></h2>`.

### Columns (one row per point, in race_point ORDER — start/finish INCLUDED)

`fetchCheckpoints()` returns race_point_order DESC (finish-first); this report
re-sorts ASCENDING so the course reads forwards (start → finish). Times use the
central `result_decimals` pref (default 2; these ARE result times, display-only).

| Col | Header (SK) | Source |
|-----|-------------|--------|
| Kontrola | `race_point_name` (escaped) |
| Čas dňa  | `race_clock::timeOfDay(bib, code)` → `race_clock::formatTimeOfDay(.,$resDec)`; blank if no crossing |
| Medzičas | `race_clock::elapsedRaw(bib, code)` → `race_format::formatElapsed(.,$resDec)`; blank for the start row and where no usable crossing |
| Úsek     | `race_clock::elapsedBetween(bib, prevSeen, code)` → `formatElapsed(.,$resDec)`; blank for the first point and where either crossing missing |

- **"Previous point" = last point IN ORDER the racer was actually SEEN at** (has a
  usable crossing), so a missed checkpoint does NOT blank every following segment — the
  segment is measured from the last point he WAS at. `$prevSeen` advances only when
  `hasCrossingAt(bib, code)`.
- **DNF/DSQ**: render the points the racer reached as-is; if the engine flags him
  ended (`isDsq` takes precedence over `isDnf`), show a status note below the table
  (`LAN_RACEREPORTS_NUM_DNF`/`_DSQ`), with the marker point name when
  `race_clock::endedPoint(bib, flag)` resolves it. No times are fabricated for points
  he never reached.
- **Missing crossing → blank cell (—)**, never zero, never an error.

### Boundaries / engine

bib STRING everywhere (never int-cast), `$tp->toDB` on `?n`, parametrised `#racer`
lookup, output escaped (toHTML/toAttribute), no `extract()`. NO DataTables, NO export,
NO komplet, NO links from other reports/racers/sitelinks. Confirmed the engine already
covers this — `timeOfDay`/`formatTimeOfDay`, `elapsedRaw`/`elapsedToPoint`,
`elapsedBetween`, `endedPoint` all exist; **no engine change was needed**.

### LAN

Admin EN/SK `LAN_ADMIN_RACEREPORTS_116` (menu) + `_117` (panel heading). Front EN/SK
`LAN_RACEREPORTS_NUM_*` (unknown-bib, column headers Kontrola/Čas dňa/Medzičas/Úsek,
DNF/DSQ). Next free range (admin highest previously 115).
