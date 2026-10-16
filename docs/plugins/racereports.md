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
| **Results — SUT** | Results of one track (finishers, then DNF/DSQ), in the format required by the Slovak Ultra Trail series | `/stu/{track id}/` |
| **Start list** | Who started, and when | `/start/{track}/{category}/` |
| **Checkpoint times** | All times at one checkpoint | `/point/{track}/{checkpoint}/` |
| **Racer progression** | One competitor across all checkpoints | `/racer/{bib}/` |
| **Segment (Od–Do)** | Time between two chosen checkpoints | `/custom/{track}/{point 1}/{point 2}/` |

**`komplet`** in place of the category (online, finish, start) shows every category on one page;
in place of the checkpoint (checkpoint times, arrivals board) it shows every checkpoint except start
and finish. Some reports also take a special value in place of the **track**:

| Report | Value | Shows |
| --- | --- | --- |
| Online results | `/online/komplet/komplet/` | Every track, each in a compact table listed **slowest first**. |
| Arrivals board | `/dobeh/komplet/komplet/` | Every track, one board per checkpoint (without start and finish). |
| Results — finish | `/finish/overview/komplet/` | Every track on one page, finishers listed **slowest first** with the rank counting down. Intentional. |
| Start list | `/start/overview/komplet/` | Every track on one page. |

The checkpoint-times report has no all-tracks view (`komplet` as the track gives *No race selected.*).

The online results, arrivals board, full results and the two `overview` pages are shown **without
the site header and footer**, so they fit a projector or a big screen. The other reports keep the
normal site layout.

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

