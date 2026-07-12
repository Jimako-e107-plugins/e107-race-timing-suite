# racetiming - developer notes

_Created: 2026-06-24 04:21 UTC._
_Updated: 2026-06-24 09:42 UTC - race_time CRUD admin moved in from timetracker._
_Updated: 2026-06-24 11:59 UTC - bulk start-generation + its `starttime` pref moved in from timetracker._
_Updated: 2026-06-25 06:47 UTC - manual passing-entry "keypad app" (vstup) + {TIMETRACKER_VSTUP} shortcode + `kontrola` route moved in from timetracker (and racetrack); racetiming's FIRST front route/page/LAN._
_Updated: 2026-06-26 - engine `timeOfDay()` / `formatTimeOfDay()` start-time primitive added; racers/startlist.php switched onto it (off timetracker). Parity extended in racereports/parity._
_Updated: 2026-06-29 - three legacy event names renamed `timetracker_*` -> `terminovka_*` (saveresult, deleteresult, finish_time), atomically across listener registration + every trigger. See "Event-name rename" below._

Checkpoint timing engine for the single-event race-timing suite (e107 Lite
2.4.x). These notes live here, not as XML comments in `plugin.xml`, by project
convention.

## State: manual passing-entry "keypad app" (vstup) - racetiming's FIRST front route

The manual passing-entry feature - a mobile, app-like keypad UI used live on the
course by the people recording racers at each checkpoint - now lives here. It was
relocated **faithfully, 1:1** from timetracker (the page was
`timetracker/timetracker_vstup.php`); nothing in its UI, JS, CSS, DNF/DSQ flow,
header (ms time / order / previous number) or operator notices was changed. This
is racetiming's FIRST `e_url.php` route, first front page and first front LAN.

THREE coupled units moved together:

1. **The page** `racetiming/vstup.php` (was `timetracker/timetracker_vstup.php`).
   Opened via the `kontrola` route. Reads `k` (checkpoint `race_point_code`) +
   `p` (password segment); typing 4 digits + SEND (or DNF / DSQ) writes a row
   into `race_time`. DNF/DSQ are **legitimate** non-numeric `race_time_ended`
   values, NOT errors - mistakes are fixed later by the admin via the race_time
   CRUD, never here. The body is byte-identical to the original except an added
   front-LAN loader block at the top (see LAN below).

2. **The shortcode** `{TIMETRACKER_VSTUP}` - registered in
   `racetiming/e_shortcode.php` (`racetiming_shortcodes::sc_timetracker_vstup`).
   The TAG NAME is **intentionally KEPT as `TIMETRACKER_VSTUP`** (NOT renamed to
   `RACETIMING_*`): existing and archived event-pages contain the literal tag in
   their body, so renaming would break them. Only the registering plugin moved;
   the rendered checkpoint links now use `e107::url('racetiming', 'kontrola', …)`
   so they resolve through the moved route.

