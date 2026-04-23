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
 * Admin settings for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'modernvideoplayermodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')
    ));

    $settings->add(new admin_setting_configtext(
        'modernvideoplayer/defaultrequiredpercent',
        get_string('defaultrequiredpercent', 'modernvideoplayer'),
        '',
        95,
        PARAM_FLOAT
    ));
    $settings->add(new admin_setting_configtext(
        'modernvideoplayer/defaultheartbeatinterval',
        get_string('defaultheartbeatinterval', 'modernvideoplayer'),
        '',
        15,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'modernvideoplayer/defaultgraceseconds',
        get_string('defaultgraceseconds', 'modernvideoplayer'),
        '',
        3,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultallowplaybackspeed',
        get_string('defaultallowplaybackspeed', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configtext(
        'modernvideoplayer/defaultmaxplaybackspeed',
        get_string('defaultmaxplaybackspeed', 'modernvideoplayer'),
        '',
        1.25,
        PARAM_FLOAT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultresumeenabled',
        get_string('defaultresumeenabled', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultfullscreenenabled',
        get_string('defaultfullscreenenabled', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configselect(
        'modernvideoplayer/defaultautoplay',
        get_string('defaultautoplay', 'modernvideoplayer'),
        '',
        'unmuted',
        [
            'off' => get_string('autoplayoff', 'modernvideoplayer'),
            'muted' => get_string('autoplaymuted', 'modernvideoplayer'),
            'unmuted' => get_string('autoplayunmuted', 'modernvideoplayer'),
        ]
    ));
    $settings->add(new admin_setting_configtext(
        'modernvideoplayer/defaultcaptionlang',
        get_string('defaultdefaultcaptionlang', 'modernvideoplayer'),
        '',
        'en',
        PARAM_ALPHANUMEXT,
        8
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultshowprimarynav',
        get_string('defaultshowprimarynav', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultshowsecondarynav',
        get_string('defaultshowsecondarynav', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultshowcourseindex',
        get_string('defaultshowcourseindex', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultshowrightblocks',
        get_string('defaultshowrightblocks', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configselect(
        'modernvideoplayer/defaulttitleposition',
        get_string('defaulttitleposition', 'modernvideoplayer'),
        '',
        'left',
        [
            'left' => get_string('titlepositionleft', 'modernvideoplayer'),
            'center' => get_string('titlepositioncenter', 'modernvideoplayer'),
            'right' => get_string('titlepositionright', 'modernvideoplayer'),
            'hidden' => get_string('titlepositionhidden', 'modernvideoplayer'),
        ]
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultshowcontroltext',
        get_string('defaultshowcontroltext', 'modernvideoplayer'),
        '',
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'modernvideoplayer/defaultsuspiciouslogging',
        get_string('defaultsuspiciouslogging', 'modernvideoplayer'),
        '',
        1
    ));
}
