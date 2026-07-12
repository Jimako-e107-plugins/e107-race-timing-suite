# terminovka - developer notes

Exports timetracker results to the external terminovka.sk API. These notes live
here (not as XML comments in `plugin.xml`) by project convention.

## Ownership of the external track ID (issue #34)

The external track ID (a track's ID on terminovka.sk) used to live in the
`race` plugin as `race.race_extid`. It is now **owned by terminovka**, so the
optional `terminovka` plugin can be absent without `race` carrying any
terminovka-specific column or code.

### What terminovka owns

- **Table** `terminovka_track` (`terminovka_sql.php`): `terminovka_track_id` PK,
  `race_id` INT **UNIQUE** (one mapping row per track, used for upsert),
  `ext_id` INT (the terminovka.sk track ID, default 0).
- **Injected field** (`e_admin.php`): an editable "terminovka.sk track ID" field
  on its own **Terminovka** tab in the `race` track edit form.
- **Export** (`e_event.php`): reads `ext_id` from `terminovka_track` by `race_id`
  for `externalTrackId`, falling back to `0` when no mapping exists.
- **Migration** (`terminovka_setup.php`): copies legacy `race.race_extid` values
  into `terminovka_track`, then drops the `race.race_extid` column.

## Step 0 - API verification (bundled Lite 2.4.x source)

1. **Addon interface & discovery.** `e_admin_addon_interface` exists
   (`ehandlers/e107_class.php`) with exactly `config(e_admin_ui $ui)`,
   `load($event, $ids)`, `process(e_admin_ui $ui, $id=0)`. `e_admin.php` addons
   are auto-collected via `e107::getAddonConfig('e_admin', ...)`, which reads the
   `e_admin_list` pref and instantiates the class `{folder}_admin`
   (-> `terminovka_admin`); the optional form class is `{folder}_admin_form`
   (not needed here - plain numeric field). `e_admin` is in the addon scan list,
   so `e_admin_list` is (re)built by `buildAddonPrefLists()`, which runs during
   plugin install/upgrade. **Re-scan requirement:** bumping the version to 1.1
   and running the DB update rebuilds `e_admin_list` automatically. If the file
   is added without an upgrade, a manual plugin re-scan (admin -> Plugins -> scan
   for new addons) is needed before `terminovka` appears in `e_admin_list`.

2. **Host targeting via `getEventName()`.** `getEventName()` simply returns the
   protected `$eventName` (default `null`). After setting
   `protected $eventName = 'race';` on `racetrack_ui` (the event name stays keyed
   to the unchanged `race` table, not the `racetrack` folder — issue #44),
   `getEventName()` returns exactly **`'race'`** for the main controller. The
   sibling controllers `racetrack_point_ui` and `racetrack_price_ui` leave
   `$eventName` unset (-> `null`), so they do **not** collide; the addon guards
   with `=== 'race'`.

3. **Injected field plumbing.** Confirmed in `e_admin_ui::initAdminAddons()`:
   each injected field is force-set to `data = false` (nothing written to the
   `race` table) and registered as `x_{plug}_{key}` -> **`x_terminovka_ext_id`**
   (so it arrives in POST under that name). `process($ui, $id)` is called from
   `_manageSubmit()` after a successful create/edit. `load($event, $ids)` is
   called from `setAdminAddonModel()` for the list view.

4. **Tabs merge.** Confirmed: `$config['tabs']` is merged via
   `$this->tabs[$t] = $tb`, tab keys may be strings, and the field's `'tab'` key
   must equal the tab key. Once tabs are non-empty, every edit field needs a
   `'tab'` - hence every `race` edit field now carries `'tab' => 'race'`.

5. **Plugin upgrade hook.** This Lite version uses `{plugin}_setup.php` with a
   `{plugin}_setup` class and `{what}_{when}` methods. We use
   **`upgrade_post()`** (and `install_post()` for fresh installs onto legacy
   data). Order during upgrade (`e_plugin`/`plugin_class`): `upgrade_pre` ->
   `XmlTables` (SQL diff -> **creates `terminovka_track`** via
   `db_verify::runFix()`) -> ... -> `upgrade_post` (**migration**). So the table
   exists before the migration runs.

## Lite core fix required for #34

`db_verify` does **not** detect/drop "extra" columns (columns present in the DB
but absent from `*_sql.php`) - see the `@todo` at
`db_verify_class.php::prepareResults()`. So removing `race_extid` from
`race_sql.php` alone does **not** drop the column on existing installs. The
terminovka migration therefore performs the `ALTER TABLE race DROP race_extid`
itself, after copying the values (guarded by a column-exists check, so it is
idempotent and a no-op on fresh installs).

`e_admin_form_ui` only called `setAdminAddonModel()` (which runs the addon
`load()`) for the **list view** (`getList()`), not for the **edit form**
(`getCreate()`). Because injected fields are `data = false`, the edit form would
render the field **empty** and a save would overwrite the owned value with 0
(data loss). Aligning with the intended e107 behaviour, `getCreate()` now calls a
companion `setAdminAddonEditModel()` that runs the addon `load()` for the single
edited record. This is the only change outside the `race` / `terminovka` plugins
(`ehandlers/admin_ui.php`).

## Security

- `ext_id` is always `(int)` cast and persisted via the e107 `db` class with
  parameterized data arrays (`_FIELD_TYPES`); no string concatenation of values.
- `load()` validates `$ids` against `^[0-9]+(,[0-9]+)*$` before the `IN (...)`
  clause, even though it originates from core.
- CSRF is handled by the host `e_admin_ui` submit (`e_token`); the addon rides
  the same submit. Export stays server-side only.
