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
 * Library callbacks for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

/**
 * Feature support callback.
 *
 * @param string $feature feature name
 * @return mixed
 */
function modernvideoplayer_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        default:
            return null;
    }
}

/**
 * Add instance callback.
 *
 * @param stdClass $data form data
 * @param mod_modernvideoplayer_mod_form|null $mform form object
 * @return int
 */
function modernvideoplayer_add_instance($data, $mform = null): int {
    global $DB;

    modernvideoplayer_normalise_completion_settings($data);
    $time = time();
    $data->videoitemid = 0;
    $data->posteritemid = 0;
    $data->timecreated = $time;
    $data->timemodified = $time;

    $data->id = $DB->insert_record('modernvideoplayer', $data);

    $context = context_module::instance($data->coursemodule);
    modernvideoplayer_save_files($data, $context);
    modernvideoplayer_update_completion_event($data->coursemodule, $data);

    return $data->id;
}

/**
 * Update instance callback.
 *
 * @param stdClass $data form data
 * @param mod_modernvideoplayer_mod_form|null $mform form object
 * @return bool
 */
function modernvideoplayer_update_instance($data, $mform = null): bool {
    global $DB;

    modernvideoplayer_normalise_completion_settings($data);
    $data->id = $data->instance;
    $data->timemodified = time();
    $data->videoitemid = 0;
    $data->posteritemid = 0;

    $DB->update_record('modernvideoplayer', $data);

    $context = context_module::instance($data->coursemodule);
    modernvideoplayer_save_files($data, $context);
    modernvideoplayer_update_completion_event($data->coursemodule, $data);

    return true;
}

/**
 * Delete instance callback.
 *
 * @param int $id instance id
 * @return bool
 */
function modernvideoplayer_delete_instance($id): bool {
    global $DB;

    $instance = $DB->get_record('modernvideoplayer', ['id' => $id]);
    if (!$instance) {
        return false;
    }

    if ($cm = get_coursemodule_from_instance('modernvideoplayer', $id, $instance->course, false)) {
        \core_completion\api::update_completion_date_event($cm->id, 'modernvideoplayer', $id, null);
    }

    $progressids = $DB->get_fieldset_select('modernvideoplayer_progress', 'id', 'modernvideoplayerid = ?', [$id]);
    if ($progressids) {
        [$insql, $params] = $DB->get_in_or_equal($progressids, SQL_PARAMS_QM);
        $DB->delete_records_select('modernvideoplayer_segments', "progressid $insql", $params);
    }
    $DB->delete_records('modernvideoplayer_progress', ['modernvideoplayerid' => $id]);
    $DB->delete_records('modernvideoplayer', ['id' => $id]);

    return true;
}

/**
 * Save module file areas.
 *
 * @param stdClass $data form data
 * @param context_module $context
 * @return void
 */