3. **The route** `kontrola` - `racetiming/e_url.php` (the plugin's first route).
   Copied verbatim except `redirect` -> `{e_PLUGIN}racetiming/vstup.php?k=$1&p=$2`.
   Same alias / regex / sef, so the PUBLIC SEF URL is byte-identical:
   `/kontrola/{race_point_code}/{race_point_password}/`. GET params `k`/`p` kept.
   The links are already distributed to checkpoint staff in the field, so the URL
   must not change.

### `kontrola` had THREE declarers before the move (point-vs-timing drift)

Before this move the `kontrola` alias was declared in **three** places:
`timetracker`, `racetrack`, and (added by this move) `racetiming`. The
`racetrack` declaration was a duplicate of timetracker's, redirecting to
`timetracker_vstup.php`, with a caller at `racetrack/admin_config.php`.

`racetrack`'s declaration was **drift** and was folded into racetiming here.
Rationale: `racetrack` owns the checkpoint *entity* (the `race_point` code +
password definition), but **recording a passing at that checkpoint is timing,
not track** — and racetiming owns `race_time`, where the passing is written. A
prior change had moved `kontrola` onto racetrack by conflating "the point
belongs to racetrack" with "recording times at the point"; the strangler removes
exactly that conflation.

Final state: the `timetracker` AND `racetrack` declarations were both removed;
`kontrola` is now declared by **racetiming only**. `racetrack`'s checkpoint
"execute" admin button (`admin_config.php`) was repointed to
`e107::url('racetiming', 'kontrola', …)`. Route-integrity across all three
plugins: zero dead `kontrola` links anywhere, single declarer.

### Inline CREATE TABLE race_time - LEFT in place (runtime safety net)

`vstup.php` carries a **commented-out** `CREATE TABLE \`race_time\`` block
(bare `CREATE TABLE`, NOT `CREATE TABLE IF NOT EXISTS`). It is a deliberate
runtime safety net / reference - the page can run before the table is otherwise
populated - NOT schema drift. It is intentionally LEFT untouched. The canonical
schema remains `racetiming/racetiming_sql.php`; no `plugin.xml` schema change.

### Front LAN (NEW) - keypad UI intentionally still hardcoded

`languages/English/English_front.php` + `languages/Slovak/Slovak_front.php`
(`LAN_FRONT_RACETIMING_*`, EN canonical, SK overrides + falls back to EN) are
racetiming's first front LAN. `vstup.php` loads them at the top via
`e107::lan('raceevent', true, true)`, `e107::lan('racetiming', true, true)` and
`e107::lan('racetiming', '', true)`. Per the faithful 1:1 relocation and the
non-negotiable "do not change the keypad UI / header / operator notices"
constraint, the page's visible strings (digits, DNF/DSQ/CLEAR/SEND glyphs, the
`poradie … predošlé číslo` header caption, the "už zapísaný" / "už predčasne
skončil" notices) are **intentionally LEFT HARDCODED** and are NOT wired to these
keys. The LAN files hold the canonical EN/SK wording as scaffolding for a later
i18n pass. (The only LAN constants the page actually references -
`LAN_UPDATED_FAILED`, `ADLAN_78` - are core e107 constants, not feature strings.)

### SECURITY - REPORTED, NOT FIXED (for Jimmi to address separately)

The relocated page concatenates raw request input (`$_GET['k']`,
`$_POST['number']`, `$_POST['race_time_point']`) directly into SQL - the legacy
`get_starttime_from_tracker()`-style pattern. These were moved AS-IS (no fix per
the faithful-relocation scope). Line numbers below are in the current
`racetiming/vstup.php`:

- `vstup.php:206-207` - `"SELECT *  FROM " . MPREFIX . "race_time WHERE race_time_point LIKE '{$race_point_sef}' AND race_time_racer_number LIKE '{$racer_number}' LIMIT 1"`
- `vstup.php:224` - `"SELECT *  FROM " . MPREFIX . "race_time WHERE race_time_racer_number LIKE '{$racer_number}'  AND race_time_ended != '' LIMIT 1"`
- `vstup.php:248-255` - `UPDATE` concatenating `$race_point_sef`, `$racer_number`, `$ended` (the `race_time_point = '…' , race_time_racer_number = '…'` SET clause)
- `vstup.php:288-289` - `"SELECT * FROM " . MPREFIX . "race_point AS rp WHERE race_point_code = '{$race_point_sef}'"` (`$_GET['k']`)
- `vstup.php:295` - `"SELECT * FROM  " . MPREFIX . "race_time AS rt WHERE race_time_point = '" . $race_point_sef . "' ORDER BY \`race_time_id\` DESC "`

Also reported: the page does **not** authenticate the `p` (password) segment
server-side - `$_GET['p']` is never read in the page; access control relies on
the password-protected e107 page that hosts the `{TIMETRACKER_VSTUP}` shortcode.
Left as-is.

## State: race_time CRUD admin (timing engine still pending)

The **race_time table administration** (list / create / edit / delete) now lives
here - it was extracted verbatim from
`timetracker/admin/admin_timetracker.php` (strangler decomposition). The admin is
a native `e_admin_ui` controller (`admin/admin_config.php`, dispatched by
`admin/admin_menu.php`) over the `race_time` table, behind this plugin's own
admin permission (`getperms('P')`, `$pluginName = 'racetiming'`). Field map,
`$listOrder`, filters and batch behaviour are reproduced unchanged from the old
timetracker CRUD; the field LAN strings are renamed to `LAN_ADMIN_RACETIMING_*`.

The **bulk start-generation feature STAYS in timetracker** (it depends on
timetracker prefs) and was deliberately NOT moved here. The **timing computation
engine is still NOT implemented** - it is extracted from `timetracker` in a
later issue.

### Field-type preservation (do not "upgrade")

- `race_time_racer_number` (start/bib number) is a **string** field
  (`type=text`, `data=str`) - 4-char with leading zeros, never cast to int.
- `race_time_time` is a plain **text** field (`type=text`, `data=str`) holding
  the exact `Y-m-d H:i:s.v` millisecond value (`VARCHAR(100)`). It is **never** a
  `datestamp`/datetime e_admin_ui type - that would convert to/from a unix int
  and destroy the millisecond precision. (`race_time_created` /
  `race_time_updated` ARE `datestamp` - they are read-only unix-int columns.)

### Interim cross-plugin coupling (race_result sync)

Editing/deleting a `race_time` row still fires the `terminovka_saveresult` /
`terminovka_deleteresult` events, whose handlers live in `terminovka`'s
`e_event.php`. The event names were renamed from the legacy `timetracker_*`
prefix to `terminovka_*` once the handlers were rehomed; behaviour is
unchanged — only the event-name strings moved.

`racetiming` is the planned replacement for the timing half of `timetracker`,
following the strangler pattern: the new owner is created first, the
behaviour moves incrementally, and the old code is removed last.

## Table ownership

`racetiming` owns **exactly one** table: **`race_time`** (the checkpoint times).
Its `CREATE TABLE` is copied **verbatim** (columns, types, keys, engine) from
`timetracker/timetracker_sql.php` into `racetiming/racetiming_sql.php`, which is
the install hook e107 Lite reads (`plugin_class::XmlTables()` parses
`{plugin}_sql.php`). The definition uses `CREATE TABLE IF NOT EXISTS` (the safe
form); install only ever issues `CREATE` (never `DROP`), so on a database where
`race_time` already exists the install leaves the existing table and its data
untouched - it does not drop or recreate it.

### Transient double declaration + interim multi-writer state

`race_time` is **still declared in `timetracker`** as well (its
`timetracker_sql.php` still lists it). This double declaration is intentional and
temporary. **Removing the `race_time` schema from `timetracker` is a SEPARATE
later extraction issue** - moving the CRUD admin (this issue) does **not** touch
the `CREATE TABLE` declaration on either side, and performs no data migration.

During the interim, `race_time` is written by **two** writers:

1. the **racetiming** CRUD admin (`admin/admin_config.php`), and
2. the **timetracker** bulk start-generation feature
   (`admin/admin_timetracker.php` -> `includes/generujstart.php`), which still
   inserts `start` rows directly.

This multi-writer state is expected until the start-generation feature is moved
out of timetracker in a later step.

### Explicitly NOT owned by racetiming

- `race_tracking` - belongs to **racerfid** (the RFID import plugin).
- `race_result` / `race_archive` - belong to **racereports**.

## Dependencies

`raceevent` (event base) + `racetrack` (tracks/checkpoints) + `racers`. Declared
in `plugin.xml` `<dependencies>`, mirroring the working format in
`racereg/plugin.xml`.

## Language files

Sub-folder layout (`languages/English/`, `languages/Slovak/`), array-return
format (`return array(...)`, never `define()`), loaded with the 3-arg loader
`e107::lan('racetiming', true, true)` (admin) and
`e107::lan('racetiming', 'global', true)` (global). English is the canonical,
complete set; Slovak overrides per key and falls back to English for any missing
key. LAN key pattern: `LAN_<SCOPE>_RACETIMING_<NAME>`.

## Out of scope (the race_time CRUD move)

No `e_url.php` / SEF route changes in any plugin, no front-end pages or menus, no
front LAN files (`English_front` / `Slovak_front`), no timing/computation logic,
no `race_time` schema change (the `CREATE TABLE` stays declared in both plugins),
no data migration. The bulk start-generation feature stays in `timetracker`. The
only timetracker-side edits are the cut itself (removing the race_time CRUD
controller, its CRUD menu entries and CRUD-only LAN strings) and repointing the
admin edit-links on the timetracker front-end pages to this plugin, guarded by
`e107::isInstalled('racetiming')`.

---

## State: timing-computation engine (PART A) — IMPLEMENTED

_Updated: 2026-06-24 — clean compute/format engine extracted from timetracker._

The read/compute half of the timing engine now lives here as two pure classes
under `includes/`. timetracker is **untouched** and keeps running for the parity
check (strangler):

- `includes/race_clock.php` — `race_clock`: reads `race_time` ONLY (one crossing
  SELECT + the DNF/DSQ set readers), and exposes pure compute. Method names drop
  the legacy misnomers and map 1:1 to the legacy they replace:
  - `parseCrossingTime()` ⟵ `timetrackerMain::ISO8601ToMicrotime` (keeps the
    `DateTime::createFromFormat('Y-m-d H:i:s.v', …)` body, drops the name; NOT
    `strtotime`/`new DateTime`/`ATOM`).
  - `buildSplits()` ⟵ the two byte-identical load loops
    (`timetracker::__construct :65-84` AND `get_points_times :559-580`) — ONE
    loader. latest-crossing-wins per (number,point); per-racer `ended` propagated
    forward exactly as legacy.
  - `elapsedToPoint()` / `elapsedRaw()` ⟵ core of `get_racer_time_on_point`
    (`:248-310`). `round(point − start, 3, HALF_UP)`, full ms precision.
    `elapsedRaw` is the pure subtraction (used for the "time DNF" display cell);
    `elapsedToPoint` is the ranking-safe variant (null unless status OK).
  - `pointStatus()` — one clean enum `OK|DNF|DSQ|DNS|NO_START|NO_CROSSING`
    replacing the magic returns `-1 / '' / '---' / '-'`.
- `includes/race_format.php` — `race_format::formatElapsed($seconds,$decimals=1)`
  ⟵ `secondsToTime` (`:516-535`) **+** the per-call `substr` cut. `$decimals` is
  DISPLAY-ONLY (default 1 == legacy `substr(...,0,10)` tenths). Reproduces the
  legacy string byte-for-byte (verified by the fuzz in
  `racereports/parity/engine_selftest.php`).

The start/bib number is ALWAYS a string (`race_time_racer_number` VARCHAR) — no
`(int)` cast anywhere. `is_numeric()` guards every stored-time consumption
(PHP-8 safe): real data stores a non-numeric `DNF` marker at one point with valid
times at later points (the Cinko case), so the engine never assumes
complete/monotonic/numeric data.

### Intentional-fix candidates (POST-PARITY — flagged, NOT applied here)

These reproduce legacy behaviour for now so parity holds; each is a candidate
fix for Jimmi to schedule **after** parity is accepted:

- **DSG → DSQ typo (APPLIED as the one sanctioned divergence).** `pointStatus`
  and the report emit `DSQ`; legacy `get_racer_time_on_point` emits the typo
  `DSG` for a DSQ racer with no crossing at the queried point. The parity
  comparator lists this as an intentional fix, never auto-passes it.
- **Hours field (D-2).** `formatElapsed` reproduces the legacy hours via integer
  truncation without `%24`/`%60`; durations ≥ 100h overflow the field. (Uses
  `intdiv` for PHP-8 safety but the same arithmetic.) Fix: explicit H/M/S
  decomposition.
- **Negative elapsed (D-11).** Out-of-order/clock-skew crossings (`point < start`)
  produce malformed output in both legacy and here. Fix: clamp/flag negatives.
- **Timezone (D-10).** `parseCrossingTime` leaves the server-default timezone to
  match legacy (elapsed = difference of two same-zone crossings is tz-safe).
  Fix: pin UTC.
- **rtrim ms width (D-3).** The legacy `.mmm` `rtrim('0')` gives a variable-width
  fraction; reproduced for byte parity (irrelevant at the default tenths cut).
  Fix: fixed 3-digit `.mmm`.

### Deferred (NOT built in this phase)

- `timeOfDay(point)` — **now built** (2026-06-26); see "State: time-of-day
  start primitive" below.
- Ranking / standings / HTML — those live in **racereports**, never here.
- No writes, no result-freeze, no terminovka, no archive — out of scope and
  untouched.

### Security (engine)

All engine queries go through the e107 db class; the one interpolated value
(the DNF/DSQ flag) passes through `$tp->toDB()`. The crossing SELECT takes no
user input. The legacy raw-`$_GET`-into-SQL pattern lived in the report **pages**
and is NOT carried into the new pages (see racereports/NOTES.md).

---

## State: time-of-day start primitive (timeOfDay / formatTimeOfDay)

_Updated: 2026-06-26._

The engine's `elapsedToPoint()` returns a **duration** (start -> point). The
start list needs the opposite: the **raw wall-clock TIME-OF-DAY** the racer
crossed the start. Two new methods on `race_clock` supply it - the clean
replacement for the legacy start-time read chain
`get_racer_start_time` / `get_starttime_from_point`
(`timetrackerStart_class.php:9-43`) -> `ISO8601ToMicrotime` ->
`microtimeToSeconds` (`timetrackerMain_class.php:62-121`):

- **`timeOfDay(string $number, $point): ?float`** - the crossing's epoch float at
  `$point` for `$number`, full ms precision, or `null` if there is no usable
  crossing. Reuses the existing `parseCrossingTime()` + `buildSplits()` loader -
  **no second parser**. Defensive: non-numeric / `'DNF'` / sentinel / missing all
  flow to `null` via `savedTime()` (the engine never assumes complete data). The
  number stays a **string** (never `(int)` - leading zeros are data).
- **`formatTimeOfDay(float $epoch, int $decimals = 0): string`** - formats the
  epoch as an `HH:MM:SS[.mmm]` wall-clock string, `$decimals` truncated for
  display (the same substr cut as `race_format::formatElapsed`; display-only,
  never a sort key). The start list passes `$decimals = 3` to reproduce the
  legacy full `.mmm` form.

**Why `microtimeToSeconds` is a misnomer.** Despite the name, the legacy function
returns a TIME-OF-DAY clock string (`date('H:i:s', floor(epoch))` + an rtrim'd
`.mmm` / `.000` suffix), NOT a count of seconds. `formatTimeOfDay` reproduces that
byte-for-byte.

**ROUND vs TRUNCATE (the one subtle parity point).** The legacy *start-time*
formatter `microtimeToSeconds` **rounds** the ms field
(`round($micro*1000, 0)`), whereas the *duration* formatter
`race_format::formatElapsed` (mirroring `secondsToTime`) **truncates**. They are
**different legacy functions**. Because the absolute epoch carries float residue
(`format('U.v')` of a ~1.75e9 value), `.700` can land at `.6999996`: `round` ->
`700` (`.7`), but `(int)` -> `699` (`.699`). `formatTimeOfDay` therefore ROUNDS,
to stay string-equal to `microtimeToSeconds`. (For durations there is no residue
because the value is `round(...,3)`-ed upstream, so `formatElapsed`'s truncate is
safe there.) This is why `formatTimeOfDay` does NOT simply delegate to
`race_format`.

**Parity.** The primitive is new, so it is proven, not assumed:
- `racereports/parity/engine_selftest.php` (pure, no DB) - start-cell parity
  against a faithful copy of the legacy chain across normal starter, leading-zero
  bib, no-start (`null`/empty -> `""`), and non-numeric marker; plus a
  round-not-truncate byte-parity fuzz.
- `racereports/parity/parity_check.php` (DB-backed, admin) - `timeOfDay` +
  `formatTimeOfDay` vs the live legacy start-list compute over the same
  `race_time` rows, reporting matched/mismatch and flagging any legacy comma-
  format divergence as intentional (never auto-passed). Legacy left untouched.

**Consumer.** `racers/startlist.php` now reads the start cell from this primitive,
guarded by `isInstalled('racetiming')` (see racers/NOTES.md). timetracker is NOT
modified.

---

## State: bulk start-generation moved in from timetracker

_Updated: 2026-06-24 11:59 UTC._

The **bulk start-generation feature** now lives here, in
`admin/admin_generujstart.php` - relocated (behaviour-preserving) from
timetracker's `admin/admin_timetracker.php` (`mode=tracker action=generujStart`)
+ `admin/includes/generujstart.php`. racetiming owns the `race_time` table and
start-generation writes `start` passings into it, so the feature belongs here.

`admin_generujstart.php` is a native `e_admin_ui` controller (`race_generate_ui`)
dispatched by `admin/admin_menu.php` under the **`generate`** mode, behind this
plugin's OWN admin permission (`getperms('P')`, `$pluginName = 'racetiming'`).
Two admin-menu entries point at it:

- `generate/prefs` - sets the `starttime` default-time pref.
- `generate/generujStart` - the generation page (`generujStartPage()`): pick
  tracks + a start time, insert one `start` `race_time` row per active racer
  that does not already have one. The generation logic, inputs and `race_time`
  writes are reproduced from the legacy code unchanged.

### Prefs: `starttime` (datetime STRING)

`starttime` is now a **racetiming** plugin pref, read/written via
`e107::getPlugConfig('racetiming')`. It is a datetime **STRING** with seconds
precision (e.g. `2024-10-08 07:00:00`) - a plain `type=text` / `data=str` field,
**never** an int/`datestamp` e_admin_ui type. Definition + default
(`date('Y-m-d H:i:s')`) are recreated from the old timetracker prefs form. The
generation page no longer reads any timetracker pref.

> **`generujStart` was never a pref.** In timetracker it was only the
> mode/action/submit-button name (`mode=tracker action=generujStart` /
> `GenerujStartTrate`), not a stored preference. There was nothing to register
> for it - only `starttime` is a real pref.

### MANUAL STEP REQUIRED ON EXISTING INSTALLS

`starttime` (and there is no `generujStart` pref) was **NOT migrated** from the
timetracker prefs. No pref-value copy was performed. **The admin must re-enter
`starttime` in racetiming after deploy** (Race timing → Start time setting), or
start-generation will fall back to the current server time as its default value.

### LAN

Generation strings were copied into `languages/English/English_admin.php` and
`languages/Slovak/Slovak_admin.php` (array-return, sub-folder layout) as
`LAN_ADMIN_RACETIMING_040`-`052`, renamed from the timetracker `LAN_TR_*` set.
EN is canonical/complete; SK overrides per key and falls back to EN. Load order
(in `admin_menu.php`) is `raceevent` → `racetiming` (admin) → `racetiming`
(global). A few non-translated form labels (`Trať`, the two table headers,
`Chyba pri generovaní`) were hardcoded in the legacy include and are reproduced
**verbatim** here - intentionally not LAN-ified, to keep behaviour identical.

### Security

The custom generation form is opened with `e107::getForm()->open()` (carries the
e107 form token); `e_admin_ui` handles CSRF. The legacy existence lookup
concatenated the bib number raw into SQL - here it is escaped via
`$tp->toDB()` before being embedded (`race_time_racer_number` is a 4-char STRING,
never cast to int). The `race_time` insert goes through the db class
(values escaped on the parameterised insert path). `starttime` stays a datetime
STRING.

### Out of scope (this move)

No `e_url.php` / SEF route change in any plugin, no `race_time` schema change
(the `CREATE TABLE` stays declared in both plugins - strangler double
declaration), no pref-value migration. timetracker keeps `refresh_interval` and
the archive admin + its menu entries untouched.

---

## State: engine `crossingId()` — additive crossing-identity getter (2026-06-26)

`race_clock` now exposes the **race_time_id** of a racer's crossing at a point, so
the report layer can link the racetiming `race_time` admin on the exact row. This
is crossing IDENTITY (the stored PK), not presentation — fine to live in the engine.
The change is **purely ADDITIVE**: no existing time/status method changed behaviour.

### What changed

- `loadCrossings()` now also `SELECT`s `race_time_id` (the legacy load did not read
  it). One extra column; the crossing SELECT still takes no user input.
- `buildSplits()` carries the id into each `map[number][point]` cell as
  `crossing_id`. Unlike the time fields (latest-crossing-wins), the id is kept as
  the **LOWEST** `race_time_id` for a duplicate `(number,point)` — matching the
  legacy point page's `SELECT race_time_id … LIMIT 1` (no `ORDER BY` → physical/PK
  order → first-inserted = lowest id). Data shouldn't have duplicates, but is
  untrusted, so the choice is explicit and stable. So for a duplicate the displayed
  TIME follows the latest crossing while the EDIT link targets the lowest-id row.
