# raceevent — event configuration

## What it does

`raceevent` is the **base plugin** of the suite. It holds the event itself: name, date, place,
description, the registration window and the payment details. Because this is a single-event
installation, the event is stored as **plugin preferences**, not as rows in a table — there is no
"list of events" to browse.

Everything else in the suite reads the event from here, so `raceevent` is installed first. It also
provides the **season maintenance** screen (clearing last season's data) and two diagnostic
screens.

## Requires / required by

| | |
| --- | --- |
| **Requires** | Nothing. This is the base. |
| **Required by** | `racetrack`, `racers`, `racereg`, `racetiming`, `racereports` |
| **Owns** | Plugin preferences — no tables of its own |

## Installation notes

Install this plugin **before any other plugin of the suite**. The others declare a dependency on
it and will not install cleanly without it.

After installing, go straight to **Event configuration** and fill in at least the event name —
several pages across the suite display it.

## Admin area

The plugin adds four items to its admin menu.

---

### Event configuration

The default screen. It is a preferences form with two tabs.

#### Tab: Default

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Event name | `LAN_RACEEVENT_NAME` | — | Required. Shown on the welcome menu, the event overview and across the suite. Available as the `{RACEEVENT_EVENT_NAME}` shortcode. |
| Event date | `LAN_RACEEVENT_DATE` | — | Date only, no time. Stored as a Unix timestamp. |
| Town / village | `LAN_RACEEVENT_CITY` | — | Plain text. |
| Venue / base | `LAN_RACEEVENT_LOCATION` | — | Plain text — the actual place competitors report to. |
| Description | `LAN_RACEEVENT_DESCRIPTION` | — | Plain textarea (no rich editor). Rendered as HTML by the `{RACEEVENT_EVENT_DESCRIPTION}` shortcode and by the welcome menu. |
| Organizer | `LAN_RACEEVENT_ORGANIZER` | — | Plain textarea. |
| Charity event | `LAN_RACEEVENT_IS_CHARITY` | — | On/off flag describing the event. |
| Children runs included | `LAN_RACEEVENT_IS_CHILDREN_RUNS` | — | On/off flag describing the event. |
| Participation with a dog allowed | `LAN_RACEEVENT_IS_DOG_ALLOWED` | — | On/off flag describing the event. |

#### Tab: Registration

These fields are only meaningful if you use `racereg`. They are stored in `raceevent` because the
registration window and the payee belong to the **event**, not to the registration form.

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Registration opens | `LAN_RACEEVENT_REG_START_HELP` | Sign-ups are accepted from this moment. Leave empty (0) for no lower bound. If both bounds are set, opening must be before closing. | Date **and** time. `racereg` accepts a sign-up only inside this window. |
| Registration closes | `LAN_RACEEVENT_REG_END_HELP` | Sign-ups are accepted until this moment. Leave empty (0) for no upper bound. If both bounds are set, closing must be after opening. | Date **and** time. |
| Payee IBAN | `LAN_RACEEVENT_PAYEE_IBAN_HELP` | Bank account (IBAN) shown as the payment target on the registration confirmation page. Spaces are removed automatically. Required (together with the beneficiary name) for the payment QR code. | Checked on save (format, length, mod-97 checksum). A suspicious IBAN is **saved with a warning**, not rejected. |
| Beneficiary name | `LAN_RACEEVENT_PAYEE_NAME_HELP` | Account-holder / beneficiary name shown with the payment details. Required whenever an IBAN is set: PAY by square cannot generate the QR code without it. | **Hard requirement.** Saving an IBAN without a name is rejected. |
| Payee SWIFT / BIC | `LAN_RACEEVENT_PAYEE_SWIFT_HELP` | Optional bank identifier (BIC), 8 or 11 characters. Spaces are removed automatically. | Optional. A malformed BIC is saved with a warning. |

#### What the form refuses to save

Two rules are enforced on save. If either is broken, **your change to those fields is discarded**
and the previous values are kept:

| Situation | Message | LAN key |
| --- | --- | --- |
| An IBAN is set but the beneficiary name is empty | A beneficiary name is required when an IBAN is set… | `LAN_RACEEVENT_PAYEE_NAME_REQUIRED` |
| Registration closes before (or exactly when) it opens | Registration cannot close before (or at the same time as) it opens… | `LAN_RACEEVENT_REG_WINDOW_INVALID` |

And three situations that are only **warnings** — the values are saved:

| Situation | LAN key |
| --- | --- |
| The IBAN does not pass the checksum | `LAN_RACEEVENT_IBAN_WARN` |
| The SWIFT / BIC is not 8 or 11 characters | `LAN_RACEEVENT_SWIFT_WARN` |
| A registration window is set but the payee block is incomplete | `LAN_RACEEVENT_PAYEE_INCOMPLETE_WARN` |

---

### Maintenance

> **This screen deletes data. There is no undo.** Use it when you are starting a **new season**,
> after you have archived the results of the previous one.

The screen has three parts.

#### Active tables

For each table it shows the record count and a **Clean** checkbox. Tick what you want to clear,
then press **Execute**.

| Item | Table | LAN key | Notes |
| --- | --- | --- | --- |
| Tracks | `race` | `LAN_TR_TBL_RACES` | **Locked by default.** It only unlocks once every other table above is empty — a track cannot be removed while times, racers or results still point at it. |
| Checkpoints | `race_point` | `LAN_TR_TBL_POINTS` | |
| Categories | `race_category` | `LAN_TR_TBL_CATEGORIES` | |
| Racers / start numbers | `racer` | `LAN_TR_TBL_RACERS` | |
| Results | `race_result` | `LAN_TR_TBL_RESULTS` | |
| Checkpoint times | `race_time` | `LAN_TR_TBL_TIMES` | The raw measured times. Clearing this loses the race. |
| Reader | `race_tracking` | `LAN_TR_TBL_READER` | Belongs to `racerfid`. If that plugin is not installed, the row is simply skipped. |

Before anything is deleted you get a warning if **archive records are still linked to a track**
(`LAN_TR_UDRZBA_ARCHIVE_WARN_TITLE`). Unlink them first — otherwise the archived results of the
previous edition can be affected.

#### Legacy / old tables

Tables left over from older versions. Here you can **Clean** them (empty) or **DROP** them (remove
the table entirely).

| Item | Table | LAN key |
| --- | --- | --- |
| Old racer list | `race_racer` | `LAN_TR_TBL_OLD_RACERS` |

#### Required plugins check

A read-only table listing the plugins of the suite with their status: **Active** / **Disabled** /
**Missing in eplugins**, whether they are mandatory, and how many preferences they have stored.
Useful as a quick health check after an install or an upgrade.

---

### Navigation check

Read-only diagnostic. It lists the navigation links (site links) of your website that call a plugin
function, and flags the broken ones:

| Status | Meaning | LAN key |
| --- | --- | --- |
| OK | The plugin and the function both exist. | `LAN_RACEEVENT_CL_OK` |
| Plugin missing | The link calls a plugin that is no longer installed. | `LAN_RACEEVENT_CL_BROKEN_PLUGIN` |
| Function missing | The plugin exists but the function does not. | `LAN_RACEEVENT_CL_BROKEN_METHOD` |
| Malformed function | The link's function is not written as `plugin::method`. | `LAN_RACEEVENT_CL_MALFORMED` |
| Owner / function mismatch | The link's owner plugin differs from the plugin it calls. | `LAN_RACEEVENT_CL_OWNER_MISMATCH` |

Broken links can be **hidden** (set to the "nobody" userclass) from here. Editing or deleting them
is done in the normal Site Links admin.

This is typically needed after removing a plugin — a leftover menu item pointing at a plugin that
is gone.

---

### Event overview

A directory of links to every page the suite publishes (tracks, checkpoints, categories, start
lists, result lists), with an **alive check** on each one:

| Marker | Meaning | LAN key |
| --- | --- | --- |
| OK | The target page exists. | `LAN_RACEEVENT_OV_ALIVE` |
| missing | The target file is not on disk. | `LAN_RACEEVENT_OV_DEAD` |
| route not registered | The plugin does not publish that route. | `LAN_RACEEVENT_OV_NO_ROUTE` |

Read-only. This is the same content as the public [event overview page](#event-overview-page) —
one shared source, so the admin view and the public page can never disagree.

## Front-end

### Event overview page

| | |
| --- | --- |
| **URL** | `/preteky/` |
| **File** | `raceevent/page_overview.php` |
| **Route key** | `index` |

The public link directory: tracks, checkpoints, categories, start lists and result lists of the
event, all on one page. This is the natural "hub" page to link from your main menu.

### Welcome menu

A menu you can place through the e107 Menu Manager. It shows the event name as a heading and the
event description below it — both taken from Event configuration.

The look is controlled by the `welcome` template in `raceevent/templates/raceevent_template.php`.

### Shortcodes

Usable in any e107 page or template:

| Shortcode | Shows |
| --- | --- |
| `{RACEEVENT_EVENT_NAME}` | The event name. |
| `{RACEEVENT_EVENT_DESCRIPTION}` | The event description, rendered as HTML. |

## Notes and limitations

- **One event per website.** There is deliberately no event table and no event switcher. A second
  event means a second website.
- **The description field is a plain textarea**, not a rich-text editor. Basic HTML works; there is
  no WYSIWYG toolbar.
- **Maintenance is destructive and has no confirmation dialog beyond the checkbox.** Back up the
  database before a season reset.
- The IBAN check is a **format and checksum check only**. It cannot tell you whether the account
  actually exists.
