<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * terminovka plugin - terminovka_setup.php
 *
*/

/*
 * Install/upgrade hooks. Migration: copy legacy race.race_extid values into
 * terminovka_track, then drop the race.race_extid column. The core SQL-diff step
 * creates terminovka_track (terminovka_sql.php) BEFORE these post hooks run.
 * Guarded by a column-exists check, so it is idempotent and a no-op on fresh
 * installs.
 */

if (!defined('e107_INIT'))
{
    exit;
}

class terminovka_setup
{
    /**
     * Run the ownership migration after an upgrade (e.g. 1.0 -> 1.1).
     */
    public function upgrade_post()
    {
        $this->migrateExternalTrackId();
    }

    /**
     * Also migrate when terminovka is freshly installed onto a site that
     * already carries the legacy race.race_extid column with data.
     */
    public function install_post()
    {
        $this->migrateExternalTrackId();
    }

    /**
     * Copy legacy race.race_extid values into terminovka_track and drop the
     * legacy column. Idempotent: does nothing once the column is gone.
     */
    private function migrateExternalTrackId()
    {
        $db = e107::getDb();

        // Nothing to do on fresh installs / once already migrated.
        if (!$db->field('race', 'race_extid'))
        {
            return;
        }

        // Materialise the rows first so writes below do not clobber the
        // result set. Only non-zero IDs are worth migrating.
        $rows = $db->retrieve('race', 'race_id, race_extid', 'race_extid != 0', true);

        foreach ((array) $rows as $row)
        {
            $raceId = (int) $row['race_id'];
            $ext    = (int) $row['race_extid'];

            if ($raceId < 1)
            {
                continue;
            }

            // Upsert keyed by race_id (UNIQUE) - parameterized data arrays.
            $exists = $db->retrieve('terminovka_track', 'terminovka_track_id', 'race_id=' . $raceId);

            if ($exists)
            {
                $db->update('terminovka_track', array(
                    'ext_id'       => $ext,
                    'WHERE'        => 'race_id=' . $raceId,
                    '_FIELD_TYPES' => array('ext_id' => 'int'),
                ));
            }
            else
            {
                $db->insert('terminovka_track', array(
                    'terminovka_track_id' => 0,
                    'race_id'             => $raceId,
                    'ext_id'              => $ext,
                    '_FIELD_TYPES'        => array(
                        'terminovka_track_id' => 'int',
                        'race_id'             => 'int',
                        'ext_id'              => 'int',
                    ),
                ));
            }
        }

        // Drop the legacy column so race matches racetrack_sql.php (db_verify
        // does not drop extra columns itself).
        $db->gen('ALTER TABLE `' . MPREFIX . 'race` DROP `race_extid`');
    }
}
