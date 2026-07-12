# Preparing an event

The order below matters — each step depends on the one before it.

## 1. The event itself

- Set the event name, date, town and venue — [raceevent → Event configuration](../plugins/raceevent.md#event-configuration)
- If you will take online sign-ups, also fill in the **Registration** tab: the registration window, the payee IBAN and the beneficiary name

## 2. The structure of the race

- Create the tracks — [racetrack → Track list](../plugins/racetrack.md#track-list)
- Create the checkpoints on each track — [racetrack → Checkpoints](../plugins/racetrack.md#checkpoints)
  - **Code the start `start` and the finish `finish`.** Everything about elapsed time depends on this.
  - Give each checkpoint a password — it protects the marshal's keypad
- Set the entry fees — [racetrack → Price tiers](../plugins/racetrack.md#price-tiers)

## 3. The people

- Create the categories — [racers → Categories](../plugins/racers.md#categories)
- Add the competitors — [racers → List](../plugins/racers.md#list--add-racer), or let them sign up through [racereg](../plugins/racereg.md)
- Assign bib numbers. **They are strings — leading zeros matter.**

## 4. Check before race day

- Open the start lists and read them — [racers → Start lists](../plugins/racers.md#start-lists)
- Open one **keypad link** and confirm it loads: `/kontrola/{code}/{password}/`
- If you use RFID: run the connection test — [racerfid → Test database access](../plugins/racerfid.md#test-database-access)
- If you use terminovka.sk: run the configuration test and set each track's external ID — [terminovka](../plugins/terminovka.md)

## 5. On the morning

- Send each marshal the keypad link **for their own checkpoint** — nothing else
- Put the [online results](../plugins/racereports.md) page on a screen in the finish area