- New getter **`crossingId(string $number, $point): ?int`** — the `race_time_id`
  of that racer's crossing at `$point`, or `null` when there is no row at the point.
  A row carries an id even when its time is unusable (an unparseable / `DNF` marker
  row still has a PK), so an ended/waiting racer that DOES have a stored crossing
  here returns its id; a racer with no row here returns `null`. The bib stays a
  **STRING** key (never `(int)` — leading zeros are data); the point is keyed by
  `race_time_point` as elsewhere.

### Parity (proven, not assumed)

- `racereports/parity/engine_selftest.php` (pure, no DB) — `crossingId` over the
  synthetic crossings: normal crossing, leading-zero bib stays a string key,
  no-row → `null`, marker-row-with-unparseable-time still returns its id, and the
  duplicate `(number,point)` lowest-id choice (asserting the time cell is
  unchanged — the id is independent of the time).
- `racereports/parity/parity_check.php` (DB-backed, admin) — asserts `crossingId`
  returns the SAME id as the legacy `SELECT race_time_id … LIKE point AND … LIKE
  number LIMIT 1` over every `(racer, point)`, reporting matched / both-null /
  mismatch counts. Legacy is left untouched.

### SCOPE held

racetiming engine only (`includes/race_clock.php`) + the racereports parity
harness. The existing time/status methods are byte-for-byte unchanged; the id is
only ever carried alongside, never used by them. No schema change, no writes, no
route change.

