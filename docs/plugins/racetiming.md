# racetiming — timing engine

## What it does

`racetiming` records **when a competitor passed a checkpoint** — and that is all it stores. Every
elapsed time, split and gap you ever see in a report is **calculated** from these raw passings; none
of them is saved.

It provides three things:

1. a **mobile entry screen** for marshals at a checkpoint (the keypad — the tool actually used on
   race day),
2. an **admin screen** for correcting times afterwards,
3. **bulk start generation** — writing the same start time for a whole track at once.

## Requires / required by

| | |
| --- | --- |
| **Requires** | `raceevent`, `racetrack`, `racers` |
| **Required by** | `racereports` |
| **Owns** | `race_time` (the raw passings) |

## Installation notes

Install after `racetrack` — a passing has no meaning without a checkpoint to belong to.

Before you can record anything, the checkpoints must exist in `racetrack`, **with the start coded
`start` and the finish coded `finish`**.

## How a time is stored

A passing is a row of exactly four meaningful values:

| | |
| --- | --- |
| **Bib** | The start number, as a **string** — leading zeros preserved. |
| **Checkpoint** | Which point on the track. |
| **Time** | The moment of passing, to the **millisecond**. |
| **Status** | Empty, or `DNF` / `DSQ` / `DNS`. |

The time is stored as a **datetime string with milliseconds** (`2026-07-12 10:03:11.482`), not as a
number of seconds. That precision is what the split calculations depend on — it must not be rounded
away.

A competitor can have **one row per checkpoint**. Recording the same bib at the same point twice is
prevented by the database.

## Admin area

Four items: **Time Entries**, **Add Time Entry**, **Start time setting**, **Generate Start**.

---

### Time Entries / Add Time Entry

The raw passings — one row per competitor per checkpoint. This is where you go to **fix** a time:
a marshal mistyped a bib, a chip did not read, someone was recorded at the wrong point.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Control Point / Checkpoint | `LAN_ADMIN_RACETIMING_021` | Select control point / checkpoint | The checkpoints come from `racetrack`. |
| Bib / Start Number | `LAN_ADMIN_RACETIMING_023` | Enter bib/start number (including leading zeros) | A **string**. `0042` is not `42`. |
| Measured Time | `LAN_ADMIN_RACETIMING_025` | Measured time at control point | Format `YYYY-MM-DD HH:MM:SS.mmm`. Keep the milliseconds. **Add Time Entry** pre-fills the current server time **without** milliseconds. |
| Status / Finish Status | `LAN_ADMIN_RACETIMING_027` | DNF = Did Not Finish, DSQ = Disqualified, DNS = Did Not Start | Leave empty for a normal passing. |
| Created / Updated | `LAN_ADMIN_RACETIMING_028` / `..._029` | — | Set automatically. |

#### The three statuses

| Status | Meaning |
| --- | --- |
| *(empty)* | A normal passing. |
| `DNF` | Did Not Finish — abandoned the race. |
| `DSQ` | Disqualified. |
| `DNS` | Did Not Start. |

Once a competitor has **any** row with a status set, they are treated as finished-early: further
passings are refused. Setting a status does **not** overwrite the time already recorded.

If `terminovka` is installed, the admin screen keeps its export table in step:

| Action | Effect on the `terminovka` export |
| --- | --- |
| Save an entry with a status (`DNF` / `DSQ` / `DNS`) | The status is written as the competitor's result, marked *not sent* — it goes out with the next export. |
| Save an entry with an **empty** status | The competitor's result row is **removed** from the export table. |
| Delete an entry | The competitor's result row is **removed** from the export table. |

This applies to editing and deleting; adding a new entry here does not touch the export.

---

### Start time setting

A single preference: the **default start time** used when generating the start (`LAN_ADMIN_RACETIMING_042` — *Used as the default value when generating the start*).

---

### Generate Start

Mass-writes a start passing for every competitor on the selected track(s), all with the same time.

Use it when:

- the track has a **mass start** — everyone leaves together, so everyone shares one start time;
- there is **no RFID reader at the start** — otherwise a marshal would have to key in 300 bibs by
  hand.

