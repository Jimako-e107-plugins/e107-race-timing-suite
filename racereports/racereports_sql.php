# racereports - install schema (no-op).
#
# racereports declares NO tables of its own yet. e107 parses this file for
# CREATE TABLE statements at install; with none present this is a clean no-op.
#
# Deliberately NOT declared here (per the suite architecture):
#   - race_result  -> terminovka-owned (result staging / export log).
#   - race_archive -> raceevent-owned  (frozen archive snapshots).
# Both currently still live in timetracker/timetracker_sql.php (legacy) pending
# the later extraction to their owners. Either way, racereports must not declare
# them: a second CREATE for a table another plugin owns is a duplicate-ownership
# install conflict. racereports will add its OWN ranking/results table here when
# that logic is implemented. See NOTES.md ("Table ownership").
