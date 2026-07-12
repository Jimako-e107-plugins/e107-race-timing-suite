# racers — competitors and categories

## What it does

`racers` holds the **people**: the competitor roster and the categories they compete in. One
competitor belongs to **one track and one category**.

It produces the **start lists** — the roster grouped by track and category, ready to publish online
or print — and provides an **on-site registration** screen for signing people up at check-in on race
day.

## Requires / required by

| | |
| --- | --- |
| **Requires** | `raceevent`, `racetrack` |
| **Required by** | `racereports` |
| **Owns** | `racer` (competitors), `race_category` (categories) |

## Installation notes

Install after `racetrack` — a competitor cannot exist without a track to run on.

**Set up your categories before adding competitors.** The competitor form asks for a category and
there is no useful default.

## The bib number

The bib (start number) is stored as a **string**, not a number. `0042` and `42` are different bibs
and the leading zeros are preserved everywhere — in the timing engine, in reports, in the RFID
import.

Do not think of it as "competitor number 42". Think of it as a **code printed on a shirt**.

## Admin area

Six items: **Settings**, **List**, **Add racer**, **Categories**, **Start lists**, **Racer
overview**, **On-site registration**.

---

### Settings

Plugin preferences, split across two tabs.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Anonymize names? | `LAN_RACERS_ADMIN_005_HELP` | Replaces personal data (names) with unreadable characters | Privacy switch — use it if you must publish start lists without exposing full names. |
| Show team next to the name? | `LAN_RACERS_ADMIN_006_HELP` | Adds the team to the displayed name | |
| Show the local flag next to the name? | `LAN_RACERS_ADMIN_007_HELP` | If a racer is marked as local, the code entered below is shown next to their name. | Works together with **Text for a local racer** below and the **Local** checkbox on the competitor. |
| Text for a local racer | `LAN_RACERS_ADMIN_021` | — | The marker shown next to a local competitor's name (e.g. a club abbreviation). |
| Country codes (for statistics) | `LAN_RACERS_ADMIN_022` | — | Comma-separated list offered in the Nationality dropdown. Default: `SVK,CZE,POL,HUN,RUS,AUS,USA,FRA,AUT,UKR`. |
| Age calculation method | `LAN_RACERS_ADMIN_027` | — | How age is worked out when placing a competitor into an age-bounded category. |
| Allow manual entry of participants | `LAN_RACERS_ADMIN_030` | — | Enables adding competitors by hand (as opposed to importing them). |
| Počet znakov | *hardcoded* | 11 - dve tisiciny | Number of characters used when displaying a time. Label and help are **hardcoded in Slovak**. |
| CSS pre export | *hardcoded* | — | Styling used when printing / exporting start lists. Label **hardcoded in Slovak**. |
| HTML pre export | *hardcoded* | — | Markup wrapper used when printing / exporting. Label **hardcoded in Slovak**. |

---

### Categories

The categories competitors are ranked in — typically by gender and age band, per track.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Trať (track) | *hardcoded* | — | Which track the category belongs to. Categories are **per track**. |
| Názov kategórie | *hardcoded* | — | The category name shown on start lists and results. |
| Gender | *hardcoded* | — | Restricts the category to one gender, or leave open. |
| Vek od / Vek do | *hardcoded* | — | Age band. Combined with **Age calculation method** in Settings. |
| Farba kategórie | *hardcoded* | — | Colour used to distinguish the category visually. |
| SEF URL | `LAN_SEFURL` (core) | — | The URL segment for this category's start list and result list. |

> Labels on this screen are **hardcoded in Slovak** and do not follow the site language.

---

### List / Add racer

The competitor roster.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Bib number | `LAN_RACERS_ADMIN_026` | — | **A string.** Leading zeros matter. This is what the timing engine keys on. |
| First name | `LAN_RACERS_ADMIN_009` | — | |
| Surname | `LAN_RACERS_ADMIN_010` | — | |
| Date of birth | `LAN_RACERS_ADMIN_011_HELP` | Enter the date of birth in D.M.YYYY format or pick it from the calendar | Used to place the competitor in an age-bounded category. |
| Track | `LAN_RACERS_ADMIN_023_HELP` | Which track they will run on | One track per competitor. |
| Category | `LAN_RACERS_ADMIN_025` | — | One category per competitor. Must belong to the same track. |
| Type (Individual/Relay) | `LAN_RACERS_ADMIN_014` | — | |
| Nationality | `LAN_RACERS_ADMIN_017` | — | Values come from **Country codes** in Settings. |
| Local | `LAN_RACERS_ADMIN_018` | — | Marks a home competitor; shown with the marker from Settings. |
| Team / Club | `LAN_RACERS_ADMIN_019` | — | Shown next to the name if **Show team** is on. |
| Special tags / Relay member list | `LAN_RACERS_ADMIN_020` | — | For relay teams: the members. |
| Ext. ID | `LAN_RACERS_ADMIN_013_HELP` | External application ID (terminovka.sk) for the API | Filled by the `terminovka` export. Leave alone unless you use it. |
| Nastúpil (started) | *hardcoded* | — | Whether the competitor actually turned up. Label **hardcoded in Slovak**. |

---

### Start lists

The roster grouped by track and category, with links to each published start list. Use this to
check the lists before race day and to print them.

Start lists show the **start time** as an extra column when `racetiming` is installed — who has
already reported to the start.

---

### Racer overview

A quick read-only view of the roster, linking to the public racer list.

---

### On-site registration

The check-in desk screen: sign someone up on race day, at the event. Distinct from `racereg`,
which is the **online** sign-up before the event.

The two are deliberately separate. `racereg` collects sign-ups (which may still be unpaid or
unapproved); `racers` holds the people who will actually start.

## Front-end

| Page | URL | Route key | File |
| --- | --- | --- | --- |
| Racer list | `/racers/list/` | `index` | `racers.php` |
| Start list | *(per track + category)* | `startlist` | `startlist.php` |
| On-site registration | | `registracia` | `registracia.php` |

`racers` also ships a **categories menu** (`race_categories_menu.php`) for the Menu Manager,
linking each category to its start list and result list.

## Notes and limitations

- **One track, one category per competitor.** Someone running two tracks is two competitor records
  with two bibs.
- **The Categories screen is restricted to the main administrator.** A user with plugin-admin rights
  cannot reach it. This is deliberate, not an oversight.
- **Several admin labels are hardcoded in Slovak** (the Categories screen, three Settings fields,
  the "Nastúpil" column) and do not follow the site language.
