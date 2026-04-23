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
 * Main learner view.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
$standardview = optional_param('standardview', 0, PARAM_BOOL);
$focusmode = !$standardview;

[$course, $cm, $instance] = modernvideoplayer_get_course_module_and_instance($id, $n);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/modernvideoplayer:view', $context);

modernvideoplayer_view($instance, $course, $cm, $context);

$pageurlparams = ['id' => $cm->id];
if ($standardview) {
    $pageurlparams['standardview'] = 1;
}

$PAGE->set_url('/mod/modernvideoplayer/view.php', $pageurlparams);
$PAGE->set_context($context);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$showprimarynav = !empty($instance->showprimarynav);
$showsecondarynav = !empty($instance->showsecondarynav);
$showcourseindex = !empty($instance->showcourseindex);
$showrightblocks = !empty($instance->showrightblocks);

// When editing mode is active, site admins and course managers always see all
// navigation elements so they can navigate the course normally.
if ($PAGE->user_is_editing() && has_capability('moodle/course:update', context_course::instance($course->id))) {
    $showprimarynav   = true;
    $showsecondarynav = true;
    $showcourseindex  = true;
    $showrightblocks  = true;
}
$needsincourselayout = $focusmode && ($showprimarynav || $showsecondarynav || $showcourseindex || $showrightblocks);
$PAGE->set_pagelayout(($focusmode && !$needsincourselayout) ? 'embedded' : 'incourse');
$PAGE->add_body_class($focusmode ? 'modernvideoplayer-focus-mode' : 'modernvideoplayer-standard-mode');
$PAGE->add_body_class($showprimarynav ? 'modernvideoplayer-show-primarynav' : 'modernvideoplayer-hide-primarynav');
$PAGE->add_body_class($showsecondarynav ? 'modernvideoplayer-show-secondarynav' : 'modernvideoplayer-hide-secondarynav');
$PAGE->add_body_class($showcourseindex ? 'modernvideoplayer-show-courseindex' : 'modernvideoplayer-hide-courseindex');
$PAGE->add_body_class($showrightblocks ? 'modernvideoplayer-show-rightblocks' : 'modernvideoplayer-hide-rightblocks');
$PAGE->requires->css(new moodle_url('/mod/modernvideoplayer/styles.css'));

$renderer = $PAGE->get_renderer('mod_modernvideoplayer');
$videofile = modernvideoplayer_get_file($context, 'video');
$posterfile = modernvideoplayer_get_file($context, 'poster');
$backurl = new moodle_url('/course/view.php', ['id' => $course->id]);
$titleposition = $instance->titleposition ?? 'left';
$validtitlepositions = ['left', 'center', 'right', 'hidden'];
if (!in_array($titleposition, $validtitlepositions, true)) {
    $titleposition = 'left';
}

$enforcefocus = !empty($instance->enforcefocus);
$allowpip = !$enforcefocus && !empty($instance->allowpip);
$allowtranscriptdownload = !empty($instance->allowtranscriptdownload);

$playercontext = [
    'cmid' => $cm->id,
    'name' => format_string($instance->name),
    'intro' => $focusmode ? '' : format_module_intro('modernvideoplayer', $instance, $cm->id),
    'hasvideo' => (bool) $videofile,
    'videourl' => $videofile ? modernvideoplayer_file_url($videofile)->out(false) : '',
    'posterurl' => $posterfile ? modernvideoplayer_file_url($posterfile)->out(false) : '',
    'allowfullscreen' => (bool) $instance->allowfullscreen,
    'showcontroltext' => !empty($instance->showcontroltext),
    'showtitle' => $titleposition !== 'hidden',
    'titleleft' => $titleposition === 'left',
    'titlecenter' => $titleposition === 'center',
    'titleright' => $titleposition === 'right',
    'focusmode' => $focusmode,
    'enforcefocus' => $enforcefocus,
    'allowpip' => $allowpip,
    'allowtranscriptdownload' => $allowtranscriptdownload,
    'backurl' => $backurl->out(false),
    'backlabel' => get_string('back'),
    'reporturl' => !$focusmode && has_capability('mod/modernvideoplayer:viewreports', $context)
        ? (new moodle_url('/mod/modernvideoplayer/report.php', ['id' => $cm->id]))->out(false)
        : '',
];

$defaultcaptionlang = (string) ($instance->defaultcaptionlang ?? 'en');
if (!preg_match('/^[a-zA-Z]{2,3}(-[a-zA-Z]{2,4})?$/', $defaultcaptionlang)) {
    $defaultcaptionlang = 'en';
}
$captiontracks = modernvideoplayer_get_caption_tracks($context, $defaultcaptionlang);
$playercontext['hascaptions'] = !empty($captiontracks);
$playercontext['captions'] = $captiontracks;

