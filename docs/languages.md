# Languages and translations

## What ships

| Plugin | English | Slovak | Italian |
| --- | :---: | :---: | :---: |
| raceevent | ✓ | ✓ | ✓ |
| racetrack | ✓ | ✓ | ✓ |
| racers | ✓ | ✓ | ✓ |
| racetiming | ✓ | ✓ | ✓ |
| racereports | ✓ | ✓ | ✓ |
| racereg | ✓ | ✓ | ✓ |
| racerfid | ✓ | ✓ | ✓ |
| terminovka | ✓ | ✓ | — |

The Italian translation was contributed by [@kreossino](https://github.com/kreossino).

## How the fallback works

**English is the complete, canonical set.** Other languages override it key by key. If a Slovak or
Italian file is missing a string, the **English value is shown** — not an error, not a blank.

This means a partial translation is perfectly usable. You do not have to translate everything before
you translate anything.

## Where the files are

```
<plugin>/languages/
├── English/
│   ├── English_admin.php     admin screens
│   ├── English_front.php     public pages
│   └── English_global.php    the plugin name and shared labels
├── Slovak/
│   └── …
└── Italian/
    └── …
```

Each file returns an array of constants:

```php
<?php
if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_ADMIN_RACE_CAPACITY' => "Capacity",
    'LAN_ADMIN_RACE_CAPACITY_HELP' => "Maximum number of racers on the start list.",
);
```

## Adding a language

1. Copy the `English/` folder of a plugin and rename it to your language, e.g. `German/`.
2. Rename the files inside to match: `German_admin.php`, `German_front.php`, `German_global.php`.
3. Translate the **values**. Leave the **keys** exactly as they are.
4. Delete any key you have not translated — it will fall back to English. Do not leave English values
   in place; that hides what still needs doing.

Then send a pull request. A partial translation is welcome.

## Finding the string you want

Every field table in this documentation carries the **LAN key** of the field. If a label reads wrong
in your language, look it up in the plugin's page here, then search for that key in the language file.

## Two known gaps

- **Some labels are hardcoded** in the code rather than in a language file, so they cannot be
  translated at all. A few of them are in Slovak. Mainly on the Checkpoints, Archive and Categories
  admin screens. They are noted on the relevant plugin pages.
- **`terminovka`'s language files use the old `define()` format** instead of returning an array. That
  means it does **not** get the per-key English fallback: a missing key stays undefined rather than
  falling back. It is also the reason it has no Italian translation yet.
