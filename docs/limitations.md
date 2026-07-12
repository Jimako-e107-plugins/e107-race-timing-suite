# Known limitations

An honest list. Nothing here stops you running a race; all of it is worth knowing first.

## The model

- **One website = one event.** There is no event switcher and no list of events. Two events means
  two websites. A new edition of the same race reuses the same site — you archive, then clear.
- **One track and one category per competitor.** Someone running two tracks is two records with two
  bibs.

## racereg is not finished

- **No e-mails.** No confirmation to the applicant, no payment reminder, no approval notice.
- **No organizer notification** of a new sign-up.
- **The amount due is typed in by hand** — the price tiers are not applied automatically.
- **No card payments** — bank transfer with a QR code only.

It is published as a reference implementation. For a real event, consider taking sign-ups by other
means and using the on-site registration in `racers`.

## Security

- **The checkpoint keypad has no login.** This is by design — marshals must not be logging in during
  a race. The password in the URL is the access control, so the link is only as private as the people
  you sent it to. **Regenerate the passwords after every race.**
- **The payment page is protected only by its token.** Anyone with the link sees that person's
  payment details.
- **`racereg` holds the most personal data in the suite.** Restrict its admin permission.

## Presentation

- **The archive stores rendered HTML.** Changing your theme later will not restyle an already
  archived edition.
- The suite was developed against the **Artemis** theme. Full support for other themes was never a
  goal — expect to touch some CSS.
- **On upstream e107, the plugins appear in the admin menu in alphabetical order**, not in the
  intended sequence. e107 Lite honours the order. Cosmetic only.

## Rough edges

- **Some admin labels are hardcoded**, a few of them in Slovak, and do not follow the site language.
  Mainly on the Checkpoints, Archive and Categories screens.
- **`terminovka` still refers to a `timetracker` plugin** in its README, its plugin description and
  some help texts. That plugin no longer exists; `terminovka` owns its own data. Ignore those
  references.
- **`terminovka` has no Italian translation** — it was added to the suite after the Italian
  translation was contributed.
- **`terminovka` has no cron.** The export only runs while the batch page is open.

## Things that are deliberate, not bugs

- The Slovak words in the URLs (`pretek`, `kontrola`, `prihlaska`…) — changing them would break
  existing links. They are editable in Admin → URLs.
- The `{TIMETRACKER_VSTUP}` shortcode name — kept so existing pages do not break.
- The Categories admin screen is restricted to the main administrator, not to plugin administrators.
