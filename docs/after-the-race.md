# After the race

## 1. Check the results

- Open the finish results and read them — [racereports](../plugins/racereports.md)
- Anything odd? Fix the **time** in [racetiming → Time Entries](../plugins/racetiming.md#time-entries--add-time-entry), never the result
- Use [Racer progression](../plugins/racereports.md) when someone queries their time — it shows their whole race, checkpoint by checkpoint

## 2. Publish

The report pages are public. Link them from your site menu, or use the site links that
[racereports](../plugins/racereports.md#front-end) provides — one per track and category.

## 3. Send the results away

If you use terminovka.sk: open the batch export and let it clear any unsent rows.
Check the **Sent** column in the export log — filter it to find what failed and read the log entry.
[terminovka](../plugins/terminovka.md#export-logs)

## 4. Archive the edition

This is the step people forget.

- [racetrack → Track list](../plugins/racetrack.md#archive) → the **Archive** button on each track
- The snapshot freezes both the data and the rendered results — it will keep showing what it showed
  today, even after you clear the season
- **Then unlink it** (set Track = *Unlinked archive*). An unlinked archive can no longer be
  regenerated or disturbed by next season's data. **This is what makes it permanent.**

> Archiving needs `racereports` installed — it is what produces the content.

## 5. Close the door

**Regenerate the checkpoint passwords.** Every keypad link you handed out this morning still works
until you do. [racetrack → Checkpoints](../plugins/racetrack.md#checkpoints) — the Password field has
a generate button, one click each.
