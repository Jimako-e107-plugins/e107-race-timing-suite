# raceevent - developer notes

Base plugin for the race-timing suite. These notes live here (not as XML
comments in `plugin.xml`) by project convention.

## Single-event model (web = event)

This installation is **single-event**: there is exactly ONE event, so its data
is **configuration**, not table rows. The plugin therefore owns **no database
table** - every event field is a plugin pref, read/written via
`e107::getPlugConfig('raceevent')`.

### No `race_event` table (first version, no migration)

The first version dropped the `race_event` table outright (no data to migrate):

- **There is no `raceevent_sql.php` at all.** It was removed: a prefs-only
  plugin owns no table, so the file had no `CREATE TABLE` and served no
  purpose. The earlier belief that a non-empty dummy file was *required* (to
  dodge an install error) is false - see the verified `XmlTables` behaviour
  below.
- `plugin.xml` carries **no** table reference (there is no `<tables>` /
  install-table list), so e107 never tries to install/uninstall a missing
  table.
- The old event CRUD (`e_admin_ui` list/add/edit controller) is gone.

#### Verified: a missing `_sql.php` is a clean no-op (e107 master `plugin_class::XmlTables`)

`XmlTables()` does `if(!file_exists($sqlFile)) { ... log("No SQL File Found");
return null; }` - a **missing** `_sql.php` simply logs and returns, **no error**.
Only an **empty** (0-byte) file trips `addError("Can't read SQL definition")`.
So a prefs-only plugin needs **no** `_sql.php`: deleting it (rather than keeping
a 0-byte file or a dummy comment file) is the correct, error-free choice. Both
install and uninstall run cleanly with nothing to create or drop.

## Shared config / prefs

The plugin config is reachable via `e107::getPlugConfig('raceevent')`.

- `schema_version` - tracks the prefs layout.
- Event fields: `event_name`, `event_date`, `event_city`, `event_location`,
  `event_description`, `event_organizer`.
- Event flags: `is_charity`, `is_children_runs_included`,
  `is_participate_with_dog_allowed`.

All of the above are edited on the single **Event configuration (Nastavenie
podujatia)** admin screen (`admin/admin_config.php`), a native e107
`e_admin_ui` `$prefs` form.

`event_date` is stored as a **Unix timestamp** (`data` type `int`). The field
uses `type => 'datestamp'` with `writeParms => array('type' => 'date')`, which
forces the datepicker into **date-only** mode (no time component): the display
format comes from the site `inputdate` pref (e.g. `%Y-%m-%d`).

Per-area settings can later be stored as nested arrays (e.g. `areas/<plugin>/...`)
so feature plugins keep their own settings without new tables.

## Registration settings (issue #28)

Event-level registration settings live in the SAME prefs / settings admin (no
parallel system). They are consumed by `racereg` #24/#25 via
`e107::getPlugConfig('raceevent')`, so the **pref key names are fixed** and must
not be renamed (verified against the keys `racereg/includes/racereg_signup.php`
reads):

- `registrationStartAt` - INT Unix timestamp (0 = unbounded). Registration
  window opening.
- `registrationEndAt` - INT Unix timestamp (0 = unbounded). Registration window
  cut-off.
- `payeeIban` - string. Organizer's payee account (IBAN).
- `payeeName` - string. Beneficiary / account-holder name.
- `payeeSwift` - string (optional). Bank identifier (BIC).

All five are edited on the existing **Event configuration** screen
(`admin/admin_config.php`) as `$prefs` fields (native `e_admin_ui`, CSRF via the
`e_token`):

- The window uses `type => 'datestamp'`, `data => 'int'` with
  `writeParms => array('type' => 'datetime')` so it renders **date+time** pickers
  and stores a Unix timestamp (INT) - never a SQL DATE. (Contrast `event_date`,
  which is date-only `type=date`.)
- `payeeIban` / `payeeName` / `payeeSwift` are plain text, `data => 'str'` -
  stored as **strings, never cast**.

### IBAN / SWIFT normalisation + light validation

`raceevent_config_ui::beforePrefsSave()` (the native e_admin_ui prefs hook,
called from `PrefsSaveTrigger()`) normalises and lightly validates the payee
fields. Notes:

- The hook returns the **full** posted-data array (with only the payee fields
  rewritten). Returning a subset would drop the other prefs, because
  `PrefsSaveTrigger()` replaces the whole posted set with whatever non-empty
  array the hook returns.