---

## ADDITIVE accessor: `endedPoint($number, $flag = 'DNF')` (for the full results matrix)

The racereports AKTUALNE report (the full per-race results matrix,
`racereports/report_aktualne.php`) needs the checkpoint code at which a racer
carries an ended marker — the value the legacy archive read as
`self::$dnf_racers[$number]['race_time_point']` (`get_dnf_racers` /
`get_dsq_racers`, `timetracker_class.php`). The DNF furthest-point ordering
(`ordertime = (checkpointCount - dnf_point_order) * 1e6`) and the blank-after-DNF
mid-time cells both pivot on the MARKER's point.

The propagated `ended` flag already in the split map **cannot recover** this: it is
stamped forward onto every later cell (latest-crossing-wins / forward propagation),
so the marker cell is no longer distinguishable from the cells after it. Hence a
NEW read is genuinely needed.

`endedPoint()` is **purely additive**: it touches NOTHING existing — not `build()`,
not `buildSplits()`, not the split map, not the DNF/DSQ membership sets, not any
time/status method. It performs ONE `race_time` read per flag (lazily, cached in
`$endedPointMap`), mirroring the legacy `GROUP BY race_time_racer_number` pick so
the grouped `race_time_point` is the SAME row the legacy archive read (the realistic
one-marker-per-racer case is unambiguous). Engine reads `race_time` ONLY (no racer
join — the report already scopes the lookup to its own race's bibs, consistent with
how `isDnf()`/`isDsq()` and the rest of the engine already work globally). Flag goes
through `$tp->toDB()`; the bib/start number stays a STRING (leading zeros are data).
Returns `null` when the racer carries no such marker.

