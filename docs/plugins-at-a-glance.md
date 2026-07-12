# Plugins at a glance

The suite is eight plugins. Five form the core; three are optional.

| Plugin | What it does | Requires | Role |
| --- | --- | --- | --- |
| [**raceevent**](../plugins/raceevent.md) | The event itself: name, date, place, registration window, payment details. Also season maintenance. | — | **Required** — install first |
| [**racetrack**](../plugins/racetrack.md) | The structure: tracks, checkpoints, entry prices — and the archive of finished editions. | raceevent | **Required** |
| [**racers**](../plugins/racers.md) | The people: competitors, categories, start lists, on-site check-in. | raceevent, racetrack | **Core** |
| [**racetiming**](../plugins/racetiming.md) | The timing engine: passings at checkpoints, the marshal's keypad, mass-start generation. | raceevent, racetrack, racers | **Core** |
| [**racereports**](../plugins/racereports.md) | The results: live board, rankings, splits, checkpoint times. **The reason to run the suite.** | all of the above | **Core** |
| [**racereg**](../plugins/racereg.md) | Online sign-up, approval, substitutes, QR payment. | raceevent, racetrack | Optional — **work in progress** |
| [**racerfid**](../plugins/racerfid.md) | Imports passings from an RFID chip system. | — (independent) | Optional |
| [**terminovka**](../plugins/terminovka.md) | Sends results to terminovka.sk, the Slovak race calendar. | — (independent) | Optional |

## How they fit together

```
                    raceevent          the event (as settings, not data)
                        │
                    racetrack          tracks · checkpoints · prices · archive
                        │
                     racers            competitors · categories
                        │
                   racetiming          when did who pass which point
                        │
                  racereports          → results, rankings, live board


    racerfid       feeds times into racetiming        (independent)
    racereg        feeds people into racers           (independent of racers by design)
    terminovka     sends finish times out             (independent)
```

## The one thing to understand

**Only the raw passings are stored.** A time is: *this bib, at this checkpoint, at this moment.*

Every elapsed time, split, gap and ranking you ever see is **calculated on the spot** from those raw
rows. Nothing is saved.

This is why:

- correcting a time in `racetiming` updates **every** report immediately — there is nothing to rebuild;
- you never edit a result — you fix the time behind it;
- to preserve an edition permanently you must **archive** it, which freezes a snapshot.

## What "optional" really means

- Without **racereg**, competitors are entered by hand or imported. Everything else works.
- Without **racerfid**, times are typed on the keypad. Everything else works.
- Without **terminovka**, results simply stay on your site.
- Without **racereports**, you have no results at all — and no archive. It is not optional in
  practice.
