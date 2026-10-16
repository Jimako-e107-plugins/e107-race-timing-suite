# terminovka — export to terminovka.sk

## What it does

`terminovka` sends finish times to **[terminovka.sk](https://www.terminovka.sk)**, the Slovak race
calendar, so results appear there without anyone retyping them.

This is a **Slovak-specific plugin**. If your event is not listed on terminovka.sk, you do not need
it. It is published mainly as a working example of how an API export can be wired into the suite.

## Requires / required by

| | |
| --- | --- |
| **Requires** | Nothing — it is an independent plugin |
| **Required by** | Nothing |
| **Owns** | `terminovka_track` (track ID mapping), `race_result` (the export queue) |

## How it works

When a competitor's finish time is recorded, a row is written into the **export queue**
(`race_result`) and marked *unsent*. The plugin then posts each unsent row to the terminovka.sk API
and writes the outcome back onto the row — sent or not, and what the API said.

```
finish time recorded  ──►  export queue (unsent)  ──►  POST to terminovka.sk  ──►  row marked sent
                                                                              └─►  or: error logged
```

A row is marked **sent** only when terminovka.sk answers with a JSON reply whose `success` field is
true. Anything else — an error reply, a network error, a missing setting — leaves the row unsent, and
the next batch run tries it again.

It is **pull-based**: there is no cron. The export runs when the batch page is open, refreshing on
its own interval. Leave the page open during the race and it keeps sending.

### What is sent

Each row is posted as JSON, with the export token in an `Authorization: Token <token>` header:

| Sent as | Taken from |
| --- | --- |
| `id` | the competitor's **Ext. ID** in `racers` |
| `externalTrackId` | the track's terminovka.sk track ID ([see below](#track-id--on-the-track-form)), 0 if none |
| `startNumber` | the start number |
| `resultTime` | the queued time, first 9 characters |
| `name`, `surname` | the competitor's first name and surname |
| `category` | the competitor's category name |

Only the API's reply is written to the log — never the posted data, so names do not end up in the
export log.

## Installation notes

You need three things from terminovka.sk before this works:

1. an **API key / export token**,
2. the **export URL** (e.g. `https://www.terminovka.sk/api/track-participant/final-time`),
3. the **track ID** your race has on terminovka.sk.

## Admin area

---

### Main configuration

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Enable export | `LAN_TERMINOVKA_PREF_ACTIVE` | — | Master switch. Nothing is sent while this is off. |
| Export token | `LAN_TERMINOVKA_PREF_TOKEN` | — | From terminovka.sk. |
| Export URL | `LAN_TERMINOVKA_PREF_URL_HELP` | e.g. https://www.terminovka.sk/api/track-participant/final-time | The API endpoint. |
| Refresh interval (seconds) | `LAN_TERMINOVKA_PREF_INTERVAL_HELP` | How often the manual batch export page refreshes and re-tries unsent results. Set to 0 to disable refresh. | **At 0 the batch page sends nothing at all** — it only shows *Refresh interval is not set*. |

Three helpers on the same screen:

| | LAN key | What it does |
| --- | --- | --- |
| **Configuration test** | `LAN_TERMINOVKA_HELP_TEST_TEXT` | Checks whether the export is activated and the API key is set. Run it before race day. It does not check the export URL and does not contact the API. |
| **Manual batch export** | `LAN_TERMINOVKA_HELP_BATCH_TEXT` | Re-sends existing unsent results. Used for competitors who did not cross the finish line naturally. |
| **Refresh interval note** | `LAN_TERMINOVKA_HELP_INTERVAL_TEXT` | Reminds you that at 0 the page will not refresh. |

Errors you may see:

| Message | LAN key |
| --- | --- |
| Export is not activated | `LAN_TERMINOVKA_ERR_NOT_ACTIVE` |
| Token is not set | `LAN_TERMINOVKA_ERR_NO_TOKEN` |
| Target URL is not set | `LAN_TERMINOVKA_ERR_NO_URL` |
| API key is not set | `LAN_TERMINOVKA_ERR_NO_APIKEY` |
| Refresh interval is not set | `LAN_TERMINOVKA_ERR_NO_INTERVAL` |
| No racer data | `LAN_TERMINOVKA_ERR_NO_RACER` |
| No track data | `LAN_TERMINOVKA_ERR_NO_RACE` |

When a row is sent, the first three, *No racer data* (no competitor with that start number) and
*No track data* (the competitor's track is missing) are written into that row's **Log** instead of
an API reply, as is a network error (`cURL error (…)`). The row stays unsent.

#### Manual batch export page

Opened by the **Run manual export** button (`LAN_TERMINOVKA_HELP_BATCH_BTN`) in a new tab. Admins
only. It first checks the refresh interval, *Enable export* and the token; if one is missing it
shows the matching error and sends nothing. Otherwise it lists every start number it sends
(*Sending start number*), ends with *Export finished* and the time, and reloads itself after the
refresh interval.

#### Configuration test and single-racer send

The **Test** button (`LAN_TERMINOVKA_HELP_TEST_BTN`) opens `terminovka_test.php?n=test`. The same
page can also send one competitor right now — type the address yourself, there is no button:

| Address | What it does |
| --- | --- |
| `terminovka_test.php?n=test` | Configuration test only. |
| `terminovka_test.php?n=<Ext. ID>` | Finds the competitor by their **Ext. ID** in `racers`, then sends their row from the export queue and shows the API's reply. |
| `…?n=<Ext. ID>&force=1` | The same, but also re-sends a row already marked sent. Without it, a sent row only shows its log. |

It stops with an error if no competitor or more than one has that Ext. ID, or if the competitor has
no row in the export queue yet.

---

### Export logs

A **read-only** view of the export queue — one row per competitor, newest first, 200 per page.

| Field | LAN key | Notes |
| --- | --- | --- |
| Start number | `LAN_TERMINOVKA_FIELD_NUMBER` | The bib. Filterable. |
| Time | `LAN_TERMINOVKA_FIELD_TIME` | The finish time being exported. |
| Sent | `LAN_TERMINOVKA_FIELD_SENT` | Whether terminovka.sk accepted it. Filterable — this is how you find what failed. |
| Log | `LAN_TERMINOVKA_FIELD_LOG` | What the API replied. Read this when a row will not send. |
| Created / Updated / Sent at | `LAN_TERMINOVKA_FIELD_CREATED` / `..._UPDATED` / `..._TIMESENT` | |

> **Do not fix results here.** This is an export log. Correct the underlying time in `racetiming`;
> the queue follows.

---

### Track ID — on the track form

`terminovka` adds a **Terminovka** tab to the track edit form in `racetrack`:

| Field | LAN key | Admin help text |
| --- | --- | --- |
| terminovka.sk track ID | `LAN_TERMINOVKA_TRACK_EXTID_HELP` | The track's ID on terminovka.sk, used as the external track ID during export. Leave 0 if the track is not published there. |

Leave it at 0 for tracks that are not on terminovka.sk. Their results are **still sent**, with
track ID 0 — the export does not skip them.

## Notes and limitations

- **No cron.** The export only runs while the batch page is open. Close the page and nothing sends
  until you open it again.
- **Refresh interval 0 = nothing happens.** The single most common cause of "the export is not
  working".
- **Slovak-specific.** The API, the field names and the whole flow exist for one external service.
- **Not translated into Italian.** Its language files were added after the Italian translation of
  the rest of the suite.
- **The plugin's own README, `plugin.xml` description and several help texts still refer to a
  `timetracker` plugin** that is no longer part of the suite, and claim `timetracker` owns
  `race_result`. It does not — `terminovka` does. Ignore those references.
