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
 * Privacy provider for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
/**
 * Privacy provider for mod_modernvideoplayer.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored data.
     *
     * @param collection $items metadata collection
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('modernvideoplayer_progress', [
            'modernvideoplayerid' => 'privacy:metadata:modernvideoplayer_progress:modernvideoplayerid',
            'userid' => 'privacy:metadata:modernvideoplayer_progress:userid',
            'sessiontoken' => 'privacy:metadata:modernvideoplayer_progress:sessiontoken',
            'duration' => 'privacy:metadata:modernvideoplayer_progress:duration',
            'lastposition' => 'privacy:metadata:modernvideoplayer_progress:lastposition',
            'maxverifiedposition' => 'privacy:metadata:modernvideoplayer_progress:maxverifiedposition',
            'totalsecondswatched' => 'privacy:metadata:modernvideoplayer_progress:totalsecondswatched',
            'percentcomplete' => 'privacy:metadata:modernvideoplayer_progress:percentcomplete',
            'completed' => 'privacy:metadata:modernvideoplayer_progress:completed',
            'completiontime' => 'privacy:metadata:modernvideoplayer_progress:completiontime',
            'timecreated' => 'privacy:metadata:modernvideoplayer_progress:timecreated',
            'lastheartbeat' => 'privacy:metadata:modernvideoplayer_progress:lastheartbeat',
            'lastplaybackrate' => 'privacy:metadata:modernvideoplayer_progress:lastplaybackrate',
            'lastvisibility' => 'privacy:metadata:modernvideoplayer_progress:lastvisibility',
            'suspiciousflags' => 'privacy:metadata:modernvideoplayer_progress:suspiciousflags',
            'integrityfailures' => 'privacy:metadata:modernvideoplayer_progress:integrityfailures',
        ], 'privacy:metadata:modernvideoplayer_progress');

        $items->add_database_table('modernvideoplayer_segments', [
            'progressid' => 'privacy:metadata:modernvideoplayer_segments:progressid',
            'segmentstart' => 'privacy:metadata:modernvideoplayer_segments:segmentstart',
            'segmentend' => 'privacy:metadata:modernvideoplayer_segments:segmentend',
            'watchedseconds' => 'privacy:metadata:modernvideoplayer_segments:watchedseconds',
            'timecreated' => 'privacy:metadata:modernvideoplayer_segments:timecreated',
        ], 'privacy:metadata:modernvideoplayer_segments');

        $items->add_database_table('modernvideoplayer_bookmarks', [
            'modernvideoplayerid' => 'privacy:metadata:modernvideoplayer_bookmarks:modernvideoplayerid',
            'userid' => 'privacy:metadata:modernvideoplayer_bookmarks:userid',
            'position' => 'privacy:metadata:modernvideoplayer_bookmarks:position',
            'label' => 'privacy:metadata:modernvideoplayer_bookmarks:label',
            'timecreated' => 'privacy:metadata:modernvideoplayer_bookmarks:timecreated',
            'timemodified' => 'privacy:metadata:modernvideoplayer_bookmarks:timemodified',
        ], 'privacy:metadata:modernvideoplayer_bookmarks');

        return $items;
    }

    /**
     * Contexts containing user data.
     *
     * @param int $userid user id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $progresssql = "SELECT ctx.id
                          FROM {modernvideoplayer_progress} p
                          JOIN {course_modules} cm ON cm.instance = p.modernvideoplayerid
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                         WHERE p.userid = :userid";
        $contextlist->add_from_sql($progresssql, [
            'modname' => 'modernvideoplayer',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        $bookmarksql = "SELECT ctx.id
                          FROM {modernvideoplayer_bookmarks} b
                          JOIN {course_modules} cm ON cm.instance = b.modernvideoplayerid
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                         WHERE b.userid = :userid";
        $contextlist->add_from_sql($bookmarksql, [
            'modname' => 'modernvideoplayer',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $progresssql = "SELECT p.userid
                          FROM {modernvideoplayer_progress} p
                          JOIN {course_modules} cm ON cm.instance = p.modernvideoplayerid
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                         WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $progresssql, [
            'modname' => 'modernvideoplayer',
            'cmid' => $context->instanceid,
        ]);

        $bookmarksql = "SELECT b.userid
                          FROM {modernvideoplayer_bookmarks} b
                          JOIN {course_modules} cm ON cm.instance = b.modernvideoplayerid
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                         WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $bookmarksql, [
            'modname' => 'modernvideoplayer',
            'cmid' => $context->instanceid,
        ]);
    }

    /**
     * Export approved user data.
     *
     * @param approved_contextlist $contextlist approved contexts
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();
        [$insql, $params] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT ctx.id AS contextid, p.*
                  FROM {modernvideoplayer_progress} p
                  JOIN {course_modules} cm ON cm.instance = p.modernvideoplayerid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id {$insql}
                   AND p.userid = :userid";
        $params += [
            'modname' => 'modernvideoplayer',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $user->id,
        ];

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $context = \context::instance_by_id($record->contextid);
            $contextdata = helper::get_context_data($context, $user);
            $contextdata = (object) array_merge((array) $contextdata, [
                'progress' => (object) [
                    'duration' => $record->duration,
                    'lastposition' => $record->lastposition,
                    'maxverifiedposition' => $record->maxverifiedposition,
                    'totalsecondswatched' => $record->totalsecondswatched,
                    'percentcomplete' => $record->percentcomplete,
                    'completed' => $record->completed,
                    'completiontime' => $record->completiontime ? transform::datetime($record->completiontime) : null,
                    'timecreated' => transform::datetime($record->timecreated),
                    'lastheartbeat' => $record->lastheartbeat ? transform::datetime($record->lastheartbeat) : null,
                    'lastplaybackrate' => $record->lastplaybackrate,
                    'lastvisibility' => $record->lastvisibility,
                    'suspiciousflags' => $record->suspiciousflags,
                    'integrityfailures' => $record->integrityfailures,
                ],
            ]);
            writer::with_context($context)->export_data([], $contextdata);

            $segments = $DB->get_records('modernvideoplayer_segments', ['progressid' => $record->id], 'segmentstart ASC');
            if ($segments) {
                writer::with_context($context)->export_data(['segments'], (object) [
                    'segments' => array_map(function ($segment) {
                        return (object) [
                            'segmentstart' => $segment->segmentstart,
                            'segmentend' => $segment->segmentend,
                            'watchedseconds' => $segment->watchedseconds,
                            'timecreated' => transform::datetime($segment->timecreated),
                        ];
                    }, array_values($segments)),
                ]);
            }

            helper::export_context_files($context, $user);
            writer::with_context($context)->export_area_files([], 'mod_modernvideoplayer', 'video', 0);
            writer::with_context($context)->export_area_files([], 'mod_modernvideoplayer', 'poster', 0);
        }

        // Export bookmarks for each context (independent of progress rows).
        $bookmarksql = "SELECT ctx.id AS contextid, b.*
                          FROM {modernvideoplayer_bookmarks} b
                          JOIN {course_modules} cm ON cm.instance = b.modernvideoplayerid
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                         WHERE ctx.id {$insql}
                           AND b.userid = :userid
                         ORDER BY ctx.id ASC, b.position ASC";
        $bookmarks = $DB->get_records_sql($bookmarksql, $params);
        $grouped = [];
        foreach ($bookmarks as $bookmark) {
            $grouped[$bookmark->contextid][] = (object) [
                'position' => $bookmark->position,
                'label' => $bookmark->label,
                'timecreated' => transform::datetime($bookmark->timecreated),
                'timemodified' => transform::datetime($bookmark->timemodified),
            ];
        }
        foreach ($grouped as $contextid => $items) {
            $context = \context::instance_by_id($contextid);
            writer::with_context($context)->export_data(['bookmarks'], (object) [
                'bookmarks' => $items,
            ]);
        }
    }

    /**
     * Delete all user data in the context.
     *
     * @param \context $context context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('modernvideoplayer', $context->instanceid);
        if (!$cm) {
            return;
        }

        $progressids = $DB->get_fieldset_select('modernvideoplayer_progress', 'id', 'modernvideoplayerid = ?', [$cm->instance]);
        if ($progressids) {
            [$insql, $params] = $DB->get_in_or_equal($progressids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('modernvideoplayer_segments', "progressid {$insql}", $params);
        }
        $DB->delete_records('modernvideoplayer_progress', ['modernvideoplayerid' => $cm->instance]);
        $DB->delete_records('modernvideoplayer_bookmarks', ['modernvideoplayerid' => $cm->instance]);
    }

    /**
     * Delete approved user data.
     *
     * @param approved_contextlist $contextlist approved contexts
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            self::delete_user_data_in_context((int) $contextlist->get_user()->id, $context);
        }
    }

    /**
     * Delete multiple users in one context.
     *
     * @param approved_userlist $userlist approved userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_data_in_context((int) $userid, $userlist->get_context());
        }
    }

    /**
     * Delete one user's data in one context.
     *
     * @param int $userid user id
     * @param \context $context context
     * @return void
     */
    protected static function delete_user_data_in_context(int $userid, \context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('modernvideoplayer', $context->instanceid);
        if (!$cm) {
            return;
        }

        $progress = $DB->get_record('modernvideoplayer_progress', [
            'modernvideoplayerid' => $cm->instance,
            'userid' => $userid,
        ]);
        if ($progress) {
            $DB->delete_records('modernvideoplayer_segments', ['progressid' => $progress->id]);
            $DB->delete_records('modernvideoplayer_progress', ['id' => $progress->id]);
        }

        $DB->delete_records('modernvideoplayer_bookmarks', [
            'modernvideoplayerid' => $cm->instance,
            'userid' => $userid,
        ]);
    }
}
