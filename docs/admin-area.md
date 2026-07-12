# The admin area

Once the plugins are installed you will find them in the e107 admin menu. Each plugin has its own
entry, and each entry has its own menu of screens.

## Where things live

The hardest thing about the suite at first is knowing **which plugin owns which screen**. The
mapping is not always where you would guess:

| I want to… | Go to |
| --- | --- |
| Set the event name, date, place | [raceevent](../plugins/raceevent.md) → Event configuration |
| Set the registration window and payment details | [raceevent](../plugins/raceevent.md) → Event configuration → Registration tab |
| Clear last season's data | [raceevent](../plugins/raceevent.md) → Maintenance |
| Add a track | [racetrack](../plugins/racetrack.md) → Track list |
| Add a checkpoint | [racetrack](../plugins/racetrack.md) → Checkpoints |
| Set the entry fee | [racetrack](../plugins/racetrack.md) → Price tiers |
| **Archive a finished edition** | [racetrack](../plugins/racetrack.md) → Archive |
| Add a category | [racers](../plugins/racers.md) → Categories |
| Add a competitor | [racers](../plugins/racers.md) → List |
| Print the start lists | [racers](../plugins/racers.md) → Start lists |
| **Fix a wrong time** | [racetiming](../plugins/racetiming.md) → Time Entries |
| Generate the start for a mass start | [racetiming](../plugins/racetiming.md) → Generate Start |
| **Open the results** | [racereports](../plugins/racereports.md) → any report screen |
| Approve a sign-up | [racereg](../plugins/racereg.md) → Registrations |

Two that surprise people:

- **The archive is in `racetrack`**, not in `raceevent` — because a snapshot belongs to a track.
  You reach it from the raceevent menu too, because the whole event gets archived.
- **Results are never edited in `racereports`.** Reports are calculated from the raw times. To
  change a result, fix the time in `racetiming`.

## Cross-plugin shortcuts

Each plugin's admin menu ends with links (`» Tracks`, `» Racers`, `» Reports`, …) to the other
plugins of the suite. They appear only for plugins you actually have installed. Use them instead of
going back to the main menu each time.

## Report screens are link directories

The screens under `racereports` do not show results. They list **links** to the public report pages —
one per track, per category, per checkpoint. Click through to see the actual report.

This is deliberate: the reports are public pages, meant to be opened on a big screen or shared.

## If the menu looks different from the screenshots

You are probably on **upstream e107** rather than e107 Lite. See
[Requirements](requirements.md#e107-lite-or-upstream-e107) — the admin theme and the plugin ordering
differ. The screens themselves are the same.
