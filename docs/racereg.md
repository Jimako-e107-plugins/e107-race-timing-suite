# racereg — registration and payments

{% hint style="warning" %}
**This plugin is not finished. Do not run a live event on it.**

Notifications and e-mail sending are **not implemented** — an applicant receives no confirmation,
and the organizer is not alerted to a new sign-up. The amount due is entered by hand; there is no
automatic price freeze from the track's price tiers.

It is published as a **reference implementation** — a worked example of registration, approval,
substitutes and PAY by square payments in e107 — not as a finished product. Use it to build on, or
take on-site registration in `racers` instead.
{% endhint %}

## What it does

`racereg` handles **online sign-up before the event**: an application form on the event website,
approval by the organizer, a place on the start list, and payment by bank transfer with a QR code.

It is deliberately **separate from `racers`**. A registration is an *application* — it may be
pending, rejected, unpaid, or a substitute. A racer is someone who will actually start. The two are
not the same thing and are not linked by a foreign key.

## Requires / required by

| | |
| --- | --- |
| **Requires** | `raceevent`, `racetrack` |
| **Required by** | Nothing |
| **Owns** | `racereg_registration`, `racereg_payment` |

## Installation notes

Configure these **before** opening registration:

| Where | What |
| --- | --- |
| `raceevent` → Event configuration → **Registration** tab | Registration opens / closes, Payee IBAN, Beneficiary name |
| `racetrack` → Track list → **Registration** tab | Capacity, Unlimited capacity, Requires approval, Registration closed |
| `racetrack` → **Price tiers** | The entry fee. A track with no tier is treated as free. |

> **Restrict this plugin's admin permission.** It holds the most personal data in the suite —
> names, addresses, birth dates, phone numbers, e-mails (`LAN_RACEREG_CONFIG_HELP`).

## The lifecycle of a registration

```
       applicant fills in the form
                  │
                  ▼
            ┌──────────┐
            │ Pending  │◄── if the track requires approval
            └────┬─────┘
                 │  organizer: Approve                organizer: Reject
                 ▼                                            │
        ┌────────────────┐   track full?                      ▼
        │   Approved     │──── yes ──►  Substitute       ┌──────────┐
        └────────┬───────┘                   │           │ Rejected │
                 │ no                        │           └──────────┘
                 ▼                    a place frees up
         ON THE START LIST  ◄──── organizer: Promote
```

Payment runs alongside and does not gate any of this — a registration can be approved and unpaid, or
paid and pending.

### Approval status

| Status | LAN key |
| --- | --- |
| Pending | `LAN_RACEREG_APPROVAL_0` |
| Approved | `LAN_RACEREG_APPROVAL_1` |
| Rejected | `LAN_RACEREG_APPROVAL_2` |

### Payment status

The **Paid** column is **calculated**, not stored (`LAN_RACEREG_PAID_STATUS_HELP` — *Derived from
valid payments vs amount due. Display only - not stored*):

| Shown | Meaning |
| --- | --- |
| No fee | Amount due is 0. |
| Unpaid | No valid payment. |
| Partial | Valid payments are less than the amount due. |
| Paid | Valid payments cover the amount due. |

A registration can have **several payment rows** (e.g. a partial payment, then the rest). Each row
has its own status: Pending, Valid, Erroneous, Refunded (`LAN_RACEREG_PAYST_0`–`3`).

### The variable symbol

Every registration gets an auto-generated, **unique** variable symbol, locked once created
(`LAN_RACEREG_VS_HELP`). It is the payment reference — how a bank transfer is matched back to a
person.

## Admin area

Five items: **Registrations**, **Add registration**, **More info**, **Payments**, **Add payment**,
plus a **track overview**.

---

### Registrations / Add registration

The applications. Contains **personal data** — organizer-only, never exposed on the front end
(`LAN_RACEREG_REG_HELP`).

