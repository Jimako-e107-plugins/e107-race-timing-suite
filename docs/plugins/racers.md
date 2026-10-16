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

The [on-site registration form](#on-site-registration-form) pads a bib to at least four characters
with leading zeros (`42` is saved as `0042`). The admin **Add racer** form saves the bib exactly as
typed.

## Admin area

Eight items: **Settings**, **List**, **Add racer**, **Categories list**, **Add category**, **Start
lists**, **Racer overview**, **On-site registration**. When `raceevent` is installed, shortcuts to the
admin pages of the other suite plugins follow.

---

### Settings

Plugin preferences, split across three tabs: **Nastavenie** (settings), **Tlač** (print) and
**Country flags** (`LAN_RACERS_ADMIN_053`). The first two tab names are **hardcoded in Slovak**.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Anonymize names? | `LAN_RACERS_ADMIN_005_HELP` | Replaces personal data (names) with unreadable characters | Privacy switch — use it if you must publish start lists without exposing full names. |
| Show team next to the name? | `LAN_RACERS_ADMIN_006_HELP` | Adds the team to the displayed name | |
| Show the local flag next to the name? | `LAN_RACERS_ADMIN_007_HELP` | If a racer is marked as local, the code entered below is shown next to their name. | Works together with **Text for a local racer** below and the **Local** checkbox on the competitor. |
| Text for a local racer | `LAN_RACERS_ADMIN_021` | — | The marker shown next to a local competitor's name (e.g. a club abbreviation). HTML is allowed: the help box on this screen shows an icon as an example (*Example of the local marker*, `LAN_RACERS_ADMIN_008`: `<i class=fas fa-map-marker-alt></i>`). |
| Country codes (for statistics) | `LAN_RACERS_ADMIN_022` | — | Comma-separated list offered in the Nationality dropdown. Default: `SVK,CZE,POL,HUN,RUS,AUS,USA,FRA,AUT,UKR`. |
| Show the country flag next to the name? | `LAN_RACERS_ADMIN_044_HELP` | Shows the flag of the racer's nationality next to the name, using the IOC to ISO code map below. Racers whose nationality is not in the map get no flag. | On the **Country flags** tab (`LAN_RACERS_ADMIN_053`). Off by default. Also adds the flag (as a plain emoji) to the finish/start CSV/XLS exports. The flag font is loaded on front-end pages only: in the admin area, browsers without flag emoji (Chrome / Edge on Windows) show two letters (e.g. `DE`) instead. |
| Flag position | `LAN_RACERS_ADMIN_045_HELP` | Before the surname, or directly after "surname firstname" (before the bib number, the local text and the team). | Options *Before the name* / *After the name* (`LAN_RACERS_ADMIN_046` / `047`). Default: after. |
| Flag size | `LAN_RACERS_ADMIN_048_HELP` | Flag size in pixels (8 to 64). | Default 16; values outside 8–64 are clamped on save. |
| Nationality to flag map | `LAN_RACERS_ADMIN_049_HELP` | How to fill it in: the left box is the code used in the Nationality field (IOC, 3 letters, e.g. GER), the right box is the country code that draws the flag (ISO, 2 letters, e.g. DE). Look the pair up in the "IOC to ISO code reference" below and copy both codes into an empty row. Clear both boxes to remove a row. | One row per code plus 3 empty rows. Invalid rows are dropped with a warning. Under the table: a warning listing the **Country codes** that have no flag yet (`LAN_RACERS_ADMIN_056`) and a collapsible IOC → ISO reference with country names (`LAN_RACERS_ADMIN_054`). Default: SVK=SK, CZE=CZ, POL=PL, HUN=HU, RUS=RU, AUS=AU, USA=US, FRA=FR, AUT=AT, UKR=UA, GER=DE, SUI=CH, NED=NL, IND=IN. |
| Age calculation method | `LAN_RACERS_ADMIN_027` | — | *Today* or *As of Jan 1* (`LAN_RACERS_ADMIN_028` / `029`). Used for the age shown next to the date of birth on the on-site registration form. The category suggestions on that form always use the age as of today — see [Notes and limitations](#notes-and-limitations). |
| Allow manual entry of participants | `LAN_RACERS_ADMIN_030` | — | Switches the [on-site registration form](#on-site-registration-form) on or off (on by default). When off, the form and the admin **On-site registration** screen show *Manual entry of participants is not allowed.* |
| Počet znakov | *hardcoded* | 11 - dve tisiciny | Intended as the number of characters used when displaying a time. **Not read by any page at present** — changing it has no effect. Label and help are **hardcoded in Slovak**. |
| CSS pre export | *hardcoded* | — | On the **Tlač** tab. Intended for styling printed / exported start lists. **Not read by any page at present.** Label **hardcoded in Slovak**. |
| HTML pre export | *hardcoded* | — | On the **Tlač** tab. Intended as a markup wrapper for print / export. **Not read by any page at present.** Label **hardcoded in Slovak**. |
| Text for a local racer (print) | `LAN_RACERS_ADMIN_021` | — | On the **Tlač** tab: a second copy of the local-racer text, meant for print. **Not read by any page at present** — the marker shown everywhere is the one on the first tab. |

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

| Panel | Links |
| --- | --- |
| *Start lists by track and category* (`LAN_RACERS_ADMIN_041`) | *All tracks (all categories)* (`LAN_RACERS_ADMIN_036`) |
| One per track: *track name (sef) — Start list* (`LAN_RACERS_ADMIN_040`) | *Start list — all categories* (`LAN_RACERS_ADMIN_037`), then one link per category of the track, sorted by name, with its SEF shown in grey |

Every link opens the [start list page](#start-list-page) in a new tab. A track without categories
shows *No categories for this track.* (`LAN_RACERS_ADMIN_039`); with no tracks at all the screen
shows only *No tracks found.* (`LAN_RACERS_ADMIN_038`).

---

### Racer overview

One button, *Open the racer overview* (`LAN_RACERS_ADMIN_042`), which opens the
[racer list page](#racer-list-page) in a new tab.

---

### On-site registration

The check-in desk screen: sign someone up on race day, at the event. Distinct from `racereg`,
which is the **online** sign-up before the event.

The two are deliberately separate. `racereg` collects sign-ups (which may still be unpaid or
unapproved); `racers` holds the people who will actually start.

What the screen shows depends on **Allow manual entry of participants** in Settings:

| Setting | Screen shows |
| --- | --- |
| On | *Caution: manual entry of participants without access control is allowed.* (`LAN_RACERS_ADMIN_032`) and an **On-site registration** button (`LAN_RACERS_GLOBAL_029`) that opens the [form](#on-site-registration-form) in a new tab |
| Off | *Manual entry of participants is not allowed.* (`LAN_RACERS_ADMIN_031`) |

## Front-end

| Page | URL | Route key | File |
| --- | --- | --- | --- |
| Racer list | `/racers/list/` | `index` | `racers.php` |
| Start list | `/startlist/{track sef}/{category sef}/` — `all` for every track / every category | `startlist` | `startlist.php` |
| On-site registration | `/racers/registracia/` | `registracia` | `registracia.php` |

The racer list and the on-site registration form need an **admin login**; other visitors are
redirected away. The start list is public.

`racers` also ships a **categories menu** (`race_categories_menu.php`) for the Menu Manager,
linking each category to its start list and result list.

For the site navigation there is a Site Links function **Štartovacie listiny** (name hardcoded in
Slovak). It adds one sub-link per track (all categories) and one per track + category; the
category links open in a new tab.

### Start list page

All competitors with a bib on the selected track and category. Columns: *#*, *Bib no.*, *Name*,
*Type*, *Nationality*, *Track*, *Start time* (`LAN_RACERS_STARTLIST_*`).

- **Row colour** — the category colour, but only for competitors marked **Nastúpil** (checked
  in). An uncoloured row is someone not marked yet.
- **Start time** — the time of day of the competitor's crossing at the start, read from
  `racetiming`. An empty cell means no start crossing yet. Without `racetiming` the column stays
  empty. A row with a start time but no colour is a competitor who started but was never marked
  as checked in.
- **Navigation** — an *All* button (`LAN_RACERS_STARTLIST_ALL`, every track and category) and one
  button per track. Switching track keeps the selected category. The buttons open in a new tab.

### Racer list page

A table of every competitor, opened from **Racer overview** in the admin area. Each bib has an
edit button that opens the competitor in the [on-site registration form](#on-site-registration-form)
(so it only works while **Allow manual entry of participants** is on). Search and sorting are
available only when `racereports` is installed; their labels are **hardcoded in Slovak**.

### On-site registration form

The check-in desk form. It is off unless **Allow manual entry of participants** is on.

- **Category suggestions.** When the date of birth or the type changes, the category list is
  refilled with the categories that match the type and the age (only those of the selected track, once a track is chosen). Choose the
  track first — changing only the track does not refresh the list. If nothing matches, the list
  offers *Zadám neskôr* ("enter later", hardcoded in Slovak).
- **Bib.** Padded to at least four characters with leading zeros. Adding a competitor with a bib that is
  already in use is refused (*The entered bib number is already in use; the data was not saved.*,
  `LAN_RACERS_FRONT_005`); editing an existing competitor does not check this.

## Notes and limitations

- **One track, one category per competitor.** Someone running two tracks is two competitor records
  with two bibs.
- **The Categories screen is restricted to the main administrator.** A user with plugin-admin rights
  cannot reach it. This is deliberate, not an oversight.
- **Several admin labels are hardcoded in Slovak** (the Categories screen, three Settings fields,
  the Settings tab names, the "Nastúpil" column) and do not follow the site language. So are a few
  front-end texts: the Site Links function name, the categories-menu buttons, the on-site form's
  validation messages and *Zadám neskôr*, and the racer list's search labels.
- **Age calculation method only affects the displayed age.** The category suggestions on the
  on-site registration form always use the age as of today, even when *As of Jan 1* is selected.
