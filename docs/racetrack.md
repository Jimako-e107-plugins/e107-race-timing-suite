# racetrack — tracks, checkpoints, prices

## What it does

`racetrack` defines the **structure of your event**: the tracks (courses) competitors run, the
checkpoints along each track, the entry prices, and the archive of finished editions.

Everything else refers back to it. A competitor belongs to a track; a measured time belongs to a
checkpoint; a registration fee comes from a track's price tier. Set this up before adding anyone.

## Requires / required by

| | |
| --- | --- |
| **Requires** | `raceevent` |
| **Required by** | `racers`, `racereg`, `racetiming`, `racereports` |
| **Owns** | `race` (tracks), `race_point` (checkpoints), `race_price` (price tiers), `race_archive` (archive) |

## Installation notes

Install after `raceevent`.

The **Registration** tab on the track form appears only when `racereg` is installed. Without it the
track form shows just the Track tab — the registration fields are hidden, not missing.

## Admin area

The plugin's admin menu has four areas: **Track list**, **Checkpoints**, **Price tiers** and
**Archive**. Each has a list screen and an "Add" screen.

---

### Track list

The tracks (courses) of the event. The form has two tabs.

#### Tab: Track

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Title | `LAN_TITLE` (core) | — | The track name shown everywhere: start lists, results, registration. |
| Track SEF URL | `LAN_ADMIN_RACE_001_HELP` | Part of the URL where the race data is shown. Current site + functionality + SEF URL. | Auto-filled from the title. Becomes part of `/pretek/{id}/{sef}`. |
| Archive SEF URL | `LAN_ADMIN_RACE_002_HELP` | Part of the URL where the race archive is shown. Current site + archive SEF URL. It can differ from the active race SEF URL and should contain the year because of races repeated in following years. | **Put the year in it.** Next year's edition of the same track needs a different archive URL, otherwise the two editions collide. |

#### Tab: Registration

> Shown only when `racereg` is installed.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Capacity | `LAN_ADMIN_RACE_CAPACITY_HELP` | Maximum number of racers on the start list. Ignored when unlimited capacity is on. | 0 with *Unlimited* off means nobody can be placed — you get a warning on save. |
| Unlimited capacity | `LAN_ADMIN_RACE_UNLIMITED_HELP` | When on, capacity is not checked and everyone is placed on the start list. | |
| Requires approval | `LAN_ADMIN_RACE_APPROVAL_HELP` | When on, sign-ups wait for approval and are placed on the start list only when approved (not automatically by capacity). | Use for events where you vet entries. |
| Registration closed | `LAN_ADMIN_RACE_CLOSED_HELP` | When on, this track cannot be signed up for. | Per track — you can close one track while others stay open. |

#### Warnings on save

Both are **warnings only** — the track is saved either way:

| Situation | LAN key |
| --- | --- |
| The track is open for registration, capacity is 0, and *Unlimited* is off → nobody can be placed | `LAN_ADMIN_RACE_CAP_WARN` |
| The track is open for registration but has no price tier → sign-ups are treated as free | `LAN_ADMIN_RACE_FREE_WARN` |

---

### Checkpoints

The points along a track where times are recorded: the start, any intermediate points, and the
finish.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Pretek (track) | *hardcoded* | — | Which track(s) the checkpoint belongs to. A checkpoint can be shared by several tracks. |
| Code | *hardcoded* | — | **The start checkpoint must be coded `start` and the finish `finish`.** Any other code makes it an ordinary intermediate checkpoint and the elapsed-time calculation will not find its start and finish. |
| DB Import Field | *hardcoded* | DB pole z import tabuľky | Only relevant with `racerfid`: which column of the RFID import table feeds this checkpoint. |
| Title | `LAN_TITLE` (core) | — | The checkpoint name shown in results and split times. |
| Password | *hardcoded* | — | Guards the mobile entry screen for this checkpoint, so a marshal can only enter times for their own point. |
| Order | `LAN_ORDER` (core) | — | The order checkpoints appear in along the track. Determines the order of the split columns in reports. |

> **The `start` / `finish` codes are the single most common setup mistake.** They are not labels —
> the timing engine looks them up by code.

---

### Price tiers