Proven by `racereports/parity/parity_aktualne.php` (DB-backed): the clean build's
DNF ordering + blank-after-DNF cells match the legacy `timetrackerArchive` output.
## Additive engine op: `elapsedBetween()` (between-two-points split)

_Added for the racereports CUSTOM (segment) report — `racereports/report_custom.php`,
the clean equivalent of `timetracker/timetracker_custom.php`._

`race_clock::elapsedBetween(string $number, $pointA, $pointB): ?float` returns the
time **elapsed BETWEEN two arbitrary points** (a split):

    savedTime($number, $pointB) − savedTime($number, $pointA),  round(., 3, HALF_UP)

…or `null` when **either** crossing is missing/unusable (the racer is simply not in
that segment). It is the clean equivalent of the legacy
`get_racer_time_between_points()` **time** computation
(`timetracker_class.php:358-414`, `round($actualtime - $startpoint, 3,
PHP_ROUND_HALF_UP)`, where `$startpoint` = saved time at the FROM point and
`$actualtime` = saved time at the TO point); UNLIKE the legacy method it returns
ONLY the float (or `null`) — the display text / `DNF` magic-value shape stays in the
presentation layer (`report_custom.php`).

### ADDITIVE — existing methods unchanged

`elapsedBetween()` is a **pure subtraction of two existing `savedTime()` lookups**:
no new DB read, no ranking, no state, no schema/route change, and it does not touch
any existing accessor (`savedTime`/`elapsedRaw`/`elapsedToPoint`/… are byte-for-byte
unchanged). Like `elapsedRaw()` it ignores any ended flag (the difference of two
stored crossings is well-defined whenever both exist) and keeps the bib a **STRING**
(never `(int)` — leading zeros are data); `is_numeric()/>0` guards each crossing
(PHP-8 safe, never assumes complete data). A reversed pair yields a **signed**
(negative) value — the engine returns the raw subtraction; dropping a non-positive
split is the report's job.

