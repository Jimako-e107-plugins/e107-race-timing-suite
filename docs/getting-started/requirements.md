# Requirements

## What you need

| | |
| --- | --- |
| **e107** | 2.4.x. Most plugins declare compatibility 2.4; three declare 2.3. |
| **PHP** | 7.x or 8.x. The code avoids PHP 8-only syntax. |
| **MySQL / MariaDB** | Tables use InnoDB. |
| **A theme** | The suite was developed against the **Artemis** theme. It is not theme-specific, but full support for other themes was never a goal — expect to touch some CSS. |

## e107 Lite or upstream e107?

The suite was **developed and tested on [e107 Lite 2.4.x](https://github.com/Jimmi08/e107-2.4.x-Lite)**,
a lighter fork of e107. It should also work on **upstream e107** — where the code differs, this is
noted in the code itself.

**Two things look different on e107 Lite**, so do not be surprised if your screen does not match a
screenshot:

### The admin dashboard

e107 Lite uses its **own backend theme**. The admin area is laid out differently from upstream e107.
Nothing about the suite depends on this — the same screens are there, they just look different.

### The order of plugins in the admin menu

Each plugin of the suite declares an `order` in its `plugin.xml`, so that the admin menu lists them
in a sensible sequence:

| Order | Plugin |
| --- | --- |
| 10 | raceevent |
| 15 | racetrack |
| 20 | racers |
| 30 | racerfid |
| 35 | racetiming |
| 40 | racereports |
| 50 | terminovka |

**e107 Lite honours that order. Upstream e107 does not** — it sorts plugins alphabetically, so on
upstream you will find them scattered through the menu. Everything still works; only the order
differs.

## Languages

The suite ships with **English**, **Slovak** and **Italian**. English is the canonical set; the
other languages fall back to it for any string they do not translate.

`terminovka` has English and Slovak only.

See [Languages and translations](../reference/languages.md).

## What you do NOT need

- **No external service.** The suite runs entirely on your own site.
- **No RFID system.** Times can be entered by hand. RFID is optional.
- **No account for anyone.** Competitors do not register on your website; marshals do not log in.
