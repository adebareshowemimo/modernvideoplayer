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
 * Activity settings form.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Module settings form.
 */
class mod_modernvideoplayer_mod_form extends moodleform_mod {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $defaults = modernvideoplayer_get_defaults();
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 48]);
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'modernvideoplayer'));
        $mform->addElement('filemanager', 'video', get_string('video', 'modernvideoplayer'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['video'],
        ]);
        $mform->addRule('video', null, 'required');

        $mform->addElement('filemanager', 'posterimage', get_string('posterimage', 'modernvideoplayer'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);

        $mform->addElement('header', 'captionssettings', get_string('captionssettings', 'modernvideoplayer'));
        $mform->addElement('filemanager', 'captions', get_string('captions', 'modernvideoplayer'), null, [
            'subdirs' => 0,
            'maxfiles' => 10,
            'accepted_types' => ['.vtt'],
        ]);
        $mform->addHelpButton('captions', 'captions', 'modernvideoplayer');
        $mform->addElement('text', 'defaultcaptionlang', get_string('defaultcaptionlang', 'modernvideoplayer'), ['size' => 8]);
        $mform->setType('defaultcaptionlang', PARAM_ALPHANUMEXT);
        $mform->setDefault('defaultcaptionlang', $defaults['defaultcaptionlang']);
        $mform->addHelpButton('defaultcaptionlang', 'defaultcaptionlang', 'modernvideoplayer');

        $mform->addElement('filemanager', 'chapters', get_string('chapters', 'modernvideoplayer'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.vtt'],
        ]);
        $mform->addHelpButton('chapters', 'chapters', 'modernvideoplayer');

        $mform->addElement('header', 'playbacksettings', get_string('playbacksettings', 'modernvideoplayer'));
        $mform->addElement('advcheckbox', 'allowresume', get_string('resumeenabled', 'modernvideoplayer'));
        $mform->setDefault('allowresume', $defaults['allowresume']);
        $mform->addElement('advcheckbox', 'allowrewind', get_string('allowrewind', 'modernvideoplayer'));
        $mform->setDefault('allowrewind', 1);
        $mform->addElement('advcheckbox', 'allowfullscreen', get_string('allowfullscreen', 'modernvideoplayer'));
        $mform->setDefault('allowfullscreen', $defaults['allowfullscreen']);
        $mform->addElement('select', 'autoplay', get_string('autoplay', 'modernvideoplayer'), [
            'off' => get_string('autoplayoff', 'modernvideoplayer'),
            'muted' => get_string('autoplaymuted', 'modernvideoplayer'),
            'unmuted' => get_string('autoplayunmuted', 'modernvideoplayer'),
        ]);
        $mform->setDefault('autoplay', $defaults['autoplay']);
        $mform->addHelpButton('autoplay', 'autoplay', 'modernvideoplayer');
        $mform->addElement('select', 'titleposition', get_string('titleposition', 'modernvideoplayer'), [
            'left' => get_string('titlepositionleft', 'modernvideoplayer'),
            'center' => get_string('titlepositioncenter', 'modernvideoplayer'),
            'right' => get_string('titlepositionright', 'modernvideoplayer'),
            'hidden' => get_string('titlepositionhidden', 'modernvideoplayer'),
        ]);
        $mform->setDefault('titleposition', $defaults['titleposition']);
        $mform->addElement('advcheckbox', 'showcontroltext', get_string('showcontroltext', 'modernvideoplayer'));
        $mform->setDefault('showcontroltext', $defaults['showcontroltext']);
        $mform->addElement('advcheckbox', 'allowplaybackspeed', get_string('allowplaybackspeed', 'modernvideoplayer'));
        $mform->setDefault('allowplaybackspeed', $defaults['allowplaybackspeed']);
        $mform->addElement('text', 'maxplaybackspeed', get_string('maxplaybackspeed', 'modernvideoplayer'));
        $mform->setType('maxplaybackspeed', PARAM_FLOAT);
        $mform->setDefault('maxplaybackspeed', $defaults['maxplaybackspeed']);

        $mform->addElement('header', 'enforcementsettings', get_string('enforcementsettings', 'modernvideoplayer'));
        $mform->addElement('text', 'graceseconds', get_string('graceseconds', 'modernvideoplayer'));
        $mform->setType('graceseconds', PARAM_INT);
        $mform->setDefault('graceseconds', $defaults['graceseconds']);

        $mform->addElement('text', 'heartbeatinterval', get_string('heartbeatinterval', 'modernvideoplayer'));
        $mform->setType('heartbeatinterval', PARAM_INT);
        $mform->setDefault('heartbeatinterval', $defaults['heartbeatinterval']);

        $mform->addElement('advcheckbox', 'forceservervalidation', get_string('forceservervalidation', 'modernvideoplayer'));
        $mform->setDefault('forceservervalidation', 1);
        $mform->addElement('advcheckbox', 'showsuspiciousflags', get_string('showsuspiciousflags', 'modernvideoplayer'));
        $mform->setDefault('showsuspiciousflags', $defaults['showsuspiciousflags']);

        $mform->addElement('advcheckbox', 'enforcefocus', get_string('enforcefocus', 'modernvideoplayer'));
        $mform->setDefault('enforcefocus', $defaults['enforcefocus']);
        $mform->addHelpButton('enforcefocus', 'enforcefocus', 'modernvideoplayer');
        $mform->addElement('advcheckbox', 'allowpip', get_string('allowpip', 'modernvideoplayer'));
        $mform->setDefault('allowpip', $defaults['allowpip']);
        $mform->addHelpButton('allowpip', 'allowpip', 'modernvideoplayer');
        $mform->disabledIf('allowpip', 'enforcefocus', 'checked');
        $mform->addElement('advcheckbox', 'allowtranscriptdownload', get_string('allowtranscriptdownload', 'modernvideoplayer'));
        $mform->setDefault('allowtranscriptdownload', $defaults['allowtranscriptdownload']);
        $mform->addHelpButton('allowtranscriptdownload', 'allowtranscriptdownload', 'modernvideoplayer');

        $mform->addElement('header', 'focusmodesettings', get_string('focusmodesettings', 'modernvideoplayer'));
        $mform->addElement('advcheckbox', 'showprimarynav', get_string('showprimarynav', 'modernvideoplayer'));
        $mform->setDefault('showprimarynav', $defaults['showprimarynav']);
        $mform->addHelpButton('showprimarynav', 'showprimarynav', 'modernvideoplayer');
        $mform->addElement('advcheckbox', 'showsecondarynav', get_string('showsecondarynav', 'modernvideoplayer'));
        $mform->setDefault('showsecondarynav', $defaults['showsecondarynav']);
        $mform->addHelpButton('showsecondarynav', 'showsecondarynav', 'modernvideoplayer');
        $mform->addElement('advcheckbox', 'showcourseindex', get_string('showcourseindex', 'modernvideoplayer'));
        $mform->setDefault('showcourseindex', $defaults['showcourseindex']);
        $mform->addHelpButton('showcourseindex', 'showcourseindex', 'modernvideoplayer');
        $mform->addElement('advcheckbox', 'showrightblocks', get_string('showrightblocks', 'modernvideoplayer'));
        $mform->setDefault('showrightblocks', $defaults['showrightblocks']);
        $mform->addHelpButton('showrightblocks', 'showrightblocks', 'modernvideoplayer');

        $mform->addElement('header', 'modstandardgrade', get_string('gradenoun'));
        $mform->addElement('modgrade', 'grade', get_string('grademax', 'grades'), ['gradeexisting' => true]);
        $mform->setDefault('grade', 100);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepare draft file areas.
     *
     * @param array $defaultvalues defaults
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if (!empty($this->current->instance)) {
            $draftid = file_get_submitted_draft_itemid('video');
            file_prepare_draft_area($draftid, $this->context->id, 'mod_modernvideoplayer', 'video', 0, ['subdirs' => 0]);
            $defaultvalues['video'] = $draftid;

            $posterid = file_get_submitted_draft_itemid('posterimage');
            file_prepare_draft_area($posterid, $this->context->id, 'mod_modernvideoplayer', 'poster', 0, ['subdirs' => 0]);
            $defaultvalues['posterimage'] = $posterid;

            $captionsid = file_get_submitted_draft_itemid('captions');
            file_prepare_draft_area($captionsid, $this->context->id, 'mod_modernvideoplayer', 'captions', 0, ['subdirs' => 0]);
            $defaultvalues['captions'] = $captionsid;

            $chaptersid = file_get_submitted_draft_itemid('chapters');
            file_prepare_draft_area($chaptersid, $this->context->id, 'mod_modernvideoplayer', 'chapters', 0, ['subdirs' => 0]);
            $defaultvalues['chapters'] = $chaptersid;
        }

        $suffix = $this->get_suffix();
        $enabledpercent = !empty($defaultvalues['requiredpercent']);
        $defaultvalues['completionvideopercentenabled' . $suffix] = $enabledpercent ? 1 : 0;
        $defaultvalues['completionvideopercent' . $suffix] = $enabledpercent ? $defaultvalues['requiredpercent'] : 100;
        $defaultvalues['completionvideoend' . $suffix] = !empty($defaultvalues['strictendvalidation']) ||
            ((int) ($defaultvalues['completionmode'] ?? 0) === 1) ? 1 : 0;
    }

    /**
     * Add custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $group = [];
        $enabledel = 'completionvideopercentenabled' . $suffix;
        $percentel = 'completionvideopercent' . $suffix;
        $group[] = $mform->createElement('checkbox', $enabledel, '', get_string('completionvideopercent', 'modernvideoplayer'));
        $group[] = $mform->createElement('text', $percentel, '', ['size' => 4]);
        $mform->setType($percentel, PARAM_FLOAT);
        $groupel = 'completionvideopercentgroup' . $suffix;
        $mform->addGroup($group, $groupel, '', ' ', false);
        $mform->hideIf($percentel, $enabledel, 'notchecked');

        $endel = 'completionvideoend' . $suffix;
        $mform->addElement('advcheckbox', $endel, '', get_string('completionvideoend', 'modernvideoplayer'));

        return [$groupel, $endel];
    }

    /**
     * Determine whether a completion rule is enabled.
     *
     * @param array $data form data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        $suffix = $this->get_suffix();
        return (!empty($data['completionvideopercentenabled' . $suffix]) &&
                !empty($data['completionvideopercent' . $suffix])) ||
            !empty($data['completionvideoend' . $suffix]);
    }

    /**
     * Modify completion settings after data is fetched.
     *
     * @param stdClass $data form data
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix};
            $autocompletion = !empty($completion) && (int) $completion === COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{'completionvideopercentenabled' . $suffix}) || !$autocompletion) {
                $data->{'completionvideopercent' . $suffix} = 0;
            }
            if (!$autocompletion) {
                $data->{'completionvideoend' . $suffix} = 0;
            }
        }
    }

    /**
     * Validate settings.
     *
     * @param array $data form data
     * @param array $files files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $suffix = $this->get_suffix();
        if (!empty($data['completionvideopercentenabled' . $suffix])) {
            $rulevalue = (float) ($data['completionvideopercent' . $suffix] ?? 0);
            if ($rulevalue <= 0 || $rulevalue > 100) {
                $errors['completionvideopercentgroup' . $suffix] = get_string('invaliddata', 'error');
            }
        }
        if ((int) $data['heartbeatinterval'] < 5) {
            $errors['heartbeatinterval'] = get_string('invaliddata', 'error');
        }
        if ((float) $data['maxplaybackspeed'] < 0.5 || (float) $data['maxplaybackspeed'] > 4) {
            $errors['maxplaybackspeed'] = get_string('invaliddata', 'error');
        }
        if (!in_array($data['autoplay'] ?? '', ['off', 'muted', 'unmuted'], true)) {
            $errors['autoplay'] = get_string('invaliddata', 'error');
        }
        $lang = trim((string) ($data['defaultcaptionlang'] ?? ''));
        if ($lang !== '' && !preg_match('/^[a-zA-Z]{2,3}(-[a-zA-Z]{2,4})?$/', $lang)) {
            $errors['defaultcaptionlang'] = get_string('invaliddata', 'error');
        }

        return $errors;
    }
}
