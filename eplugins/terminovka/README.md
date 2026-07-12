# Terminovka.sk export

Optional e107 plugin that exports race results to the external
[terminovka.sk](https://www.terminovka.sk) API.

## What it does

The plugin reads finished, **unsent** rows from the `race_result` table and
posts each one to the terminovka.sk API. After every send it records the
outcome (success flag + the API's response/status) back onto the row.

It does **not** generate result data of its own. Result rows are produced and
maintained by the **timetracker** plugin (the online standings and the
timetracker admin). Terminovka only reads those rows and sends them.

## Requirements

- The **timetracker** plugin must be installed and enabled. timetracker owns
  the `race_result` table and is its only writer; terminovka declares a
  dependency on it and cannot be installed without it.

## Architecture

- timetracker **owns and writes** `race_result` (via the
  `terminovka_saveresult` event handler). New rows are written with
  `race_result_sent = 0`.
- terminovka **reads** unsent rows, sends them to terminovka.sk, and logs the
  send outcome by triggering `terminovka_saveresult` again (timetracker
  performs the actual write).
- Sending is **pull-based**: there is no cron. The self-refreshing batch page
  drives the sends (see below).

## Preferences

Configure these under **Admin → Plugins → Terminovka.sk**:

| Preference          | Description                                                        |
|---------------------|--------------------------------------------------------------------|
| Enable export       | Master on/off switch for the export.                               |
| Export token        | API token sent as `Authorization: Token <token>`.                  |
| Export URL          | Target endpoint, e.g. `https://www.terminovka.sk/api/track-participant/final-time`. |
| Refresh interval    | How often (seconds) the batch export page refreshes and re-tries unsent results. `0` disables refresh. This is terminovka's own interval - it is no longer shared with the racerfid plugin. |

## Manual batch export page

`terminovka.php` (admin-only, requires the `P` permission) is a self-refreshing
page that finds every unsent `race_result` row and sends it. Open it during an
event and leave it running; it refreshes itself every *Refresh interval*
seconds. This covers racers whose results were entered manually (DNF/DSQ, late
fixes) and never triggered a natural finish-time send.

The **Export logs** admin view is a **read-only** list of `race_result` rows
(start number, time, sent flag, log, sent-at) for inspection only. To edit a
result, use timetracker.

## Diagnostic test tool

`terminovka_test.php?n=test` checks the configuration (export activated, API
key set). `terminovka_test.php?n=<racer_extid>` verifies and sends a single
racer; add `&force=1` to re-send a result already marked as sent.
