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
 * Local helpers for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return the module instance using either cm id or instance id.
 *
 * @param int $id course module id
 * @param int $n instance id
 * @return array
 */
function modernvideoplayer_get_course_module_and_instance(int $id = 0, int $n = 0): array {
    global $DB;

    if ($n) {
        $modernvideoplayer = $DB->get_record('modernvideoplayer', ['id' => $n], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(
            'modernvideoplayer',
            $modernvideoplayer->id,
            $modernvideoplayer->course,
            false,
            MUST_EXIST
        );
    } else {
        $cm = get_coursemodule_from_id('modernvideoplayer', $id, 0, false, MUST_EXIST);
        $modernvideoplayer = $DB->get_record('modernvideoplayer', ['id' => $cm->instance], '*', MUST_EXIST);
    }

    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    return [$course, $cm, $modernvideoplayer];
}

/**
 * Get admin defaults as a plain array.
 *
 * @return array
 */
function modernvideoplayer_get_defaults(): array {
    $config = get_config('modernvideoplayer');

    $autoplay = isset($config->defaultautoplay) ? (string) $config->defaultautoplay : 'unmuted';
    if (!in_array($autoplay, ['off', 'muted', 'unmuted'], true)) {
        $autoplay = 'unmuted';
    }

    $defaultcaptionlang = isset($config->defaultcaptionlang) ? (string) $config->defaultcaptionlang : 'en';
    if (!preg_match('/^[a-zA-Z]{2,3}(-[a-zA-Z]{2,4})?$/', $defaultcaptionlang)) {
        $defaultcaptionlang = 'en';
    }

    return [
        'requiredpercent' => isset($config->defaultrequiredpercent) ? (float) $config->defaultrequiredpercent : 95.0,
        'heartbeatinterval' => isset($config->defaultheartbeatinterval) ? (int) $config->defaultheartbeatinterval : 15,
        'graceseconds' => isset($config->defaultgraceseconds) ? (int) $config->defaultgraceseconds : 3,
        'allowplaybackspeed' => isset($config->defaultallowplaybackspeed) ? (int) $config->defaultallowplaybackspeed : 1,
        'maxplaybackspeed' => isset($config->defaultmaxplaybackspeed) ? (float) $config->defaultmaxplaybackspeed : 1.25,
        'allowresume' => isset($config->defaultresumeenabled) ? (int) $config->defaultresumeenabled : 1,
        'allowfullscreen' => isset($config->defaultfullscreenenabled) ? (int) $config->defaultfullscreenenabled : 1,
        'autoplay' => $autoplay,
        'defaultcaptionlang' => $defaultcaptionlang,
        'showprimarynav' => isset($config->defaultshowprimarynav) ? (int) $config->defaultshowprimarynav : 1,
        'showsecondarynav' => isset($config->defaultshowsecondarynav) ? (int) $config->defaultshowsecondarynav : 1,
        'showcourseindex' => isset($config->defaultshowcourseindex) ? (int) $config->defaultshowcourseindex : 1,
        'showrightblocks' => isset($config->defaultshowrightblocks) ? (int) $config->defaultshowrightblocks : 1,
        'titleposition' => isset($config->defaulttitleposition) ? (string) $config->defaulttitleposition : 'left',
        'showcontroltext' => isset($config->defaultshowcontroltext) ? (int) $config->defaultshowcontroltext : 1,
        'showsuspiciousflags' => isset($config->defaultsuspiciouslogging) ? (int) $config->defaultsuspiciouslogging : 1,
    ];
}

/**
 * Get a single stored video file for a module context.
 *
 * @param context_module $context
 * @param string $filearea
 * @return stored_file|null
 */
function modernvideoplayer_get_file(context_module $context, string $filearea): ?stored_file {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_modernvideoplayer', $filearea, 0, 'itemid, filepath, filename', false);
    if (!$files) {
        return null;
    }

    return reset($files);
}

/**
 * Build a public pluginfile URL for a stored file.
 *
 * @param stored_file $file
 * @return moodle_url
 */
function modernvideoplayer_file_url(stored_file $file): moodle_url {
    return moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(),
        false
    );
}

/**
 * Build the caption track list for a module instance.
 *
 * Language is inferred from the filename pattern <name>.<lang>.vtt (e.g. foo.en.vtt,
 * foo.fr-CA.vtt). Files with no match fall back to the instance default language.
 * The track matching the default language is marked as the default <track>.
 *
 * @param context_module $context the module context
 * @param string $defaultlang the instance default caption language code
 * @return array<int, array{src: string, srclang: string, label: string, default: bool}>
 */
function modernvideoplayer_get_caption_tracks(context_module $context, string $defaultlang): array {
    $fs = get_file_storage();
    $files = $fs->get_area_files(
        $context->id,
        'mod_modernvideoplayer',
        'captions',
        0,
        'filename ASC',
        false
    );
    if (!$files) {
        return [];
    }

    $defaultlang = strtolower(trim($defaultlang)) ?: 'en';
    $tracks = [];
    $defaultindex = -1;

    foreach ($files as $file) {
        $name = $file->get_filename();
        // Only accept .vtt files defensively.
        if (!preg_match('/\.vtt$/i', $name)) {
            continue;
        }
        $lang = $defaultlang;
        $label = preg_replace('/\.vtt$/i', '', $name);
        if (preg_match('/[._-]([a-z]{2,3}(?:-[a-z]{2,4})?)\.vtt$/i', $name, $m)) {
            $lang = strtolower($m[1]);
            // Keep the user-friendly label without the language suffix.
            $label = preg_replace('/[._-]' . preg_quote($m[1], '/') . '\.vtt$/i', '', $name);
        }
        // Normalise the BCP-47 region portion to uppercase (en-US not en-us).
        if (strpos($lang, '-') !== false) {
            [$primary, $region] = explode('-', $lang, 2);
            $lang = strtolower($primary) . '-' . strtoupper($region);
        }
        $label = $label !== '' ? $label : $lang;

        $tracks[] = [
            'src' => modernvideoplayer_file_url($file)->out(false),
            'srclang' => $lang,
            'label' => $label,
            'default' => false,
        ];

        if ($defaultindex === -1 && strcasecmp($lang, $defaultlang) === 0) {
            $defaultindex = count($tracks) - 1;
        }
    }

    if ($tracks) {
        $tracks[$defaultindex >= 0 ? $defaultindex : 0]['default'] = true;
    }

    return $tracks;
}

/**
 * Get the chapter track (single WebVTT) for a module instance.
 *
 * @param context_module $context the module context
 * @return array|null ['src' => string, 'label' => string] or null when not uploaded
 */
function modernvideoplayer_get_chapter_track(context_module $context): ?array {
    $file = modernvideoplayer_get_file($context, 'chapters');
    if (!$file) {
        return null;
    }
    $name = $file->get_filename();
    if (!preg_match('/\.vtt$/i', $name)) {
        return null;
    }
    $label = preg_replace('/\.vtt$/i', '', $name);
    return [
        'src' => modernvideoplayer_file_url($file)->out(false),
        'label' => $label !== '' ? $label : $name,
    ];
}