### Parity (proven, not assumed)

- `racereports/parity/engine_selftest.php` (pure, no DB) — `elapsedBetween` over the
  synthetic crossings: intermediate split `K1→finish`, `start→K1`,
  `start→finish == elapsedToPoint(finish)`, reversed pair → signed negative, missing
  FROM crossing → `null`, no-start → `null`, unparseable marker row at od → `null`,
  and the leading-zero bib staying a string key.
- `racereports/parity/parity_custom.php` (DB-backed, admin) — asserts the clean
  `report_custom` segment **time cell** AND the **ASC rank order** match the legacy
  `timetracker_custom` (`get_racer_time_between_points`) for the same race/od/do, over
  every consecutive checkpoint pair (or an explicit `?od&do`). Legacy untouched.

## Event-name rename: `timetracker_*` -> `terminovka_*` (2026-06-29)

Three legacy event names still carried the dead `timetracker_` prefix even though
their handlers + triggers already live in `terminovka` (+ `racetiming` triggers).
An event name is a contract between trigger and listener: rename one side without
the other and the event silently stops firing — `race_result` writes would break
with **no error**. So all three were renamed **atomically** across the listener
registration AND every trigger in one pass:

| old name | new name |
|---|---|
| `timetracker_saveresult` | `terminovka_saveresult` |
| `timetracker_deleteresult` | `terminovka_deleteresult` |
| `timetracker_finish_time` | `terminovka_finish_time` |

