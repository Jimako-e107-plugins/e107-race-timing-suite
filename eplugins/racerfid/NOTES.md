# racerfid - developer notes

These notes live here (not as XML comments in `plugin.xml`) by project
convention.

## `race_tracking` is solely owned by racerfid

`race_tracking` is the raw RFID staging table written by the chip reader. Its
**single source of truth** is `racerfid_sql.php` (the `CREATE TABLE
race_tracking` block), and `racerfid` is its **sole owner**: no other plugin
declares, manages, or reads it.

- `timetracker` no longer declares/manages/reads `race_tracking`. Its
  `CREATE TABLE race_tracking` block, the `tracking` admin mode, the legacy
  `get_starttime_from_tracker()` reader, and the dead commented `race_tracking`
  SELECTs were all removed.
- `raceevent`'s season maintenance still cleans `race_tracking` **by table
  name** (not by plugin dependency). That cleanup is guarded by a table-exists
  check, so it is a silent no-op when `racerfid` is not installed - no
  `raceevent -> racerfid` dependency is introduced.

The importer (`includes/import.php`) reads `race_tracking` from an **external**
database (raw PDO) and writes `race_time` into the local e107 db, mapped via
`racetrack`'s `race_point` table. That logic is unchanged.

## Dependencies - none (independent / optional)

`plugin.xml` declares **no** `<dependencies>`. `racerfid` stays fully
independent and freely installable: it never blocks at install time on any
other plugin being present.

Its inputs are resolved at **runtime**, not install time:

- `racetrack`'s `race_point` table - the importer maps reader records to
  checkpoints.
- the timing table `race_time` - the importer's write target (currently owned
  by `timetracker`; its move to a future `racetiming` plugin is a later task).
- `raceevent` prefs - read when present.

If any of these is absent the import is a guarded no-op: the
`dependenciesMet()` check in `includes/import.php` gates the importer, so a
missing input is handled gracefully at runtime rather than as an install-time
dependency. Keep that guard.

## Table / folder name mismatch (accepted debt)

The table is `race_tracking`, which matches neither the old folder name
(`racetracking`) nor the new one (`racerfid`). The mismatch is left as-is to
avoid a data migration; see the root `NOTES.md` for the rename history.