function modernvideoplayer_save_files(stdClass $data, context_module $context): void {
    file_save_draft_area_files(
        $data->video,
        $context->id,
        'mod_modernvideoplayer',
        'video',
        0,
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    file_save_draft_area_files(
        $data->posterimage,
        $context->id,
        'mod_modernvideoplayer',
        'poster',
        0,
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    if (!empty($data->captions)) {
        file_save_draft_area_files(
            $data->captions,
            $context->id,
            'mod_modernvideoplayer',
            'captions',
            0,
            ['subdirs' => 0, 'maxfiles' => 10]
        );
    }

    if (!empty($data->chapters)) {
        file_save_draft_area_files(
            $data->chapters,
            $context->id,
            'mod_modernvideoplayer',
            'chapters',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }
}

/**
 * Module viewed callback.
 *
 * @param stdClass $instance
 * @param stdClass $course
 * @param stdClass $cm
 * @param context_module $context
 * @return void
 */
function modernvideoplayer_view(stdClass $instance, stdClass $course, stdClass $cm, context_module $context): void {
    $event = \mod_modernvideoplayer\event\course_module_viewed::create([
        'context' => $context,
        'objectid' => $instance->id,
    ]);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('modernvideoplayer', $instance);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Participation report view actions.
 *
 * @return array
 */
function modernvideoplayer_get_view_actions(): array {
    return ['view', 'view all', 'report'];
}

/**
 * Participation report post actions.
 *
 * @return array
 */
function modernvideoplayer_get_post_actions(): array {
    return ['progress', 'complete'];
}

/**
 * Get course module display information.
 *
 * @param stdClass $coursemodule course module
 * @return cached_cm_info|null
 */
function modernvideoplayer_get_coursemodule_info($coursemodule): ?cached_cm_info {
    global $DB;

    $instance = $DB->get_record(
        'modernvideoplayer',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat, requiredpercent, strictendvalidation'
    );
    if (!$instance) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('modernvideoplayer', $instance, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionvideopercent'] = $instance->requiredpercent;
        $info->customdata['customcompletionrules']['completionvideoend'] = !empty($instance->strictendvalidation) ? 1 : 0;
    }

    return $info;
}

/**
 * Describe active completion rules.
 *
 * @param cm_info|stdClass $cm course module
 * @return array
 */
function mod_modernvideoplayer_get_completion_active_rule_descriptions($cm): array {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $value) {
        switch ($key) {
            case 'completionvideopercent':
                if (!empty($value)) {
                    $descriptions[] = get_string('completionvideopercentdesc', 'modernvideoplayer', $value);
                }
                break;
            case 'completionvideoend':
                if (!empty($value)) {
                    $descriptions[] = get_string('completionvideoenddesc', 'modernvideoplayer');
                }
                break;
        }
    }

    return $descriptions;
}

/**
 * Serve module files.
 *
 * @param stdClass $course course record
 * @param stdClass $cm course module
 * @param context_module $context context
 * @param string $filearea file area
 * @param array $args path args
 * @param bool $forcedownload forcedownload
 * @param array $options options
 * @return bool
 */
function modernvideoplayer_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/modernvideoplayer:view', $context)) {
        return false;
    }

    if (!in_array($filearea, ['video', 'poster', 'captions', 'chapters'], true)) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/mod_modernvideoplayer/{$filearea}/{$relativepath}";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Resolve pluginfile path details for privacy/export integrations.
 *
 * @param string $filearea file area
 * @param array $args pluginfile args
 * @return array
 */
function mod_modernvideoplayer_get_path_from_pluginfile(string $filearea, array $args): array {
    $itemid = 0;
    if (!empty($args) && is_numeric($args[0])) {
        $itemid = (int) array_shift($args);
    }

    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    return [
        'itemid' => $itemid,
        'filepath' => $filepath,
    ];
}

/**
 * File areas available in the module.
 *
 * @param stdClass $course course
 * @param stdClass $cm course module
 * @param context_module $context context
 * @return array
 */
function modernvideoplayer_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('video', 'modernvideoplayer'),
        'poster' => get_string('posterimage', 'modernvideoplayer'),
    ];
}

/**
 * Export activity contents for backup/mobile integrations.
 *
 * @param stdClass $cm course module
 * @param string $baseurl pluginfile base url
 * @return array
 */
function modernvideoplayer_export_contents($cm, $baseurl): array {
    global $CFG;

    $context = context_module::instance($cm->id);
    $contents = [];
    foreach (['video', 'poster'] as $filearea) {
        $file = modernvideoplayer_get_file($context, $filearea);
        if (!$file) {
            continue;
        }

        $contents[] = [
            'type' => 'file',
            'filename' => $file->get_filename(),
            'filepath' => $file->get_filepath(),
            'filesize' => $file->get_filesize(),
            'fileurl' => file_encode_url(
                "{$CFG->wwwroot}/{$baseurl}",
                "/{$context->id}/mod_modernvideoplayer/{$filearea}/0{$file->get_filepath()}{$file->get_filename()}",
                true
            ),
            'timecreated' => $file->get_timecreated(),
            'timemodified' => $file->get_timemodified(),
            'mimetype' => $file->get_mimetype(),
        ];
    }

    return $contents;
}

/**
 * Update the completion expected event from the standard completion settings.
 *
 * @param int $cmid course module id
 * @param stdClass $data instance data
 * @return void
 */
function modernvideoplayer_update_completion_event(int $cmid, stdClass $data): void {
    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'modernvideoplayer', $data->id, $completiontimeexpected);
}

/**
 * Normalise completion-related settings coming from the completion form.
 *
 * @param stdClass $data form data
 * @return void
 */
function modernvideoplayer_normalise_completion_settings(stdClass $data): void {
    $percentenabled = !empty($data->completionvideopercentenabled);
    $completionpercent = $percentenabled ? (float) ($data->completionvideopercent ?? 0) : 0.0;
    $requireend = !empty($data->completionvideoend);
    $automatic = isset($data->completion) && (int) $data->completion === COMPLETION_TRACKING_AUTOMATIC;

    if ($automatic && ($percentenabled || $requireend)) {
        $data->requiredpercent = $percentenabled ? $completionpercent : 100.0;
        $data->strictendvalidation = $requireend ? 1 : 0;
        $data->completionmode = $requireend ? 1 : 0;
    } else if (!$automatic) {
        $data->requiredpercent    = 0.0;
        $data->strictendvalidation = 0;
        $data->completionmode     = 0;
    }
}