| Field | LAN key | Admin help text | Notes |
| --- | --- | --- | --- |
| Track | `LAN_RACEREG_TRACK` | — | Which course they applied for. |
| First name / Last name | `LAN_RACEREG_FIRST_NAME` / `..._LAST_NAME` | — | |
| Birth date | `LAN_RACEREG_BIRTH_DATE` | — | Used to place them in an age category. |
| Street / City / Postal code / Country | `LAN_RACEREG_STREET` … `..._COUNTRY` | — | |
| Email / Phone | `LAN_RACEREG_EMAIL` / `..._PHONE` | — | |
| Club | `LAN_RACEREG_CLUB` | — | |
| Registration date | `LAN_RACEREG_REG_DATE` | — | Set automatically. |
| On start list (date) | `LAN_RACEREG_START_LIST_AT` | — | When they were placed. Empty = not on the start list. |
| Variable symbol | `LAN_RACEREG_VS_HELP` | Auto-generated unique numeric symbol. Locked after creation. | Cannot be changed. |
| Amount due | `LAN_RACEREG_AMOUNT_DUE_HELP` | Entered manually in this version. Automatic price freeze comes later. | **Type it in by hand.** The price tier is not applied automatically — this is one of the unfinished parts. |
| Approval status | `LAN_RACEREG_APPROVAL` | — | Pending / Approved / Rejected. |
| Private note | `LAN_RACEREG_PRIVATE_NOTE` | — | Organizer-only. |

#### Row actions

| Action | LAN key | What it does |
| --- | --- | --- |
| **Approve** | `LAN_RACEREG_ACT_APPROVE` | Approves and places on the start list — or, if the track is full, keeps them as a **substitute** (`LAN_RACEREG_MSG_APPROVED_SUB`). |
| **Reject** | `LAN_RACEREG_ACT_REJECT` | Rejects. Asks for confirmation. |
| **Promote** | `LAN_RACEREG_ACT_PROMOTE` | Moves a substitute onto the start list when a place frees up. |
| **Mark paid** | `LAN_RACEREG_ACT_MARKPAID` | Records a valid payment covering the amount due. |
| **Show payment details** | `LAN_RACEREG_ACT_PAYMENT` | Opens the payment page with the IBAN, variable symbol and QR code. |

> **Deletes are soft.** A deleted registration is hidden, not destroyed — kept for audit and
> restore (`LAN_RACEREG_SOFT_DELETED`).

---

### Payments / Add payment

The payment rows, linked to a registration. Use the filter box to find a specific registration's
payments (`LAN_RACEREG_PAY_HELP`).

| Field | LAN key |
| --- | --- |
| Registration | `LAN_RACEREG_PAY_REGISTRATION` |
| Amount | `LAN_RACEREG_PAY_AMOUNT` |
| Status | `LAN_RACEREG_PAY_STATUS` |
| Paid at | `LAN_RACEREG_PAY_PAID_AT` |
| Note | `LAN_RACEREG_PAY_NOTE` |

---

### More info / track overview

A per-track summary: how many registrations are pending, approved and rejected on each track. Use it
to see at a glance whether a track is filling up.

## Front-end

### Sign-up form

| | |
| --- | --- |
| **URL** | `/prihlaska/` |
| **File** | `racereg/signup.php` |
| **Route key** | `signup` |

The public application form. It accepts a sign-up only:

- while the **registration window** in `raceevent` is open, and
- if the chosen track is not marked **Registration closed** in `racetrack`.

When the track is full, the applicant is accepted as a **substitute**
(`LAN_RACEREG_MSG_CREATE_SUBSTITUTE`).

### Payment page

| | |
| --- | --- |
| **URL** | `/platba/{token}/` |
| **File** | `racereg/pay.php` |
| **Route key** | `pay` |

A page the applicant can return to later to pay. It shows:

- the **payee**, **IBAN** and **SWIFT** from `raceevent`,
- the **variable symbol** to use as the payment reference (`LAN_RACEREG_PAY_NOTE_TEXT`),
- a **PAY by square QR code** — scan it with a banking app and the IBAN, amount and variable symbol
  are filled in (`LAN_RACEREG_QR_HINT`).

Each registration has its own **unguessable token** in the URL. That token is the only protection —
anyone with the link sees that person's payment details.

> Without a **Payee IBAN and Beneficiary name** in `raceevent`, no QR code can be produced
> (`LAN_RACEREG_PAY_NO_IBAN`).

## Notes and limitations

**The unfinished parts, plainly:**

- **No e-mails.** Applicants get no confirmation, no payment reminder, no approval notice. The
  event hooks for it exist (`racereg_registration_submitted`, `..._approved`, `..._rejected`,
  `racereg_substitute_promoted`) but nothing listens to them.
- **No organizer notification** of a new sign-up.
- **The amount due is typed in by hand** for every registration. The price tiers in `racetrack` are
  not applied automatically.
- **No card payments.** Bank transfer with a QR code only.

**Other things worth knowing:**

- **Registrations are not racers.** Approving someone places them on the start list, but the two
  tables stay independent.
- **Payment does not block approval, and approval does not block payment.** The organizer decides.
- **This is the most sensitive data in the suite.** Restrict the admin permission, and remember the
  payment link is protected only by its token.
