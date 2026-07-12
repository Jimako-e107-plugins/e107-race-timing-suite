# During the race

## Recording times

Times reach the system in one of three ways — you can mix them:

| | |
| --- | --- |
| **The keypad** | A marshal opens `/kontrola/{code}/{password}/` on their phone and types bibs. [racetiming](../plugins/racetiming.md#the-checkpoint-keypad--the-tool-for-race-day) |
| **Generate Start** | One start time written for a whole track at once — a mass start, or when there is no reader at the start. [racetiming → Generate Start](../plugins/racetiming.md#generate-start) |
| **RFID import** | Passings copied from the chip provider's database. [racerfid](../plugins/racerfid.md) |

## Watching the race

Leave these open on a screen — they refresh themselves:

- **Online results** — the standings
- **Arrivals board** (dobeh) — who has just come in

Both are in [racereports](../plugins/racereports.md#the-two-you-leave-on-a-screen-during-the-race).

## When something goes wrong

| Problem | What to do |
| --- | --- |
| A bib was typed wrong | Fix the row in [racetiming → Time Entries](../plugins/racetiming.md#time-entries--add-time-entry). Every report updates immediately. |
| Someone abandoned | Mark them `DNF` — from the keypad, or in Time Entries. |
| Someone was disqualified | Mark them `DSQ`. |
| Someone never started | Mark them `DNS`. |
| A chip did not read | Add the passing by hand in Time Entries. |
| "The RFID import is not working" | Check the refresh interval is not 0, and that the plugin is Active. |

**Never edit a result.** Results are calculated. Fix the **time**, and the result follows.

## Exporting live

If you use terminovka.sk, leave the batch export page open — it sends finish times as they arrive.
Close the page and nothing is sent. [terminovka](../plugins/terminovka.md)
