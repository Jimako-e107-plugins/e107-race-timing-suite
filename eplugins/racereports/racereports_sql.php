# racereports - install schema (no-op).
#
# racereports declares NO tables of its own yet. e107 parses this file for
# CREATE TABLE statements at install; with none present this is a clean no-op.
#
# Deliberately NOT declared here (per the suite architecture):
#   - race_result  -> declared by terminovka (terminovka_sql.php; result staging / export log).
#   - race_archive -> declared by racetrack  (racetrack_sql.php; frozen archive snapshots).
# Each table is declared by its owning plugin above; racereports must not declare
# them: a second CREATE for a table another plugin owns is a duplicate-ownership
# install conflict. racereports will add its OWN ranking/results table here when
# that logic is implemented. See NOTES.md ("Table ownership").