Date-tiered entry fees. A track can have several tiers; the one that applies is the tier with the
**latest "Valid from" date that is already in the past**.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Track | `LAN_ADMIN_PRICE_TRACK` | — | Which track this price belongs to. |
| Price | `LAN_ADMIN_PRICE_VALUE_HELP` | Amount in EUR (e.g. 15.00). | |
| Valid from | `LAN_ADMIN_PRICE_FROM_HELP` | Date and time from which this price applies. The tier with the latest date that is <= the current time applies to a sign-up. | Date **and** time. |

**Example — early bird pricing**

| Valid from | Price |
| --- | --- |
| 1 January | 15.00 |
| 1 March | 20.00 |
| 1 May | 25.00 |

Someone signing up on 15 March pays 20.00. A track with **no** price tier at all is treated as
free.

---

### Archive

A finished track's results, frozen. The archive stores both a data snapshot and the rendered HTML,
so an archived edition keeps showing exactly what it showed on race day — even after you clear the
season and set up the next one.

#### Creating an archive

From the **Track list**, each track row has an **Archive** button (`LAN_ADMIN_ARCHIVE_ARCHIVOVAT`).
It generates the snapshot from the current results.

> Archiving requires **`racereports`** to be installed — it is what produces the result content.
> Without it you get: *"The Racereports plugin is not installed - the archive cannot be generated."*
> (`LAN_ADMIN_ARCHIVE_MSG_NO_RR`)

#### Archive list

| Field | LAN key | Notes |
| --- | --- | --- |
| Track | `LAN_ADMIN_ARCHIVE_TRACK` | Which track the snapshot came from — or **Unlinked archive** (`LAN_ADMIN_ARCHIVE_UNLINKED`). |
| Title | `LAN_TITLE` (core) | Shown on the public archive page. |
| Sef | *hardcoded* | The URL segment: `/archiv/{sef}/`. |
| Desc | *hardcoded* | Free description of the edition. |
| Data / HTML | *hardcoded* | The frozen snapshot itself. Do not hand-edit unless you know what you are doing. |
| Created / Updated | `LAN_ADMIN_ARCHIVE_CREATED` / `..._UPDATED` | |

#### Row actions

| Action | LAN key | What it does |
| --- | --- | --- |
| **Regenerate** | `LAN_ADMIN_ARCHIVE_REGENERATE` | Rebuilds the snapshot from the current data. Only available while the archive is **linked to a track**. |
| **View** | `LAN_ADMIN_ARCHIVE_VIEW` | Shows the frozen snapshot. **Never** regenerates. |

#### Linked vs unlinked — the thing to understand

| | Linked (Track is set) | Unlinked |
| --- | --- | --- |
| Regenerate available | Yes | No |
| Affected by clearing the season | **Yes** — the track it points at can still change it | No |
| Public archive page | Works | Works |

**Unlink an archive once the edition is truly finished.** That is what makes it safe: the snapshot
can no longer be regenerated or disturbed by next season's data. This is also why the
[Maintenance](raceevent.md#maintenance) screen in `raceevent` warns you when archives are still
linked to a track before you clear the season.

(`LAN_ADMIN_ARCHIVE_NOTE` says the same thing on the screen itself.)

## Front-end

### Track page

| | |
| --- | --- |
| **URL** | `/pretek/{id}/{sef}` |
| **File** | `racetrack/pretek.php` |
| **Route key** | `race` |

The public page of a single track.

### Archive page

| | |
| --- | --- |
| **URL** | `/archiv/{sef}/` |
| **File** | `racetrack/page_archive.php` |
| **Route key** | `archiv` |

The frozen results of a past edition. The results table is sortable and searchable (it uses the
DataTables assets from `racereports`, so that plugin should be installed).

### Menus

`racetrack` ships a menu you can place through the Menu Manager (`race_points_menu.php`).

## Notes and limitations

- **The `start` and `finish` checkpoint codes are mandatory and case-sensitive.** Everything about
  elapsed time depends on them.
- **The archive stores rendered HTML.** A later change to your theme will not restyle an already
  archived edition.
- **A track cannot be deleted while data still points at it.** The Maintenance screen enforces
  this: tracks can only be cleared once times, racers, results and categories are empty.
- **Known issue:** several field labels on the Checkpoints and Archive screens are hardcoded in the
  code instead of coming from the language files, so they stay in English (one help text is in
  Slovak) regardless of the site language.
