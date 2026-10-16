# racerfid — RFID import

## What it does

`racerfid` reads passing times from an **RFID chip system** and feeds them into the timing engine, so
nobody has to key bibs in by hand at the start and finish.

It does not talk to the chips or the readers. The chip company runs its own system and writes to its
own database; `racerfid` **reads that database** on a schedule and copies the passings across into
`race_time`.

## Requires / required by

| | |
| --- | --- |
| **Requires** | Nothing — it is an independent plugin |
| **Required by** | Nothing |
| **Owns** | `race_tracking` (the local copy of the reader's records) |

`racerfid` installs on its own and does nothing harmful if the timing plugins are absent — it simply
has nowhere to write. When they are present, it writes into `racetiming`'s `race_time`.

At run time the import needs **both `racetrack` and `racetiming`** installed. If either is missing,
the import does nothing and shows the warning `LAN_RACETRACKING_DEPS_MISSING`.

## Installation notes

You need **two things from your chip provider** before this is useful:

1. **Database access** — host, port, user, password, database name, table name.
2. **Which columns** hold the bib and the passing times.

If your chip provider does not give you database access, this plugin cannot help you.

## How the mapping works

The reader's table has one row per chip, with a column per reading point. `racerfid` maps those
columns onto your checkpoints using the **DB Import Field** you set on each checkpoint in
`racetrack`.

```
reader table column          racetrack checkpoint          race_time
─────────────────────────────────────────────────────────────────────
Start               ──►      checkpoint with              ──►  passing
                             DB Import Field = Start           at start

Ciel                ──►      checkpoint with              ──►  passing
                             DB Import Field = Ciel            at finish
```

Every checkpoint that has a DB Import Field is imported — not only start and finish (intermediate
checkpoints can use columns such as `K01`). Checkpoints without a DB Import Field are skipped.

The bib comes from the reader's `TagID` column and is treated as a **string** — a chip mapped to bib
`0042` stays `0042`. The time is copied as it is, except that a decimal comma is turned into a dot.

Records already imported are skipped, so the import can run repeatedly without creating duplicates.
A bib that already has a time at a checkpoint is skipped there — an existing time is **never
overwritten** by the import.

Table and column names may contain only letters, digits and the underscore. Any other name is
refused with the error `LAN_RACETRACKING_INVALID_IDENTIFIER`, and that checkpoint is skipped.

## Admin area

Three menu entries: **Preferences** (plugin settings), **Main configuration** (database access) and
**Test database access**. The last two are visible only to the main administrator.

---

### Preferences

#### Plugin settings — for the organizer

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Plugin Active | `LAN_RACETRACKING_PREF_001_HELP` | When disabled, the import cron does not run and the manual import cannot be started. | The master switch. Turn it off outside race day. |
| Cron disabled | `LAN_RACETRACKING_PREF_002_HELP` | When enabled, the cron returns quietly without doing anything, even if the plugin is active. | Stops the scheduled import while leaving manual import available. |
| Seconds for manual import from the reader | `LAN_RACETRACKING_PREF_REFRESH_HELP` | 0 - import will not run | How often the manual-import page re-runs. **0 means it never runs** — this is the most common reason "nothing imports". |

### Main configuration

#### Database access — main administrator only

> **These fields are visible only to the main administrator** (`LAN_RACETRACKING_HELP_002`). A plugin
> administrator can turn the plugin off and disable the cron, but cannot see or change the database
> credentials.

| Field | LAN key | Default |
| --- | --- | --- |
| Server | `LAN_RACETRACKING_SERVER` | |
| Port | `LAN_RACETRACKING_PORT` | |
| Username | `LAN_RACETRACKING_USERNAME` | |
| Password | `LAN_RACETRACKING_PASSWORD` | |
| Database | `LAN_RACETRACKING_DATABASE` | |
| Table Prefix | `LAN_RACETRACKING_PREFIX` | |
| Table | `LAN_RACETRACKING_TABLE` | `race_tracking` |
| Name Field | `LAN_RACETRACKING_FIELDNAME` | `Meno` |
| Start Number Field | `LAN_RACETRACKING_FIELDNUMBER` | `TagID` |

The defaults are those of the reader system this plugin was built against. The table prefix is put
in front of the table name.

> **Only the test uses Table, Name Field and Start Number Field.** The import itself always reads the
> table *prefix* + `race_tracking` and takes the bib from the `TagID` column. Name Field is only shown
> in the test summary. The password is an ordinary text field, so it is visible on screen.

---

### Test database access

Runs the connection and reports what it found. **Do this before race day, not on race morning.**

What it shows, in order:

1. A summary of the settings — server, database, table, name field, start number field. The username
   and password are never shown.
2. The total number of records, and how many have a filled start number field.
3. For each checkpoint (in checkpoint order): the field it is imported from and how many records have
   both a bib and a value there — or *not imported* if it has no DB Import Field. This part is
   skipped, with the warning `LAN_RACETRACKING_DEPS_MISSING`, when `racetrack` or `racetiming` is
   missing.
4. The whole reader table, with the fixed columns ID, TagID, Meno, Start, Ciel, Proces and K01–K08.

If the connection fails, the database error is shown instead (*Connection failed: …*).

> If the connection fails and the settings look right, check the password: it must be entered
> literally, without HTML entities (`LAN_RACETRACKING_HELP_001`).

---

### The import itself

Two ways it runs:

| | |
| --- | --- |
| **Scheduled** | An e107 cron task, *Import RFID Tag Data*. It imports the passings that do not exist yet, for every checkpoint with a DB Import Field. It must be enabled in e107's Schedule Tasks Manager. |
| **Manual** | A page that re-runs the import on the interval you set. The link is the *Manual refresh* button in the help panel of the Preferences screen (*Link for manual import*). |

The manual page needs an administrator login with access to the plugin, and it keeps running only
while it is open: it reloads itself every *Seconds for manual import* and shows *Import finished*
with the time. With an interval of 0 it shows *Refresh interval is not set*; with Plugin Active off,
*Import is not activated*. *Cron disabled* does not affect it.

The main administrator can also trigger the cron by hand from the Schedule Tasks Manager.

## Notes and limitations

- **The chip data is not yours.** It lives in the provider's database; this plugin only copies from
  it. If their system is down, nothing imports.
- **The scheduled import needs e107 cron to actually be running** on the server. A cron task that is
  never fired imports nothing, silently.
- **Refresh interval 0 = disabled.** Easy to overlook.
- The plugin was built for one specific reader system. The checkpoint columns are configurable (DB
  Import Field), but the table name `race_tracking`, the `TagID` bib column and the shape of the data
  (one row per chip, a column per reading point) are assumed.
