# Starting a new season

The suite is built for **one event per website**. A new edition of the same race reuses the same
site — you clear the data and start again.

> **Back up your database first.** Maintenance deletes data and there is no undo.

## 1. Make sure last year is safe

- Every finished track is **archived** — [racetrack → Archive](../plugins/racetrack.md#archive)
- Every archive is **unlinked** (Track = *Unlinked archive*)

If an archive is still linked to a track, the Maintenance screen will warn you. Do not ignore it:
a linked archive can still be changed by what happens next.

## 2. Clear the data

[raceevent → Maintenance](../plugins/raceevent.md#maintenance). Tick what you want to clear, press
**Execute**.

Clear in this order — the screen enforces it anyway:

```
checkpoint times  →  results  →  racers  →  categories  →  checkpoints  →  tracks
```

**Tracks are locked until everything else is empty.** That is deliberate: a track cannot be removed
while times, competitors or results still point at it.

## 3. Set up the new edition

- Update the event name and date — [raceevent → Event configuration](../plugins/raceevent.md#event-configuration)
- Recreate or reuse the tracks and checkpoints
- **Use a new archive SEF URL with the year in it** — otherwise this year's archive collides with
  last year's
- Open the registration window again, if you use it

## 4. Do not forget

- **New checkpoint passwords** (if you did not regenerate them after the last race)
- New price tiers — the old dates are in the past, so the last tier would apply to everyone
