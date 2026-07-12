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

The bib comes from the reader's tag column (`TagID` by default) and is treated as a **string** — a
chip mapped to bib `0042` stays `0042`.

Records already imported are skipped, so the import can run repeatedly without creating duplicates.

## Admin area

Two screens: **Main configuration** and **Test database access**.

---

### Main configuration

#### Plugin settings — for the organizer

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Plugin Active | `LAN_RACETRACKING_PREF_001_HELP` | When disabled, the import cron does not run and the manual import cannot be started. | The master switch. Turn it off outside race day. |
| Cron disabled | `LAN_RACETRACKING_PREF_002_HELP` | When enabled, the cron returns quietly without doing anything, even if the plugin is active. | Stops the scheduled import while leaving manual import available. |
| Seconds for manual import from the reader | `LAN_RACETRACKING_PREF_REFRESH_HELP` | 0 - import will not run | How often the manual-import page re-runs. **0 means it never runs** — this is the most common reason "nothing imports". |

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
| Table | `LAN_RACETRACKING_TABLE` | `race_tracking` |
| Name Field | `LAN_RACETRACKING_FIELDNAME` | `Meno` |
| Start Number Field | `LAN_RACETRACKING_FIELDNUMBER` | `TagID` |

The defaults are those of the reader system this plugin was built against. Your provider's column
names will differ — that is what these fields are for.

---

### Test database access

Runs the connection and reports what it found. **Do this before race day, not on race morning.**

> If the connection fails and the settings look right, check the password: it must be entered
> literally, without HTML entities (`LAN_RACETRACKING_HELP_001`).

---

### The import itself

Two ways it runs:

| | |
| --- | --- |
| **Scheduled** | An e107 cron task, *Import RFID Tag Data*. It imports start and finish records that do not exist yet. It must be enabled in e107's Schedule Tasks Manager. |
| **Manual** | A page that re-runs the import on the interval you set. The link is on the configuration screen (*Link for manual import*). |

The main administrator can also trigger the cron by hand from the Schedule Tasks Manager.

## Notes and limitations

- **The chip data is not yours.** It lives in the provider's database; this plugin only copies from
  it. If their system is down, nothing imports.
- **The scheduled import needs e107 cron to actually be running** on the server. A cron task that is
  never fired imports nothing, silently.
- **Refresh interval 0 = disabled.** Easy to overlook.
- The plugin was built for one specific reader system. Column names are configurable, but the shape
  of the data (one row per chip, a column per reading point) is assumed.
