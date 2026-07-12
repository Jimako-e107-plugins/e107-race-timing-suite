# racereports — results and reports

## What it does

`racereports` is **why you run the suite**. It is where measured times become public results: the
live board people watch during the race, the rankings, the splits, the finish lists you print and
hand out.

It stores nothing of its own. Every page is calculated on the spot from the raw passings in
`racetiming`, joined with names and categories from `racers` and checkpoints from `racetrack`.
That is deliberate: correct a time in `racetiming` and every report reflects it immediately, with
nothing to rebuild.

## Requires / required by

| | |
| --- | --- |
| **Requires** | `raceevent`, `racetrack`, `racers`, `racetiming` |
| **Required by** | Nothing — but `racetrack` needs it to generate the archive |
| **Owns** | No tables |

## Installation notes

Install **last** — it needs all four plugins above.

It is also what generates the **archive** snapshot in `racetrack`. Without `racereports` installed,
the Archive button there cannot produce anything.

## The nine reports

Each report has an **admin screen** (which lists the links) and a **public page** (the report
itself). The admin screen never shows results — it is a link directory. You open the public page.

| Report | What it answers | Public URL |
| --- | --- | --- |
| **Online results** | Who is currently leading? | `/online/{track}/{category}/` |
| **Arrivals board** (dobeh) | Who just came in? | `/dobeh/{track}/{checkpoint}/` |
| **Full results** (aktuálne) | Everything for one track, all checkpoints | `/aktualne/{track id}/` |
| **Results — finish** | The final classification | `/finish/{track}/{category}/` |
| **Results — SUT** | Finishers of one track, in the format required by the Slovak Ultra Trail series | `/stu/{track id}/` |
| **Start list** | Who started, and when | `/start/{track}/{category}/` |
| **Checkpoint times** | All times at one checkpoint | `/point/{track}/{checkpoint}/` |
| **Racer progression** | One competitor across all checkpoints | `/racer/{bib}/` |
| **Segment (Od–Do)** | Time between two chosen checkpoints | `/custom/{track}/{point 1}/{point 2}/` |

Each accepts `all` (or `komplet`) in place of a category to show every category on one page.

---

### The two you leave on a screen during the race

**Online results** (`LAN_ADMIN_RACEREPORTS_005`) — the standings, auto-refreshing. This is the page
you project in the finish area or share with spectators.

**Arrivals board** (`LAN_ADMIN_RACEREPORTS_108`) — who has just crossed a given checkpoint,
auto-refreshing on its own interval. Useful at the finish line and for the announcer.

Both refresh on a timer you set in Settings, and both accept `?refresh=` in the address, which
overrides the setting.

### The ones you publish afterwards

**Results — finish** — the classification per track and category. This is the result list.

**Results — SUT** — a **Slovak-specific report**. It produces the finishers of a track in the format
expected by the [Slovak Ultra Trail](https://www.slovakultratrail.sk/) series. If your event is not
part of that series, you will not need it — use the finish report instead.

It has its **own** decimal-places setting, separate from the other reports.

**Start list** — who started and at what time. (Different from the start list in `racers`, which is
the roster; this one shows recorded start times.)

### The ones you use to check and explain

**Checkpoint times** — everyone's time at one point. Use it when a marshal reports a problem.

**Racer progression** — one competitor's whole race, checkpoint by checkpoint. Use it when someone
asks "what happened to my time?".

**Segment (Od–Do)** — elapsed time between any two checkpoints. Use it for a climb, a special
stage, a prime.

**Full results (aktuálne)** — everything for one track in one table. This is also the report the
**archive** is generated from.

## Admin area

Ten items: **Settings**, plus one link-directory screen per report.

---

### Settings

Grouped into four tabs.

#### Tab: Decimals

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Results — sub-second decimal places | `LAN_ADMIN_RACEREPORTS_111` | 0-3. Applies to the result reports (online, checkpoints, finish, start, segment, arrivals). The data is always stored at 3 decimals (ms); this is display only (truncated, not rounded). SUT has its own setting; the Aktuálne (overview) report is not governed by this. | **Display only.** Lowering it does not destroy precision — the milliseconds stay in the database. |
| SUT time — decimal places | `LAN_ADMIN_RACEREPORTS_071` | Decimal places for finish times on the SUT (finishers) report. 0 = whole seconds (HH:MM:SS), as before. | Deliberately separate from the setting above. |

#### Tab: Coloring

| Field | LAN key | Admin help text |
| --- | --- | --- |
| SUT — colour categories | `LAN_ADMIN_RACEREPORTS_073` | Off = clean results list with no category background colours. On = per-category row background on the SUT (finishers) report. |
| Finish — colour categories | `LAN_ADMIN_RACEREPORTS_077` | On (default) = per-category row background on finisher rows of the finish (results) report. Off = clean results list with no category colours. Ended rows (DNF/DSQ/DNS) are never coloured. |
| Start — colour categories | `LAN_ADMIN_RACEREPORTS_079` | On (default) = per-category row background on starter rows of the start (start list) report. Off = clean list with no category colours. Not-started rows are never coloured. |

The colours come from the **category colour** set in `racers`.

#### Tab: Refresh

| Field | LAN key | Admin help text |
| --- | --- | --- |
| Online — auto-refresh interval (s) | `LAN_ADMIN_RACEREPORTS_075` | 0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence. |
| Arrivals — auto-refresh interval (s) | `LAN_ADMIN_RACEREPORTS_094` | 0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence. Separate from the online interval - the arrivals board is paced on its own. |

#### Tab: Other

| Field | LAN key | Admin help text |
| --- | --- | --- |
| Finish — category column | `LAN_ADMIN_RACEREPORTS_096` | Off (default) = no category column. On = show a column with the category name in the finish (results) list and its CSV/XLS export. |

---

### Report screens

The nine remaining menu items are **link directories**, one per report type. Each lists every
combination that exists — every track, every category, every checkpoint — with a link to the public
page, plus an "all categories" and "all tracks on one page" link where that makes sense.

They deliberately show no results themselves. Their job is to save you from assembling URLs by hand.

When there is nothing to link to yet you get: *No races yet* / *No categories yet* / *No checkpoints
yet* (`LAN_ADMIN_RACEREPORTS_010`–`012`).

## Front-end

Beyond the nine report pages, `racereports` provides **site links** so you can put results straight
into your website menu: a link per track and category for the online, finish, start and checkpoint
reports.

The results tables are sortable and searchable in the browser.

## Notes and limitations

- **Nothing is stored.** A report is always the current truth. If you want to freeze an edition, use
  the **archive** in `racetrack`.
- **Decimals are a display setting**, not a data setting. Times are always kept at millisecond
  precision.
- **Two reports have separate settings on purpose** — SUT decimals and the arrivals refresh interval
  are independent of the general ones.
- Report names carry their Slovak origins (`stu`, `aktualne`, `dobeh`) in the URLs. They were kept
  so existing links and bookmarks keep working.
