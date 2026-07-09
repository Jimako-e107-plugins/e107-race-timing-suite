# racereg - developer notes

Registration + payments plugin for the race-timing suite. These notes live here
(not as XML comments in `plugin.xml`) by project convention - e107 does not
process `<!-- -->` comments in the manifest.

## Scope

- **#20 (done)** - plugin scaffold: install/uninstall + admin entry.
- **#22 (this change)** - data model (two tables, 1:N) + full admin CRUD for
  both, so the model is testable in admin. `amount_due` is entered **manually**
  here.

Explicitly **out of scope** (separate issues):

- automatic price resolution from `race` tiered prices + freezing `amount_due`,
  and the front-end sign-up flow / gating / substitute promotion (#3)
- PAY by square QR generation (#4)
- mark-paid convenience action, derived paid-status, approval workflow (#5)
- `racers` export (#6); custom registration fields

So there is **no** automatic pricing, **no** front-end exposure, and **no**
approval workflow yet - the `approval_status` column exists with neutral labels
but no workflow logic.

## Dependencies

`racereg` requires **`raceevent`** (the event base/config) AND **`race`** (the
tracks/categories). Declared in `plugin.xml` with the in-repo Lite syntax
(mirrors `terminovka`; verified against `plugin_class::XmlDependencies`):

```xml
<dependencies>
	<plugin name="raceevent" />
	<plugin name="racetrack" />
</dependencies>
```

The `name` attribute is matched against the installed plugin's **folder name**
(`plugin_path`), so both target plugins must be installed first. `racereg` does
**not** depend on `racers`.

## Schema (issue #22)

Tables live in `racereg_sql.php` (e107 Lite reads `<plugin>_sql.php` on
install/uninstall - **not** `plugin.xml`; that matches `race_sql.php` /
`timetracker_sql.php`). The deliverable text says "plugin.xml - add two tables",
but the Lite mechanism is the `_sql.php` file, so that is what is used.

- `racereg_registration` - one row per registrant (PII).
- `racereg_payment` - 0..N payment rows per registration; **indexed** on
  `registration_id` (`KEY registration_id`).

Relationship `racereg_payment.registration_id -> racereg_registration` is **1:N**
and kept **logical** (indexed), with no engine-level FOREIGN KEY - none of the
reference Lite plugins declare FKs. Dates are `INT(10)` Unix timestamps, money is
`DECIMAL(10,2)`, and `variable_symbol` / `postal_code` are `VARCHAR` (numeric
strings, never INT). `variable_symbol` also carries a `UNIQUE` index.

## Admin structure (mode-per-file)

`admin/admin_menu.php` holds the shared `e_admin_dispatcher` with two CRUD modes;
the dispatcher is kept so future modes (approvals, ...) can be added as new entry
scripts + `$modes`/`$adminMenu` entries with no change to existing modes:

- `main`     -> `admin/admin_config.php`   (registrations CRUD)
- `payments` -> `admin/admin_payments.php` (payments CRUD, 1:N per registration)

Bootstrap follows the project convention: `require_once("../../../class2.php")`,
then `getperms('P')`, then the shared menu. Both are native `e_admin_ui`
controllers (no raw SQL bypassing the framework).

### e_admin_ui mechanisms used

- **track_id dropdown**: read-only cross-plugin read of the `race` (tracks) table
  via `e107::getDb()->retrieve('race', ...)` in `init()`, set as the field
  `optArray` (write + read). Same idiom as `racers` / `race`.
- **variable_symbol**: auto-generated in `beforeCreate()` (unique numeric string
  <= 10 chars, uniqueness checked via the db class + a `UNIQUE` index) and
  returned for merge into the row; rendered `readonly` so it is locked after
  creation.
- **soft-delete**: `beforeDelete()` stamps `deleted_at = time()` via the db class
  and returns `false` to cancel the hard delete (covers single **and** batch
  deletes). The list hides soft-deleted rows with a permanent
  `$this->listQrySql['db_where'] = 'deleted_at IS NULL'`. Batch delete/copy are
  disabled so PII is never hard-deleted or bulk-duplicated.
- **dates**: `type=datestamp, data=int` (stored as INT). `birth_date` is
  date-only (`writeParms type=date`); `registration_date` / `created_at` default
  to now on create.
- **payments link**: `registration_id` is a dropdown (id => "Last First (VS)")
  with `filter=true`, so the payments list can be filtered by registration; no
  unique constraint, so multiple payments per registration are allowed.
- **audit**: soft-delete is logged via `e107::getLog()`.

## Security baseline (carried forward)

`racereg` will later hold the **heaviest PII** in the suite (registrant
personal data + payment state). Even though no data is handled yet:

- Admin access is gated on the plugin's **own** admin permission
  (`getperms('P')`); grant it narrowly to a single restricted admin class.
- When data arrives: all input via `$tp->toDB()`, output via `$tp->toHTML()`,
  queries parameterized through the `db` class, CSRF via `e_token` (handled by
  `e_admin_ui`), and audit via `e107::getLog()`.

## Front-end sign-up flow (issue #24)

Public registration form + server-side processing on top of the #22 schema.
**No email in v1** (no-op hook only); **no QR** (that is #4 - payment details are
shown as text here).

### Pre-work findings (verified against bundled Lite + reference plugins)

- **SEF route** - `e_url.php` mirrors `race/e_url.php`: a `racereg_url` class with
  `config()` returning `alias`/`regex`/`sef`/`redirect`. One public route,
  `signup` (alias `prihlaska`), redirecting to `signup.php`. Build links with
  `e107::url('racereg', 'signup')`.
- **Front-end form + CSRF** - native (non-`e_admin_ui`) pattern: render the token
  with `e107::getForm()->token()` (a hidden `e-token` = `e_TOKEN`; for guests the
  cookie/JWT CSRF handler issues it), and verify server-side with
  `e107::getSession()->checkFormToken($_POST['e-token'])`. Confirmed in-repo:
  `githubSync` uses `checkFormToken`.
- **Variable symbol** - the #22 generator was a `protected` method inside the
  admin UI. It is now extracted to `includes/racereg_vs.php`
  (`racereg_vs::generate()`) and **reused by both** the admin (#22) and the
  sign-up (#24), so format + uniqueness live in one place.

### Data-model gap and how it was resolved

Issue #24 assumes track config (date-tiered prices, capacity, `requiresApproval`,
`registrationClosed`) lives in `race`, and the registration window + payee IBAN
live in `raceevent` prefs. **Neither existed yet** in this repo. Per the chosen
approach, both source plugins were extended (this is the only deviation from the
"deliverables live in `racereg/`" scoping, and it is required for the flow to
read real config):

- **`raceevent` prefs** (`plugin.xml` + `admin/admin_config.php`): added
  `registrationStartAt`, `registrationEndAt` (INT timestamps, 0 = unbounded) and
  `payeeIban` / `payeeName`. Read via `e107::getPlugConfig('raceevent')`.
  `schema_version` bumped 1 -> 2 (note: `pluginPrefs` are seeded on install;
  existing installs read missing keys via `get($key, $default)`, so no migration
  is strictly required).
- **`race` tracks** (`race_sql.php` + `admin_config.php`): added columns
  `race_capacity`, `race_unlimited_capacity`, `race_requires_approval`,
  `race_registration_closed`, plus a new **`race_price`** sub-table
  (`race_price_race`, `race_price_value` DECIMAL(10,2), `race_price_from` INT) with
  its own admin CRUD mode (`prices`). `race_sql.php` runs only on install, so an
  existing `race` install needs a reinstall or a manual `ALTER`/`CREATE` to gain
  the new columns/table.

### Gating (rejected with a clear message, no record created)

1. `now` within `[registrationStartAt, registrationEndAt]` (raceevent prefs).
2. track `race_registration_closed` not set.
3. track exists. The track `<select>` lists only open tracks
   (`race_registration_closed = 0`).

### Price freeze (server-side only)

`racereg_signup::resolvePrice()` reads `race_price` and picks the row with the
greatest `race_price_from <= now`, stored into `racereg_registration.amount_due`
**frozen** (later `race_price` edits never touch existing rows). No price tier ->
`0.00`. The client never sends price/amount.

### Capacity / approval / placement

Placement sentinel: a registration is on the start list when `start_list_at > 0`
(substitutes and pending rows store `0`). The capacity count therefore uses
`start_list_at > 0 AND deleted_at IS NULL` and is robust to both `0` and `NULL`.

- `requiresApproval` track -> created **pending** (`approval_status = 0`),
  `start_list_at = 0`, **not** placed (placement on approval is #5).
- otherwise `approval_status = 1`; placed (`start_list_at = now`) if
  `unlimitedCapacity` or `countPlaced < capacity`, else substitute
  (`start_list_at = 0`).

### Last-spot race mitigation

Re-check by **insertion order**: after inserting a placed row on a limited track,
re-count placed rows with `registration_id <= newId`. `AUTO_INCREMENT` guarantees
id order == insert order, so two concurrent sign-ups for the final spot resolve
deterministically - the later id lands as rank `capacity + 1` and is demoted to
substitute (its `start_list_at` reset to `0`). This avoids needing a raw-SQL
transaction (constraint: native db class only). Residual risk: only if the demote
`update` itself fails, leaving an admin-correctable overflow.

### Notifications (native e107 events + e_notify addon)

The state-change sites trigger native e107 events directly
(`e107::getEvent()->trigger(...)`) - no wrapper, no include. The events are
`racereg_registration_submitted` (sign-up stored),
`racereg_registration_approved`, `racereg_registration_rejected` and
`racereg_substitute_promoted`. Only the integer registration id is passed (no
PII in the event payload).

`e_notify.php` (class `racereg_notify extends notify`) subscribes to
`racereg_registration_submitted` and e-mails the recipient an admin assigns in
Admin -> Notify (a sign-up name / track / variable symbol / amount plus an
admin-detail link). Until a recipient is assigned the event is a harmless
no-op. PII is loaded inside the handler and escaped on output. After adding the
addon the plugin must be re-scanned (Admin -> Plugins) so e107 registers it
into the `e_notify_list` pref. The approved / rejected / promoted events have no
notifier yet (future addition).

### Native field rendering + datepicker (renderElement)

`racereg_render_form()` builds a `$fields` definition array (`type` + `writeParms`,
the upstream `submitnews.php` pattern) and renders **every** field through
`e107::getForm()->renderElement($key, $value, $fld)` - no hand-written `<input>`
HTML remains. `renderElement` escapes the value itself, so the repopulated
`$_POST` value is passed **raw** (no `toDB`/`toAttribute` on the way in), exactly
as `submitnews.php` passes raw stored values. The layout wrapper
(`racereg_render_row`: label, ` *`, inline error) is unchanged - only the widget
changed.

Types used (verified in `ehandlers/form_handler.php`): `dropdown` (reads
`writeParms['optArray']`), `text`, `email`, and `datestamp`. `track_id` is a
`dropdown` (open tracks); `country` is kept as **`text`** (not the native
`country` type) so the stored value remains a name string, not an ISO code.

- **Birth date** now uses the e107 **datepicker** (`type=datestamp`,
  `writeParms mode=date`), replacing the previous `<input type="date">`. The
  display format follows the **`inputdate`** pref (`form_handler::datepicker()`
  reads `e107::getPref('inputdate', '%Y-%m-%d')`), so it is **not** hardcoded and
  no longer follows the browser locale (which showed `mm/dd/yyyy`, wrong for SK).
  The SK display format is set **centrally** in **Preferences → date display**
  (the `inputdate` pref) and applies site-wide. The datepicker posts a **Unix
  timestamp** by default (`useUnix`), so `birth_date` arrives as an INT timestamp
  and is parsed by `racereg_signup::parseBirthDate()` (numeric guard + past +
  sane year), then stored in the existing INT column.

### Security & GDPR

- Untrusted public input: every field validated server-side (track exists+open,
  required fields, email via `filter_var`, birth date a datepicker Unix timestamp
  + must be past + sane year, required GDPR consent). Stored via `$tp->toDB()` +
  the db class `_FIELD_TYPES` map; output via `$tp->toHTML()` / attribute escaping.
- CSRF enforced (native `e-token`). Honeypot (`website` field) silently rejects
  bots. Price + VS are server-side only and frozen.
- Sign-ups logged via `e107::getLog()` (`RACEREG_02`) with id + track + state
  only - **no PII** in the log line. Honeypot rejects logged as `RACEREG_03`.

### Files (issue #24)

- `e_url.php` - public SEF route.
- `signup.php` - front controller (GET form / POST process + confirmation).
- `templates/racereg_template.php` - form / row / closed / confirmation templates.
- `includes/racereg_signup.php` - validation, gating, price freeze, capacity,
  insert, audit, notify.
- `includes/racereg_vs.php` - shared variable-symbol generator.
- `e_notify.php` - notify addon: e-mails an assigned admin on new sign-ups.
- `languages/English_front.php` + `languages/Slovak_front.php` - front strings.

## Organizer admin actions (issue #26)

Workflow actions on top of the #22 CRUD and #24 sign-up: mark-paid, derived paid
status, approve/reject, substitute promotion. All server-side, CSRF-protected,
logged; no email (the #24 notification hook stays no-op, fired at approve/reject/
promote). Rules live in `includes/racereg_actions.php` (the `racereg_actions`
service) so the controllers stay thin; track flags + capacity are read through
`racereg_signup` (the canonical track reader) so the `race_` column names stay in
one place.

### Native action mechanism

Each row action is a native `e_admin_ui` custom action: the form_ui `options()`
method renders a GET button to `?mode=<m>&action=<x>&id=<id>&e-token=<e_TOKEN>`,
which the dispatcher routes to `<X>Page()` on the controller (e.g. `ApprovePage`,
`PromotePage`, `MarkpaidPage`). Each handler **verifies the e-token server-side**
(`e107::getSession()->checkFormToken(...)`), delegates to `racereg_actions`, and
renders messages + a back link (the `githubSync::syncedPage` pattern). e-token
generation/verification is the native e107 CSRF mechanism.

### 1. Mark-paid

- **Payment row** (payments list): `markPaymentValid($paymentId)` → `status =
  valid (1)`, `paid_at = now`. Only that row changes; erroneous/refunded stay
  selectable; multiple payments per registration preserved.
- **From a registration** (registrations list): `recordRegistrationPayment()`
  inserts ONE valid payment for the outstanding remainder (`amount_due` − sum of
  valid payments); no-op when already fully paid (so it can't double-charge).

### 2. Derived paid status (registrations list)

Computed column `paid_status` (`type=method`, `noedit`/`nocreate`, **not stored**):
`paidState()` compares the sum of **valid** payments to the frozen `amount_due` →
**no-fee** (`amount_due <= 0`) / unpaid (sum 0) / partial (0<sum<due) / paid
(sum≥due). "No fee" (`PAID_NOFEE`, *bez poplatku*) is a **distinct** state: an
entry with nothing owed is never reported as "paid", and "paid" requires
`amount_due > 0` **and** a covering valid payment. Rendered as a coloured label
(no-fee = info badge); the quick filter exposes a `nofee` option too. A raw-SQL-free **quick filter** (links in the help
panel) computes the matching ids in PHP (`registrationIdsByPaidState()`) and
constrains the list with a framework `registration_id IN (...)` clause in
`init()`. (This is N+1 reads per list load - fine for a single-event admin; a true
SQL `HAVING SUM(...)` filter is avoided by the no-raw-SQL constraint.)

### 3. Approval workflow (approval tracks)

- Approve/reject buttons show for pending (`approval_status = 0`) rows on
  `requiresApproval` tracks.
- **Approve** (`approve()`) → `approval_status = 1`; places on the start list
  (`start_list_at = now`) if capacity allows, else keeps it a substitute
  (`start_list_at` stays 0); triggers the `racereg_registration_approved` event.
- **Reject** (`reject()`) → `approval_status = 2`. Recorded via the
  `approval_status` value, **not** soft-delete, so the decision stays auditable
  and distinct from a withdrawal. No placement; if it somehow held a spot, that
  spot is released (and auto-promotion runs). Fires `registrationRejected()`.

### 4. Substitute promotion + auto-promotion on a freed spot

- Manual **Promote** (`promote()`) on a substitute → `start_list_at = now` if
  capacity allows (else a "full" warning); fires `substitutePromoted()`.
- **Auto-promotion**: when a confirmed registration is withdrawn (soft-deleted in
  `beforeDelete()`) or a held spot is released by reject, `autoPromoteNext()`
  promotes the **oldest** eligible substitute on that track (by
  `registration_date`, then id) - one per freed spot. On approval tracks only
  approved substitutes qualify; otherwise anything not rejected.
- **Disable flag source**: the raceevent pref **`disableAutoPromote`** (read via
  `e107::getPlugConfig('raceevent')`), default `0` = auto-on; `1` = manual only.
  The pref is read with a default so it works even before raceevent exposes it in
  its settings UI (exposing the toggle there is a raceevent change, out of scope
  for #26).

Per-track capacity is honoured throughout via `racereg_actions::canPlace()`
(`unlimited_capacity` OR `countPlaced < capacity`, placement sentinel
`start_list_at > 0`). Admin actions are single-organizer / low-concurrency, so a
plain capacity check is used (the front-end sign-up keeps its insertion-order
re-check from #24).

### Logging

Every state change is logged via `e107::getLog()`: `RACEREG_10` mark payment
valid, `RACEREG_11` record registration payment, `RACEREG_12` approve, `RACEREG_13`
reject, `RACEREG_14` manual promote, `RACEREG_15` auto-promote (plus `RACEREG_01`
soft-delete from #22). PII is never written to the log line (ids/track/state only).

## Shared place + price on admin create (Refs #26)

The price-freeze + capacity-placement logic from the sign-up flow (#24) is the
**single source of truth** and now lives in two reusable methods on
`racereg_signup`:

- `applyTrackPlacementAndPrice($trackId, $ts)` - resolves the date-tiered track
  price via the canonical `race` read API (`race_registration::getTrackRegistration()`,
  #30) and decides placement: `requires_approval` → pending (`approval_status = 0`,
  not placed); else placed on the start list if `unlimited_capacity` or
  `countPlaced < capacity` (sentinel `start_list_at > 0`), otherwise a substitute.
  Returns the server-side fields (`amount_due`, `approval_status`, `start_list_at`)
  plus `state` / `placed` / `limited` for the caller.
- `confirmPlacement($trackId, $newId, $state)` - the post-insert last-spot
  concurrency re-check (insertion-order rank vs capacity), shared so both paths
  demote a lost final spot identically.

`racereg_signup::process()` (front-end) was refactored to call these - behaviour
is **identical**. The registrations `e_admin_ui` now calls the same logic on a
manual add: `beforeCreate()` freezes the price + placement (overriding anything
posted - price/placement are server-side only, never trusted from the admin POST
beyond the chosen `track_id`), and `afterCreate()` runs the last-spot re-check and
logs the freeze/placement as `RACEREG_03`. A free entry is still placed; an
`amount_due = 0` row shows **no-fee**, not paid.

This fixes manual adds previously looking like substitutes (always "promote") with
no resolved price: a manual add to a capacity-5 / 10 €-from-1.1 track now freezes
10 € and lands on the start list when there is room.

`resolvePrice()` on `racereg_signup` now delegates to `race_registration::resolvePrice()`
so the date-tiered price logic is **not** re-implemented in two places.
## PAY by square QR (issue #25) - client-side only

The confirmation page (#24) renders a Slovak **PAY by square** payment QR
entirely in the browser. The target hosting has no `xz` binary and no
`exec()`/`shell_exec()`, so server-side LZMA generation is impossible - the QR is
built client-side from a vendored, committed bundle. The QR is **not stored**
(generated on the fly) and the textual payment details remain the fallback.

### Server side

The QR renderer (originally `racereg_render_qr()` in `signup.php`, now
`racereg_payment_view::renderQr()`, see Refs #40) emits, below the textual payment table, an
initially-`hidden` block containing an empty mount + a JSON island with the
**non-secret** payment data (the same IBAN / beneficiary / SWIFT / amount_due /
variable symbol / `EUR` already shown as text):

```html
<div id="racereg-qr" hidden>
  <h4>…</h4><p>…</p>
  <div id="racereg-qr-mount" …></div>
  <script type="application/json" id="racereg-qr-data">{…}</script>
</div>
```

The JSON is `json_encode(..., JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)`
so `< > & ' "` are escaped (a beneficiary name/note can never break out of the
`<script>` island), while diacritics stay readable. `amount` is dot-decimal
machine form; `payee_swift` was added to the `racereg_signup::process()` result
(reads the `payeeSwift` raceevent pref from #28). No QR is emitted when no payee
IBAN is configured.

The bundle is enqueued only on the confirmation page via the native asset
mechanism: `e107::js('footer', e_PLUGIN_ABS . 'racereg/js/racereg-qr.bundle.js')`
(same pattern as `timetracker`/`race` datatables init). Local file only - no CDN,
no runtime npm.

### Client side / vendored bundle

`js/racereg-qr.bundle.js` is a committed, minified IIFE browser bundle. Its init
(`js/src/racereg-qr.entry.js`) reads the JSON island, builds the bysquare
`PaymentOrder` model (EUR, amount, variableSymbol, beneficiary, IBAN[, BIC]),
`encode()`s it (pure-JS LZMA - no `xz`), renders the QR as a scalable SVG
(`qrcode-generator`) into the mount, and reveals the block. Any missing element
or thrown error is logged via `console.warn('[racereg-qr]', e)` and returns,
leaving the textual fallback. bysquare deburrs diacritics by default (bank
compatibility) - left as-is.

**Beneficiary required (#40):** bysquare `encode()` throws if the beneficiary
name is empty, which would silently suppress the QR. Two guards keep it from ever
reaching the client: (1) `racereg_payment_view::renderQr()` does not emit the QR
island when `payee_name` is empty (symmetric with the empty-IBAN guard); (2)
raceevent `beforePrefsSave()` flags saving an IBAN without a beneficiary name. A
build-time `encode-check.mjs` (run by `npm run build`) also encodes a valid
payload and fails the build if the `bysquare/pay` import or `encode()` regresses.

**Vendored libraries** (MIT): `bysquare@4.0.0` (xseman), `qrcode-generator@2.0.4`.
**Build** (offline at runtime; rebuild only when bumping deps):

```
cd e107_plugins/racereg/js/src
npm install            # bysquare, qrcode-generator, esbuild (pinned in package.json)
npm run build          # -> ../racereg-qr.bundle.js  (esbuild --bundle --format=iife --target=es2017 --minify)
```

`js/src/` (entry + `package.json`) is committed for reproducibility but is **not**
served at runtime - only the built `racereg-qr.bundle.js` is. `node_modules` is
git-ignored and never shipped.

### Security / authority

The exposed data is identical to what is already shown as text - **not secret**,
safe client-side. The QR is **display-only**: the obligation (`amount_due`) and
the reconciliation key (`variable_symbol`) are server-side and authoritative, so
tampering with the client-rendered QR cannot change the stored obligation or how
a payment is matched. No server-side QR/LZMA, no `exec()`, nothing stored.

## Payment-details view + tokenized public pay page (Refs #40)

The payee/IBAN/amount/VS table + QR island that lived inline in `signup.php`
(`racereg_render_qr()` + the confirmation payment block) is extracted into a
single shared renderer, `includes/racereg_payment_view.php`, reused in three
places with identical markup:

- `racereg_payment_view::buildData($reg)` assembles the **non-secret** payment
  data (payee IBAN / name / SWIFT from the `raceevent` prefs; `amount_due` +
  `variable_symbol` from the registration row).
- `render($data, $tp)` returns the payment table + reference note + QR island
  (byte-identical to the old inline copy, so the confirmation output is
  unchanged). `renderQr()` is the moved `#25` island. Each caller enqueues the
  vendored bundle via `e107::js('footer', e_PLUGIN_ABS . 'racereg/js/racereg-qr.bundle.js')`.

**Callers:** (1) `signup.php` confirmation (refactor + a tokenized pay link the
applicant can save); (2) the admin **Show payment details** row action
(`PaymentPage()` in `admin/admin_config.php`, behind `getperms('P')` + `e-token`,
enqueues the bundle in the admin context); (3) the new public page `pay.php`.

### `pay_token` + the public page

- Schema (`racereg_sql.php`): `pay_token VARCHAR(32) DEFAULT NULL`, **UNIQUE**.
  `DEFAULT NULL` (not `''`) so MySQL allows multiple token-less legacy rows in the
  UNIQUE index until they are backfilled. Unguessable 128-bit random
  (`bin2hex(random_bytes(16))`), **not derived from `registration_id`**.
- Generated on **both** create paths: front sign-up (`racereg_signup::process()`)
  and admin create (`afterCreate()` → `ensureToken()`). Legacy rows are backfilled
  lazily when the admin opens payment details (same `ensureToken()`).
- Route: `e_url.php` adds `pay` (alias `platba`) → `pay.php`; the token rides in
  the **path** (`/platba/<token>/`, regex capture group → `redirect …?t=$1`), built
  via the `{token}` sef placeholder by `racereg_payment_view::payUrl()`
  (`e107::url('racereg','pay', array('token' => …))`). `pay.php` still reads
  `$_GET['t']`, now populated from `$1`.
- `pay.php` resolves the token with `resolveByToken()`: strict format check
  (`^[a-f0-9]{32}$`) **then** an escaped `db` lookup — never raw SQL. Unknown /
  malformed / rate-limited → one **generic not-found** (no leak, no id
  enumeration). A light per-session sliding rate limit blunts token scanning.
- **PII minimisation:** the public page shows only the payment essentials + the
  applicant's own first name + derived paid state — no birth date, address,
  contact details, and no other registration.

## Read API relocated into racereg (`includes/race_registration.php`)

The #30 `race_registration` read API used to live in `racetrack`
(`racetrack/includes/race_registration.php`) even though `racetrack` never called
it - its only consumer is `racereg_signup`. It was moved here, into
`racereg/includes/race_registration.php` (history preserved via `git mv`). The
class name stays `race_registration`, so every `race_registration::` call site is
unchanged; the only edit was the loader in `racereg_signup.php`:

```php
require_once(e_PLUGIN . 'racereg/includes/race_registration.php'); // was racetrack/includes/...
```

The API still reads the `race` / `race_price` tables (owned by racetrack) directly
- the correct direction, since `racereg` already declares a `<dependency>` on
`racetrack` (`plugin.xml`). It keeps its `(int)` casts and parameterised db-class
queries (no string-built SQL). racetrack now contains no registration read logic.

The complementary admin-UI change lives in racetrack: the four registration-config
fields (capacity / unlimited / approval / registration-closed) appear on the track
edit form only when racereg is installed - an opt-in "Registrácia" tab added at
runtime via `e107::isInstalled('racereg')`, **not** a racetrack→racereg dependency.
See racetrack's NOTES.md ("Opt-in Registration tab").

## Conventions for later issues

- Dates are stored as **Unix timestamps in INT columns** (`type=datestamp,
  data=int`), never SQL `DATE`.
- Table names may use underscores; the plugin **folder** name must not
  (`racereg`).

## Issue #47: config documentation on the admin help page

`racereg_registration_ui::renderHelp()` appends `LAN_RACEREG_CONFIG_DOC_HELP`
(EN/SK) documenting the cross-plugin config the registration/payment flow depends
on: the **required payee fields** (beneficiary name mandatory for PAY by square),
the **registration window** rule (`start < end`), and the **per-track** fields
(capacity / unlimited / approval / registration-closed / price tiers, incl. the
"bez poplatku" free state). Help text is static LAN constants only (no echoed
input, no XSS).

## Confirmation-page preview (admin-only, inert)

Lets the main admin preview the applicant's confirmation page ("thank you +
payment details", incl. the PAY by square QR) with sample data, so the real
front-end theme/layout and the QR can be checked without submitting the public
form. It is **inert**: no DB write, no event trigger, no e-mail.

- Front-end endpoint: `signup.php?preview_confirm=<state>`, guarded by
  `getperms('0')` (main admin). A non-admin hitting it just gets the normal
  form (`$showForm` stays true). It builds fake data via the new
  `includes/racereg_preview.php` and reuses the existing confirm render path
  (`racereg_render_confirm` + `racereg_payment_view`) so the preview is
  pixel-identical to a real submission.
- Admin control: `admin/admin_registration.php::ListPage()` (the existing
  `reginfo` page) shows three state buttons + an iframe pointing at the endpoint
  via `e_PLUGIN_ABS`. Inline vanilla JS swaps the iframe `src` on click.
- The tested class `includes/racereg_signup.php` is **not** touched; the feature
  reads its `STATE_*` constants read-only only.
- **Deviation from the spec prompt:** the five new admin LAN keys were added in
  `define()` form (not the array form shown in the prompt) to match the existing
  `English_admin.php` / `Slovak_admin.php` files, which use `define()` throughout.
- **Follow-up (out of scope, Phase 2):** the e-mail confirmation template(s) and
  a "send test e-mail" action are a later phase - no e-mail sending here.