Only the event-name **strings** changed. Handler method names (`saveresult`,
`deleteresult`, `onFinishTime`), payloads, and logic are unchanged — handlers
still write/track `race_result` exactly as before.

### Contract map (post-rename — each trigger has its one matching listener)

- **`terminovka_finish_time`** — listener `terminovka/e_event.php:35` (`onFinishTime`);
  trigger `terminovka/terminovka.php:69`.
- **`terminovka_saveresult`** — listener `terminovka/e_event.php:42` (`saveresult`);
  triggers `racetiming/admin/admin_config.php:164`, `terminovka/e_event.php:133`,
  `terminovka/e_event.php:147`, `terminovka/e_event.php:188`,
  `terminovka/terminovka_test.php:184`.
- **`terminovka_deleteresult`** — listener `terminovka/e_event.php:52` (`deleteresult`);
  triggers `racetiming/admin/admin_config.php:151`, `racetiming/admin/admin_config.php:171`.

### Verification

- `grep` confirms **zero** remaining `timetracker_saveresult` / `timetracker_deleteresult` /
  `timetracker_finish_time` anywhere in `eplugins` (code, comments, NOTES, tests).
- `php -l` clean on all edited PHP files.
- Out of scope: two repo-root historical audit reports
  (`audit-online-point-reports_EN_2026-06-24_1027.md`,
  `audit-time-computation_EN_2026-06-24_0926.md`) still mention the old strings —
  left verbatim as dated historical records (outside racetiming/terminovka scope).

### REQUIRED manual check (runtime, by user)

This is an event-name change only; the trigger↔listener wiring is verified
statically but not at runtime. The user must confirm **live** that saving and
deleting a `race_time` in the racetiming admin still writes to terminovka's
`race_result` (row inserted/updated on save, removed/marked on delete).
