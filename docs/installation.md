# Installation and install order

## Order matters

The plugins depend on each other. Install them in this order — e107 will refuse to install a plugin
whose dependencies are missing.

```
1.  raceevent      the base — must be first
2.  racetrack      needs raceevent
3.  racers         needs racetrack
4.  racetiming     needs racers
5.  racereports    last — it needs everything above
```

Then, optionally and in any order:

```
    racereg        online registration and payments   (work in progress)
    racerfid       RFID chip import                   (independent)
    terminovka     export to terminovka.sk            (independent)
```

**Minimal live-timing setup:** the five core plugins. That gives you tracks, competitors, timing and
results — everything you need to run a race.

## Installing

Copy the plugin folders from `eplugins/` into your site's `e107_plugins/` directory, then install
them from **Admin → Plugin Manager** in the order above.

## First steps after installing

In this order:

| Step | Where | Why |
| --- | --- | --- |
| 1. Configure the event | [raceevent](../plugins/raceevent.md) → Event configuration | Nothing else makes sense without a name and a date. |
| 2. Create your tracks | [racetrack](../plugins/racetrack.md) → Track list | Everything else refers to a track. |
| 3. Create the checkpoints | [racetrack](../plugins/racetrack.md) → Checkpoints | **Code the start `start` and the finish `finish`.** |
| 4. Create the categories | [racers](../plugins/racers.md) → Categories | Before you add anyone. |
| 5. Add the competitors | [racers](../plugins/racers.md) → List | By hand, or through registration. |

See [Preparing an event](../workflows/preparing-an-event.md) for the full sequence.

## What to check before you trust it

- **The `start` and `finish` checkpoint codes.** Everything about elapsed time depends on them. A
  checkpoint coded anything else is just an intermediate point.
- **The keypad link for each checkpoint** — open one and check it loads.
- **If you use RFID:** run the connection test in [racerfid](../plugins/racerfid.md) days before the
  race, not on race morning.
- **If you use terminovka.sk:** run the configuration test in
  [terminovka](../plugins/terminovka.md), and set the track ID on each track.

## Uninstalling

Uninstalling a plugin through the Plugin Manager removes its tables. Because the plugins share data,
uninstall in **reverse** dependency order — `racereports` first, `raceevent` last.

To clear a season's data **without** uninstalling, use
[raceevent → Maintenance](../plugins/raceevent.md#maintenance) instead.
