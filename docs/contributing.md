# Contributing

The suite is open-source and lives at
[github.com/Jimako-e107-plugins/e107-race-timing-suite](https://github.com/Jimako-e107-plugins/e107-race-timing-suite).

## Reporting a problem

Open an issue on GitHub. Useful things to include:

- which plugin, and which admin screen
- e107 version, and whether you run **e107 Lite** or upstream e107
- what you expected and what happened

## Translations

The suite ships with **English**, **Slovak** and **Italian**. Adding a language is straightforward —
see [Languages and translations](languages.md). Translations are very welcome; the Italian set was
contributed by [@kreossino](https://github.com/kreossino).

`terminovka` is not yet translated into Italian, and its language files still use the old `define()`
format rather than the array format the rest of the suite uses.

## Code

- All code, comments, commits, issues and pull requests are in **English**.
- The suite uses **native e107** — the `db` class, `e_admin_ui`, `e107::url()`, `$tp->toDB()` /
  `toHTML()`, `e_token` for CSRF. Please do the same rather than rolling your own.
- Language files return an **array**, never `define()` — that is what gives partial translations a
  per-key fallback to English.
- Start numbers are **strings**, never integers. Leading zeros are significant.
- Measured times are **millisecond datetime strings**, never Unix timestamps.

## Documentation

These pages live in `docs/` in the same repository and are synced to GitBook. A correction to a page
is a pull request like any other.

Each plugin page follows the same shape: what it does, what it requires, its admin screens one by
one, its front-end pages, and an honest list of limitations. Field tables carry the **LAN key**
alongside each field, so a translator can find the string and a reviewer can check the documentation
still matches the code.
