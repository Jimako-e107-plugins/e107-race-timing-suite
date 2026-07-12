# Page URLs and routes

Every public page of the suite has a friendly URL. Here they all are.

## raceevent

| Page | URL |
| --- | --- |
| Event overview — a directory of every published page | `/preteky/` |

## racetrack

| Page | URL |
| --- | --- |
| Track page | `/pretek/{track id}/{track sef}/` |
| Archive of a past edition | `/archiv/{archive sef}/` |

## racers

| Page | URL |
| --- | --- |
| Racer list | `/racers/list/` |
| Start list | `/racers/{track}/{category}/` |
| On-site registration | *(registration route)* |

## racetiming

| Page | URL |
| --- | --- |
| **Checkpoint keypad** | `/kontrola/{checkpoint code}/{checkpoint password}/` |

> This URL is the marshal's tool **and** its own access control. Treat it as a secret; regenerate the
> passwords after the race.

## racereports

| Report | URL |
| --- | --- |
| Online results | `/online/{track}/{category}/` |
| Arrivals board | `/dobeh/{track}/{checkpoint}/` |
| Full results | `/aktualne/{track id}/` |
| Results — finish | `/finish/{track}/{category}/` |
| Results — SUT | `/stu/{track id}/` |
| Start list | `/start/{track}/{category}/` |
| Checkpoint times | `/point/{track}/{checkpoint}/` |
| Racer progression | `/racer/{bib}/` |
| Segment (between two points) | `/custom/{track}/{point 1}/{point 2}/` |

Use `all` or `komplet` in place of a category to get every category on one page.

## racereg

| Page | URL |
| --- | --- |
| Sign-up form | `/prihlaska/` |
| Payment page | `/platba/{token}/` |

## A note on the Slovak names

`pretek`, `preteky`, `kontrola`, `prihlaska`, `platba`, `archiv`, `aktualne`, `dobeh`, `stu` — these
are Slovak words, kept as the URL segments because changing them would break every existing link and
bookmark on sites already running the suite.

They can be changed: the URL segment of each route is editable in e107's admin (**Admin → URLs**),
independently of the code.
