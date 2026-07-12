# Shortcodes and menus

## Shortcodes

Usable in any e107 page or template.

| Shortcode | Plugin | Shows |
| --- | --- | --- |
| `{RACEEVENT_EVENT_NAME}` | raceevent | The event name. |
| `{RACEEVENT_EVENT_DESCRIPTION}` | raceevent | The event description, rendered as HTML. |
| `{TIMETRACKER_VSTUP}` | racetiming | A list of links to the checkpoint keypad — one per checkpoint. |

### About `{TIMETRACKER_VSTUP}`

The odd name is a leftover from the plugin this code came from. It was kept deliberately so that
pages already using it keep working.

> **The page you put it on must not be public.** It lists every keypad link — that is, every
> checkpoint password. Restrict it to a user class, or protect it with a password. It exists purely
> so the organizer has one place to copy the links from before sending them to the marshals.

## Menus

Place these through the e107 Menu Manager.

| Menu | Plugin | Shows |
| --- | --- | --- |
| Welcome | raceevent | The event name and description. |
| Checkpoints | racetrack | The checkpoints of the event. |
| Categories | racers | The categories, linking to their start lists and results. |

## Site links

`racereports` provides site links you can add to your website menu: a link per track and category
for the online, finish, start and checkpoint reports. Add them through **Admin → Site Links**.
