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
 * Renderer for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Plugin renderer.
 */
class mod_modernvideoplayer_renderer extends plugin_renderer_base {
    /**
     * Render the player page.
     *
     * @param array $context template context
     * @return string
     */
    public function render_player(array $context): string {
        return $this->render_from_template('mod_modernvideoplayer/player', $context);
    }

    /**
     * Render the report page.
     *
     * @param array $context template context
     * @return string
     */
    public function render_report(array $context): string {
        return $this->render_from_template('mod_modernvideoplayer/report', $context);
    }
}
