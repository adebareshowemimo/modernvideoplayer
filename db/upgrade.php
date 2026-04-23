<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Perform plugin upgrade.
 *
 * @param int $oldversion old version
 * @return bool
 */
function xmldb_modernvideoplayer_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026042001) {
        $table = new xmldb_table('modernvideoplayer_progress');

        $fields = [
            new xmldb_field('duration', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0.00', 'sessiontoken'),
            new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'completiontime'),
            new xmldb_field('lastplaybackrate', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '1.00', 'lastheartbeat'),
            new xmldb_field('lastvisibility', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'lastplaybackrate'),
            new xmldb_field('integrityfailures', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'suspiciousflags'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026042001, 'modernvideoplayer');
    }

    if ($oldversion < 2026042002) {
        $table = new xmldb_table('modernvideoplayer');
        $field = new xmldb_field('showcontroltext', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'allowfullscreen');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042002, 'modernvideoplayer');
    }

    if ($oldversion < 2026042003) {
        $table = new xmldb_table('modernvideoplayer');
        $field = new xmldb_field('titleposition', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'left', 'allowfullscreen');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042003, 'modernvideoplayer');
    }

    if ($oldversion < 2026042004) {
        $table = new xmldb_table('modernvideoplayer');
        $fields = [
            new xmldb_field('showprimarynav', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'allowfullscreen'),
            new xmldb_field('showcourseindex', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showprimarynav'),
            new xmldb_field('showrightblocks', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showcourseindex'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026042004, 'modernvideoplayer');
    }

    if ($oldversion < 2026042005) {
        $table = new xmldb_table('modernvideoplayer');

        // Ensure pre-existing nav fields exist (idempotent guard).
        $existing = [
            new xmldb_field('showprimarynav', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'allowfullscreen'),
            new xmldb_field('showcourseindex', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showprimarynav'),
            new xmldb_field('showrightblocks', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showcourseindex'),
        ];
        foreach ($existing as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // New field: secondary navigation + page header visibility.
        $field = new xmldb_field('showsecondarynav', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'showprimarynav');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042005, 'modernvideoplayer');
    }

    if ($oldversion < 2026042007) {
        $table = new xmldb_table('modernvideoplayer');
        $field = new xmldb_field('autoplay', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'unmuted', 'allowfullscreen');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042007, 'modernvideoplayer');
    }

    if ($oldversion < 2026042008) {
        $table = new xmldb_table('modernvideoplayer');
        $field = new xmldb_field('defaultcaptionlang', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, 'en', 'autoplay');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042008, 'modernvideoplayer');
    }

    if ($oldversion < 2026042009) {
        // No schema changes; new file area 'chapters' introduced for WebVTT chapter tracks.
        upgrade_mod_savepoint(true, 2026042009, 'modernvideoplayer');
    }

    if ($oldversion < 2026042010) {
        // No schema changes; added keyboard shortcuts + playback speed menu AMD modules.
        upgrade_mod_savepoint(true, 2026042010, 'modernvideoplayer');
    }

    if ($oldversion < 2026042011) {
        // No schema changes; formalised privacy provider + PHPUnit coverage.
        upgrade_mod_savepoint(true, 2026042011, 'modernvideoplayer');
    }

    if ($oldversion < 2026042012) {
        // No schema changes; view.php hotfix for js_call_amd payload size.
        upgrade_mod_savepoint(true, 2026042012, 'modernvideoplayer');
    }

    if ($oldversion < 2026042013) {
        // No schema changes; PHPUnit coverage for external web services.
        upgrade_mod_savepoint(true, 2026042013, 'modernvideoplayer');
    }

    if ($oldversion < 2026042014) {
        // No schema changes; PHPUnit coverage for custom completion rules.
        upgrade_mod_savepoint(true, 2026042014, 'modernvideoplayer');
    }

    if ($oldversion < 2026042015) {
        // Gradebook integration: add numeric grade column to the main table.
        $table = new xmldb_table('modernvideoplayer');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '100', 'timemodified');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042015, 'modernvideoplayer');
    }

    if ($oldversion < 2026042016) {
        // Learner bookmarks: new table for per-user timestamp bookmarks.
        $table = new xmldb_table('modernvideoplayer_bookmarks');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('modernvideoplayerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('position', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0.00');
            $table->add_field('label', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('modernvideoplayerfk', XMLDB_KEY_FOREIGN, ['modernvideoplayerid'], 'modernvideoplayer', ['id']);
            $table->add_key('userfk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('modernvideoplayeruserid', XMLDB_INDEX_NOTUNIQUE, ['modernvideoplayerid', 'userid']);

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026042016, 'modernvideoplayer');
    }

    return true;
}