| Element | LAN key | Notes |
| --- | --- | --- |
| Select Races and Time | `LAN_ADMIN_RACETIMING_044` | Pick the track(s). Submitting with none selected warns *No track selected* (`LAN_ADMIN_RACETIMING_053`). |
| Measured Time | `LAN_ADMIN_RACETIMING_045` | The start time to write, `YYYY-MM-DD HH:MM:SS`. Pre-filled from **Start time setting** (the current time if that has never been set). Written to every start passing exactly as entered. |
| Set Current Time | `LAN_ADMIN_RACETIMING_046` | Fills in the server time **at which the page was loaded**, not the moment you press the button. Reload the page shortly before the gun if you use it. |
| Generate Start Times | `LAN_ADMIN_RACETIMING_047` | Runs the generation. |

Competitors who already have a start time are skipped (*This racer already has generated start*,
`LAN_ADMIN_RACETIMING_049`), so running it twice is safe. Competitors marked as not starting are
reported as such (`LAN_ADMIN_RACETIMING_051`).

## Front-end

### The checkpoint keypad — the tool for race day

| | |
| --- | --- |
| **URL** | `/kontrola/{checkpoint code}/{checkpoint password}/` |
| **File** | `racetiming/vstup.php` |
| **Route key** | `kontrola` |

This is the screen a marshal opens **on their phone** at a checkpoint. It is a large numeric keypad:
type the bib, confirm, and the passing is recorded with the current time.

#### Why there is no login — and what you must do instead

**The checkpoint password in the URL is the access control, and this is deliberate.** Marshals are
volunteers standing in a field. They should not have to create an account, remember a password, or
log in with cold hands on a phone. So the keypad is a plain public link, protected only by the
checkpoint's password, which is part of the URL.

The organizer sends each marshal the link for **their own checkpoint** — nothing else. The link is
usable by whoever holds it, for as long as the password stays the same.

> **Regenerate the checkpoint passwords once the race is over.** In `racetrack` → Checkpoints, the
> Password field has a **generate** button — one click per checkpoint. This is the organizer's
> responsibility: until you do it, every link handed out on race day still works.

The screen refuses a passing when:

| Situation | Message | LAN key |
| --- | --- | --- |
| The bib was already recorded at this checkpoint | Racer no. X is already recorded at Y | `LAN_FRONT_RACETIMING_ALREADY_RECORDED` |
| The competitor already has a DNF/DSQ/DNS | Racer X has already ended early | `LAN_FRONT_RACETIMING_ALREADY_ENDED` |

Marshals can also mark a competitor `DNF` or `DSQ` directly from the keypad.

The keypad stores the bib padded to four digits (`42` is saved as `0042`).

The keypad messages above and its header (*poradie* / *predošlé číslo* — order / previous number)
are currently **always in Slovak**. The LAN keys exist in all three languages, but the screen does
not use them yet.

> **This screen is optimized for speed under pressure**, on a phone, outdoors, possibly with cold
> hands. That is why it looks the way it does. Do not judge it as a normal web form.

### Shortcode

| Shortcode | Shows |
| --- | --- |
| `{TIMETRACKER_VSTUP}` | A list of links to the keypad for every checkpoint. |

There is one link per checkpoint **and track** (a checkpoint shared by two tracks appears twice),
labelled *Track - Checkpoint*, in checkpoint order. Start and finish are included. Each link opens
in a new tab.

Put this shortcode on a **page**, not in your theme. It exists purely for the organizer's
convenience: one place to copy the keypad links from before sending them out to the marshals.

> **That page must not be public.** It lists every checkpoint link — that is, every password. Restrict
> it to a user class or protect it with a password. This is the organizer's responsibility.

The shortcode name is a leftover from the plugin's origin (`timetracker`) and was kept deliberately
so existing pages do not break.

## Notes and limitations

- **Times must keep their milliseconds.** A time entered or edited without the `.mmm` part loses
  precision that the split calculation relies on.
- **Other time formats are ignored.** The reports read `YYYY-MM-DD HH:MM:SS.mmm`,
  `YYYY-MM-DD HH:MM:SS` and `HH:MM:SS.mmm` (today's date is then assumed). Anything else — a comma
  before the milliseconds, missing seconds — is treated as if there were no passing.
- **A passing earlier than the start gives a garbled time.** If a time is before the competitor's
  start (typically a wrong date), the reports show a malformed negative time. Fix the time.
- **The keypad has no login by design** — marshals should not be logging in during a race. The
  checkpoint password in the URL is the only protection, so the link is only as private as the
  people it was sent to. **Regenerate the passwords after the race** (one click per checkpoint in
  `racetrack`).
- **No duplicate passing at one checkpoint.** To correct a wrong entry you edit or delete the
  existing row, not add a second one.