- IBAN / SWIFT are whitespace-stripped and upper-cased before storage.
- IBAN is validated by `isValidIban()`: structure + length (SK = 24 chars) + the
  ISO 7064 **mod-97** checksum, implemented iteratively so it needs **no bcmath**
  on stock Lite. SWIFT/BIC is checked as 8 or 11 alphanumerics.
- Validation is **non-blocking** ("light"): an implausible value is still saved
  as a string (the organizer owns the account) but raises a `addWarning()`; the
  IBAN warning is also logged via `e107::getLog()` (`RACEEVENT_01`). Nothing is
  silently discarded or cast.

`schema_version` was bumped to `3` for this pref addition. `pluginPrefs` are
seeded on install; existing installs read the new keys via `get($key, $default)`,
so no migration is required (defaults: empty string / 0).

## Shared mapping layer (removed - reintroduce if ever standardised)

`includes/raceevent_model.php` (an abstract base CRUD / mapping class) was
**removed**: **no plugin ever extended it**. Admin CRUD is handled by
`e_admin_ui`, and the feature plugins each do their own front-end data access,
so the shared base was dead infrastructure carried for a convention that never
materialised. The `includes/` folder went with it (it held only this file).

The idea - a single sanitised access point (`toDB` on every value, a field-type
map on every query, ids cast to int) so query construction and the
SQL-injection guard live in one place - is sound and can be reintroduced later
**if** front-end data access across the suite is ever standardised onto a shared
base. Until then there is nothing to maintain. `racereg`'s own insert path keeps
its inline `toDB()` + int-cast sanitisation (it never used this class).

## Admin structure (mode-per-file)

`admin/admin_menu.php` holds the shared dispatcher/menu. The single event
configuration page is the default mode; the dispatcher is kept so future modes
can still be added - each mode is served by its own entry script and every menu
item carries an explicit `url`, so adding a feature = a new file + a new
`$modes`/`$adminMenu` entry, with no change to existing modes:

- `main` -> `admin/admin_config.php` (event configuration via `$prefs`, default)

## Welcome menu (front-end)

