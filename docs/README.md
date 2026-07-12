# Race Timing Suite for e107

A set of e107 plugins for live race timing and complete event management: registration, start
lists, checkpoint timing, live results, an archive of past editions, and optional RFID import
and calendar export.

The suite runs **inside your event's website** — it is not a separate application. Same design,
same admin area, one place.

## Two things to know before you start

**It is modular.** You install only the plugins your event needs. A few form the required core;
the rest are optional.

**It is built for the single-event model.** One website = one event. The event configuration is
stored as plugin preferences, not as database rows. If you run several events, you run several
websites.

## The plugins at a glance

| Plugin | What it does | Requires | Role |
| --- | --- | --- | --- |
| [**raceevent**](plugins/raceevent.md) | Base event configuration and shared model | — | Required (install first) |
| [**racetrack**](plugins/racetrack.md) | Tracks, checkpoints, entry prices, archive | raceevent | Required |
| [**racers**](plugins/racers.md) | Competitors, categories, start lists | raceevent, racetrack | Core |
| [**racetiming**](plugins/racetiming.md) | Checkpoint timing engine | raceevent, racetrack, racers | Core |
| [**racereports**](plugins/racereports.md) | Results, rankings, splits, live board | raceevent, racetrack, racers, racetiming | Core |
| [**racereg**](plugins/racereg.md) | Online registration and payments | raceevent, racetrack | Optional — **work in progress** |
| [**racerfid**](plugins/racerfid.md) | RFID chip import | — (independent) | Optional |
| [**terminovka**](plugins/terminovka.md) | Export to terminovka.sk | — (independent) | Optional |

## Where to go next

| I want to… | Go to |
| --- | --- |
| Understand what each plugin does | [Plugins at a glance](getting-started/plugins-at-a-glance.md) |
| Check what I need before installing | [Requirements](getting-started/requirements.md) |
| Install the suite | [Installation and install order](getting-started/installation.md) |
| Find my way around the admin | [The admin area](getting-started/admin-area.md) |
| Set up my first event | [Preparing an event](workflows/preparing-an-event.md) |

## Install order

1. **raceevent** — the base, must be first
2. **racetrack** — needs raceevent
3. **racers** — needs racetrack
4. **racetiming** — needs racers
5. **racereports** — last; it needs everything above
6. **racereg**, **racerfid**, **terminovka** — optional, any time

**Minimal live-timing setup:** raceevent + racetrack + racers + racetiming + racereports.

Add **racereg** for registration and payments, **racerfid** for chip timing, **terminovka** for
export to the Slovak race calendar.

## Source code

The suite is open-source:
[github.com/Jimako-e107-plugins/e107-race-timing-suite](https://github.com/Jimako-e107-plugins/e107-race-timing-suite)