$chaptertrack = modernvideoplayer_get_chapter_track($context);
$playercontext['haschapters'] = $chaptertrack !== null;
$playercontext['chapterurl'] = $chaptertrack['src'] ?? '';
$playercontext['chapterlabel'] = $chaptertrack['label'] ?? '';
$playercontext['defaultcaptionlang'] = $defaultcaptionlang;

$autoplaymode = (string) ($instance->autoplay ?? 'unmuted');
if (!in_array($autoplaymode, ['off', 'muted', 'unmuted'], true)) {
    $autoplaymode = 'unmuted';
}

$jsconfig = [
    'cmid' => $cm->id,
    'autoplay' => $autoplaymode,
    'hascaptions' => !empty($captiontracks),
    'haschapters' => $chaptertrack !== null,
    'defaultcaptionlang' => $defaultcaptionlang,
    'enforcefocus' => $enforcefocus,
    'allowpip' => $allowpip,
    'allowtranscriptdownload' => $allowtranscriptdownload,
    'transcriptfilename' => clean_filename(format_string($instance->name)) . '-'
        . get_string('transcriptdownloadfilename', 'modernvideoplayer') . '.txt',
    'strings' => [
        'seekblocked' => get_string('seekblocked', 'modernvideoplayer'),
        'speedrestricted' => get_string('speedrestricted', 'modernvideoplayer'),
        'progressunavailable' => get_string('progressunavailable', 'modernvideoplayer'),
        'play' => get_string('play', 'modernvideoplayer'),
        'pause' => get_string('pause', 'modernvideoplayer'),
        'mute' => get_string('mute', 'modernvideoplayer'),
        'unmute' => get_string('unmute', 'modernvideoplayer'),
        'resumeplayback' => get_string('resumeplayback', 'modernvideoplayer'),
        'resumeplaybackfrom' => get_string('resumeplaybackfrom', 'modernvideoplayer', '__TIME__'),
        'startfrombeginning' => get_string('startfrombeginning', 'modernvideoplayer'),
        'resumepromptheading' => get_string('resumepromptheading', 'modernvideoplayer'),
        'resumepromptbody' => get_string('resumepromptbody', 'modernvideoplayer'),
        'captionsoff' => get_string('captionsoff', 'modernvideoplayer'),
        'captionson' => get_string('captionson', 'modernvideoplayer', '__LABEL__'),
        'togglecaptions' => get_string('togglecaptions', 'modernvideoplayer'),
        'toggletranscript' => get_string('toggletranscript', 'modernvideoplayer'),
        'transcript' => get_string('transcript', 'modernvideoplayer'),
        'transcriptloading' => get_string('transcriptloading', 'modernvideoplayer'),
        'transcriptunavailable' => get_string('transcriptunavailable', 'modernvideoplayer'),
        'transcriptjumpto' => get_string('transcriptjumpto', 'modernvideoplayer', '__TIME__'),
        'chapters' => get_string('chapters', 'modernvideoplayer'),
        'togglechapters' => get_string('togglechapters', 'modernvideoplayer'),
        'chaptersunavailable' => get_string('chaptersunavailable', 'modernvideoplayer'),
        'chapterjumpto' => get_string('chapterjumpto', 'modernvideoplayer', '__TIME__'),
        'currentchapter' => get_string('currentchapter', 'modernvideoplayer', '__LABEL__'),
        'playbackspeed' => get_string('playbackspeed', 'modernvideoplayer'),
        'speednormal' => get_string('speednormal', 'modernvideoplayer'),
        'shortcutshelp' => get_string('shortcutshelp', 'modernvideoplayer'),
        'pip' => get_string('pip', 'modernvideoplayer'),
        'pipdisabled' => get_string('pipdisabled', 'modernvideoplayer'),
        'transcriptdownload' => get_string('transcriptdownload', 'modernvideoplayer'),
        'focuspausedhidden' => get_string('focuspausedhidden', 'modernvideoplayer'),
    ],
];

$PAGE->requires->js_call_amd('mod_modernvideoplayer/player', 'init', [(int) $cm->id]);

echo $OUTPUT->header();
if (!$videofile) {
    echo $OUTPUT->notification(get_string('novideo', 'modernvideoplayer'));
} else {
    echo $renderer->render_player($playercontext);
    // Emit the player config (strings + flags) as a JSON blob for the AMD module
    // to read. Passing it directly via js_call_amd exceeds the 1024-char arg limit.
    $configjson = json_encode($jsconfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo html_writer::tag(
        'script',
        $configjson,
        [
            'type' => 'application/json',
            'id' => 'mod_modernvideoplayer-config-' . (int) $cm->id,
            'data-for' => 'mod_modernvideoplayer-config',
        ]
    );
}
echo $OUTPUT->footer();