`welcomeevent_menu.php` is a front-end menu that shows the event name and
description. It was **moved here from timetracker** (`welcomeintro_menu.php`) - a
strangler move, not a duplicate. It now reads the RACEEVENT prefs (not
timetracker's), so the values are entered on raceevent's own config page; there
is **no** automatic value migration.

Wiring (all native e107, verified against Lite ~2.3.7):

- **Registration:** the menu manager auto-discovers any `*_menu.php` in an
  installed plugin folder (`menumanager_class::menuScan`, scanning for
  `_menu\.php$`). This is exactly how timetracker exposed its menus - **no
  `<menuLink>`/plugin.xml entry is needed** (and timetracker had none).
- **Template:** `templates/raceevent_template.php` defines
  `$RACEEVENT_TEMPLATE['welcome']`, loaded via
  `e107::getTemplate('raceevent', null, 'welcome')`. Kept extensible - add more
  event fields by adding their shortcode placeholders.
- **Shortcodes:** `e_shortcode.php` (class `raceevent_shortcodes`, registered
  site-wide like timetracker's batch) provides:
  - `{RACEEVENT_EVENT_NAME}` - `event_name` pref, escaped via `$tp->toAttribute()`.
  - `{RACEEVENT_EVENT_DESCRIPTION}` - `event_description` pref (textarea),
    rendered via `$tp->toHTML()`.
  Both read from `e107::getPlugConfig('raceevent')`.
- **Parse + render:** the menu calls
  `e107::getParser()->parseTemplate($template, true)` (site-wide batch resolves
  the codes) and outputs via `e107::getRender()->tablerender()`. Caption LAN:
  `LAN_RACEEVENT_WELCOME` (EN/SK front LAN).

## Issue #47: required-field validation for payment + registration config

Hybrid validation (recommended option C) in `raceevent_config_ui::beforePrefsSave()`:

- **HARD (reject) - IBAN => beneficiary name.** PAY by square (bysquare) cannot
  encode the QR without the beneficiary, so an `payeeIban` set with an empty
  `payeeName` is rejected with an error.
- **HARD (reject) - registration window.** If both `registrationStartAt` and
  `registrationEndAt` are set, opening must be strictly before closing.
- **SOFT (warn) - incomplete payee while registration is in use.** When a window
  is set but IBAN or name is missing, warn that QR / payment instructions fail.

### Why "reject" is implemented as a revert, not a `return false`

`e_admin_ui::PrefsSaveTrigger()` (Lite 2.4.x **and** e107 master - verified
identical) **always** calls `getConfig()->save()`; it ignores the return value of
`beforePrefsSave()`. There is no native "abort prefs save". So a hard rule is
enforced by **restoring the offending field(s) to their stored (`$old_data`)
value** inside the hook and raising `addError()`: the broken combination is never
persisted, and the organizer is told why the change did not take. The soft
warning is then evaluated on the values that will *actually* be stored (after any
revert).

### Why the payee/window fields do NOT use the HTML5 `required` writeParm

`e_admin_ui`'s `'required'` writeParm emits the **unconditional** HTML5 `required`
attribute (form_handler.php), which would block saving *any* event setting until
the field is filled - wrong for a free event (the suite supports a "bez poplatku"
state) and for the *conditional* IBAN=>name rule. The "required" hint therefore
lives in each field's `help` text and on the help page; the real rule is enforced
server-side. All checks are server-side; input is read as strings (IBAN/VS never
cast to int); no SQL is built from input.

## Event overview (Prehľad preteku) - cross-suite link directory + alive-check

A cross-suite link directory plus a cheap "is it alive" check, exposed two ways
that render IDENTICALLY because they share one include:

- **Front page** `page_overview.php` - HEADERF/FOOTERF + tablerender. Served via
  the NEW `e_url.php` route `index`, SEF alias **`preteky`** (the original alias).
  raceevent had no `e_url.php` before; timetracker's own `index`/`preteky` route
  is already commented out (intentional strangler trace - left as-is, not flagged),
  so raceevent claims the alias with no collision.
- **Admin screen** `admin/admin_overview.php` - a "Prehľad preteku" menu item
  (mode `overview`, action `view`) that tablerenders the same include's output.

### One shared include

`includes/event_overview.php` is the single source of truth. It exposes
`raceevent_event_overview()` which RETURNS the HTML (renders nothing) - both
callers wrap it in their own chrome. Plain classic `<ul>`/list links, NO
racereports visual effects. Every label escaped with `$tp->toHTML()`, every href
with `$tp->toAttribute()`. Read-only: db reads + link building, no writes.

### Five sections, entity/dependency order

1. **Zoznam tratí** -> `e107::url('racetrack','race', ...)` (per `#race`).
2. **Zoznam kontrolných bodov** -> `e107::url('racereports','point', ...)` per
   checkpoint, start/finish skipped.
3. **Zoznam kategórií** -> `e107::url('racereports','start', ...)` per category
   (the START report = start-as-point, `report_start.php`, which may not exist
   yet - linked anyway; the alive-check flags it).
4. **Štartovacie listiny** -> `e107::url('racers','startlist', ...)` per category
   + per-race komplet.
5. **Výsledkové listiny** -> `e107::url('racereports','finish', ...)` per category
   + per-race komplet.

Sections 2 / 3 / 5 **mirror the racereports `e_sitelink.php` builders** - same
queries, same start/finish skip, same `race_point_sef = race_point_code` mapping
(section 2 == `racereports_points()`, section 5 == `racereports_finish()`,
section 3 reuses `racereports_finish()`'s `#race ⋈ #race_category` source pointed
at the `start` key). No third query variant is invented. **stu and online are
intentionally EXCLUDED** (one-off / central-reporting, not part of the live event
overview).

### Alive-check (the point of the page for Jimmi)

For each generated link the route's redirect TARGET FILE is resolved from
`e107::getUrlConfig()` (the same scanned `e_url_list` `e107::url()` consults),
the query string is stripped, `{e_PLUGIN}`/`{e_BASE}` are expanded, and
`file_exists()` decides a per-link marker: **green** = route registered AND
target present, **red** = target missing OR route not registered at all (e.g.
`racereports` `start` has no e_url route yet, so it shows red until
`report_start.php` is built and routed). The page NEVER fails on a missing
target - it only flags. The marker tooltip carries the resolved target path.

### Replaces the legacy hand-made overview

Built fresh from the section spec above. The old `timetracker/index.php` pointed
at OLD/broken targets (timetracker finish/category/start/point) and carried dead
code (unused `$start`/`$finish`/`$tmp` classification, double `$url` assignments,
no escaping). None of its link targets or dead code were ported.

### LAN

Shared section headings + status labels + page caption live in the FRONT LAN
(`languages/<lang>_front.php`, `LAN_RACEEVENT_OV_*`) and the include loads
`e107::lan('raceevent')` itself, so it has its strings in either context. The
admin menu caption + help are admin LAN (`LAN_RACEEVENT_OV_MENU` /
`LAN_RACEEVENT_OV_HELP`).