**Results — SUT** — a **Slovak-specific report**. It produces the results of a track in the format
expected by the [Slovak Ultra Trail](https://www.slovakultratrail.sk/) series: the finishers first,
ranked by time, followed by the DNF and DSQ competitors (their time column shows `DNF` / `DSQ`), the
ranking continuing through. If your event is not part of that series, you will not need it — use the
finish report instead.

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

---

### What each report shows

Who counts as **DNS** (did not start): a competitor whose **Nastúpil (started)** box in `racers` is
not ticked. The online, finish and start reports read it that way; SUT and full results leave such
competitors out entirely.

#### Online results

- Every competitor is placed at the **furthest checkpoint** they have reached: first those in the
  finish, then those at the last checkpoint before it, and so on back to the start.
- Within each checkpoint: fastest first (time from the start). The rank continues from one checkpoint
  group to the next; the finish group is numbered from 1.
- Columns: rank, bib, name, nationality, time, checkpoint code.
- DNF, DSQ and DNS competitors (and anyone without a usable time) follow their checkpoint group,
  without a rank and without colour.
- Ranked rows always carry the **category colour** — there is no setting for this report.
- Logged-in admins see a collapsible help note about the automatic refresh at the top
  (`LAN_RACEREPORTS_HELP_REFRESH_TITLE`). Visitors do not.

#### Arrivals board

- Only competitors with a valid passing at the checkpoint (a passing marked DNF/DSQ does not count).
- **Newest arrival on top.** The `#` column counts down, so the first arrival is 1.
- Columns: `#`, bib, name, category, time (from the start). The category cell reads
  *Category — N.*, where N is the competitor's place **within the category** by time at this
  checkpoint (not by arrival order).
- Every row carries the category colour.

#### Checkpoint times

- Competitors who passed the checkpoint, fastest first (time from the start), ranked and coloured
  by category.
- Then DNF and DSQ competitors, without rank and colour.
- **Admins only:** competitors who have not passed yet are listed at the end; a ✅ / ❌ column shows
  whether a passing is stored, and an edit icon opens that passing in `racetiming` in a new tab.

#### Results — finish

- Finishers first, fastest first, ranked. Then the **DNF**, **DSQ** and **DNS** groups, in that order,
  without rank. Competitors who are still on the course (or have no start passing) and are not
  flagged are not listed.
- Columns: rank, bib, name, nationality, *category* (only when **Finish — category column** is on),
  status icon (✅ finished, ❌ DNF/DSQ/DNS), time (or the status text). The page has no header row;
  the export does.
- **Admins only:** an edit icon on each row opens the finish passing in `racetiming` (new tab).
- CSV / XLS export — see [Export](#export).

#### Start list

- Competitors who are ticked **Nastúpil** **and** have a start passing, sorted **alphabetically by
  name** (not by time — mass starts share one start time), numbered, with their **start time of
  day**.
- Then everyone else, without number, time or colour.
- Columns: number, bib, name, gender, nationality, start time.
- CSV / XLS export — see [Export](#export).

#### Results — SUT

- Only competitors ticked **Nastúpil**. Finishers ranked by time, then DNF, then DSQ with the
  marker in the time column, the rank continuing. Competitors with neither a finish nor a DNF/DSQ
  are not listed. With nobody to list: *No finishers yet.* (`LAN_RACEREPORTS_SUT_NO_FINISH`).
- Columns: Ranking, Time, Family Name, First Name, Gender, Birthdate, Nationality, Bibnumber
  (`LAN_RACEREPORTS_COL_*`). A birth date stored as `19.2.1996` is shown as `1996-02-19`.
- Sortable columns (click a header). **CSV** and **XLS** buttons above the table — see [Export](#export).

#### Full results (aktuálne)

- Columns: Pos., Name (with the bib), Cat., Time, one column per checkpoint except start (the
  finish is the last), Rank in category.
- Only competitors ticked **Nastúpil**. Finishers first, then DNF (furthest checkpoint first), then
  DSQ; the position continues through all three. *Rank in category* is empty for DNF/DSQ, and a
  DNF competitor's cells after the checkpoint where they stopped are empty.
- **Time** is shown as `HH:MM:SS`, the checkpoint columns as `HH:MM`. The decimal-places setting
  does not apply here.
- Sortable columns plus a **Search** box.

#### Segment (Od–Do)

- Only competitors with a passing at **both** checkpoints, fastest first.
- Columns: rank, bib, name, segment time. No category colours.

#### Racer progression

- One row per checkpoint of the competitor's track, **start and finish included**, in course order.
- Columns: Checkpoint, Time of day, Split (time from the start), Segment (time from the last
  checkpoint where the competitor was actually recorded — a missed checkpoint does not blank the
  next segment). A missing passing shows `—`.
- A DNF or DSQ competitor gets a note below the table, with the checkpoint where it was recorded.
- An unknown bib gives *Unknown bib number.* (`LAN_RACEREPORTS_NUM_UNKNOWN_BIB`).

#### Export

The **finish**, **start** and **SUT** reports can be downloaded as **CSV** or **XLS** — from the
buttons on the report screens in admin, and (SUT) above the table on the page itself.

- One file per track: all categories, or one category. The all-tracks `overview` pages are not
  exported.
- File names: `finish_{track}[_{category}]_{date}.csv`, `start_…`, `stu_{track}_{date}.xls`.
- The file holds the same cells as the page, plus a header row. The name is plain text (team, local
  text, and the country flag as an emoji when flags are switched on in `racers`).
- CSV is UTF-8 (opens in Excel with diacritics intact). XLS is a simple table that Excel and
  LibreOffice open; in it the bib and the time are kept as text, so `0042` stays `0042`.

## Admin area

Ten items: **Settings**, plus one link-directory screen per report.

---

### Settings

Grouped into four tabs.

#### Tab: Decimals

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Results — sub-second decimal places | `LAN_ADMIN_RACEREPORTS_111` | 0-3. Applies to the result reports (online, checkpoints, finish, start, segment, arrivals). The data is always stored at 3 decimals (ms); this is display only (truncated, not rounded). SUT has its own setting; the Aktuálne (overview) report is not governed by this. | Default **2**. **Display only.** Lowering it does not destroy precision — the milliseconds stay in the database, and sorting always uses the full time. Also applies to racer progression and to the start time in the start list. |
| SUT time — decimal places | `LAN_ADMIN_RACEREPORTS_071` | Decimal places for finish times on the SUT (finishers) report. 0 = whole seconds (HH:MM:SS), as before. | Default **0**. Deliberately separate from the setting above. DNF/DSQ rows show the marker, not a time. |

#### Tab: Coloring

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| SUT — colour categories | `LAN_ADMIN_RACEREPORTS_073` | Off = clean results list with no category background colours. On = per-category row background on the SUT (finishers) report. | Default **off**. Only finisher rows are coloured; DNF/DSQ rows never. |
| Finish — colour categories | `LAN_ADMIN_RACEREPORTS_077` | On (default) = per-category row background on finisher rows of the finish (results) report. Off = clean results list with no category colours. Ended rows (DNF/DSQ/DNS) are never coloured. | Default **on**. |
| Start — colour categories | `LAN_ADMIN_RACEREPORTS_079` | On (default) = per-category row background on starter rows of the start (start list) report. Off = clean list with no category colours. Not-started rows are never coloured. | Default **on**. |

The colours come from the **category colour** set in `racers`. The other reports have no setting:
online results and checkpoint times always colour their ranked rows, the arrivals board colours every
row, and the segment, racer progression and full results reports are never coloured.

#### Tab: Refresh

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Online — auto-refresh interval (s) | `LAN_ADMIN_RACEREPORTS_075` | 0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence. | Default **0**. |
| Arrivals — auto-refresh interval (s) | `LAN_ADMIN_RACEREPORTS_094` | 0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence. Separate from the online interval - the arrivals board is paced on its own. | Default **0**. |

`?refresh=0` switches the refresh **off** for that one window even when an interval is set here.

#### Tab: Other

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Finish — category column | `LAN_ADMIN_RACEREPORTS_096` | Off (default) = no category column. On = show a column with the category name in the finish (results) list and its CSV/XLS export. | Default **off**. The column sits after nationality. |

---

### Report screens

The nine remaining menu items are **link directories**, one per report type. They deliberately show
no results themselves — their job is to save you from assembling URLs by hand. Every link opens in a
new tab.

| Screen | What it lists |
| --- | --- |
| Online results | Per track: *all categories*, then each category (by name). |
| Checkpoint times | Per track: *all checkpoints*, then each checkpoint in course order, without start and finish. |
| Results (SUT) | One row per track, with **CSV** and **XLS** buttons. |
| Results (finish) | An *all tracks on one page* link, then per track: *all categories* and each category — each row with **CSV** and **XLS** buttons. |
| Start list | Same layout as Results (finish). |
| Full results | One link per track. |
| Segment (Od–Do) | Per track a **From** / **To** picker with all its checkpoints (start and finish included) and an **Open segment report** button (`LAN_ADMIN_RACEREPORTS_102`–`104`). |
| Arrivals (dobeh) | Per track: *all checkpoints*, then each checkpoint, without start and finish. |
| Racer progression | Every competitor, ordered by bib, as *bib — name*. |

When there is nothing to link to yet you get: *No races yet* / *No categories yet* / *No checkpoints
yet* (`LAN_ADMIN_RACEREPORTS_010`–`012`).

## Front-end

Beyond the nine report pages, `racereports` provides **site links** so you can put results straight
into your website menu (Site Links → link function):

| Function | Name in the list | Generates |
| --- | --- | --- |
| `racereports::racereports_online` | Online výsledky | One entry per track (all categories), then one per track and category (*Track - Category*). |
| `racereports::racereports_finish` | Výsledková listina | The same, for the finish report. |
| `racereports::racereports_start` | Štartová listina | The same, for the start list. |
| `racereports::racereports_points` | Časy na kontrolách | One entry per track and checkpoint (*Track - Checkpoint*), without start and finish. |

The per-category and per-checkpoint entries open in a new tab. The names in the list are
**hardcoded in Slovak**.

Only the **SUT** and **full results** tables are sortable in the browser (click a column header); the
full results table also has a search box.

## Notes and limitations

- **Nothing is stored.** A report is always the current truth. If you want to freeze an edition, use
  the **archive** in `racetrack`.
- **Decimals are a display setting**, not a data setting. Times are always kept at millisecond
  precision.
- **Two reports have separate settings on purpose** — SUT decimals and the arrivals refresh interval
  are independent of the general ones.
- **`all` is not accepted** in place of a category or checkpoint — use `komplet`.
- Report names carry their Slovak origins (`stu`, `aktualne`, `dobeh`) in the URLs. They were kept
  so existing links and bookmarks keep working.
