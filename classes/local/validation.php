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
 * Server-side playback validation for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\local;

use stdClass;
/**
 * Playback validation logic.
 * @package mod_modernvideoplayer
 */
class validation {
    /**
     * Validate a heartbeat payload against the current progress state.
     *
     * @param stdClass $instance activity config
     * @param stdClass $progress progress row
     * @param stdClass $payload client payload
     * @return stdClass
     */
    public function validate_heartbeat(stdClass $instance, stdClass $progress, stdClass $payload): stdClass {
        $allowedposition = (float) $progress->maxverifiedposition + (int) $instance->graceseconds;
        $currenttime = max(0.0, (float) $payload->currenttime);
        $duration = max(0.0, (float) $payload->duration);
        $playbackrate = (float) $payload->playbackrate;
        $lastposition = (float) $progress->lastposition;
        $visibility = ($payload->visibility === 'hidden') ? 'hidden' : 'visible';
        $suspicious = false;
        $integrityfailure = false;
        $now = time();
        $elapsed = !empty($progress->lastheartbeat) ? max(1, $now - (int) $progress->lastheartbeat) : 0;

        if (!$instance->allowplaybackspeed && abs($playbackrate - 1.0) > 0.001) {
            $suspicious = true;
            $integrityfailure = true;
        }
        if ($playbackrate > (float) $instance->maxplaybackspeed + 0.001) {
            $suspicious = true;
            $integrityfailure = true;
        }
        // Only clamp against duration when the duration is known. A duration of 0 means the client
        // has not yet received video metadata (or has an Infinity/NaN duration), so any position
        // would be incorrectly clamped to 0 and flagged as a false integrity failure.
        if ($duration > 0 && $currenttime > $duration + 1) {
            $currenttime = $duration;
            $suspicious = true;
            $integrityfailure = true;
        }
        if (
            $currenttime > $allowedposition
            && $currenttime > $lastposition
                + (float) $instance->heartbeatinterval
                + (float) $instance->graceseconds
        ) {
            $suspicious = true;
            $integrityfailure = true;
            $currenttime = min($allowedposition, max($lastposition, 0));
        }
        if ($elapsed > 0) {
            // Use the furthest known position as the baseline so a pre-seek heartbeat
            // (currenttime=0 sent before metadata/seek completes) cannot cause a false
            // rejection when the user is actually resuming from maxverifiedposition.
            $plausiblebaseline = max($lastposition, (float) $progress->maxverifiedposition);
            $claimeddelta = max(0.0, $currenttime - $plausiblebaseline);
            $maxplausibleadvance = ($elapsed * max(1.0, min($playbackrate, (float) $instance->maxplaybackspeed))) +
                (float) $instance->graceseconds;
            if ($claimeddelta > $maxplausibleadvance) {
                $suspicious = true;
                $integrityfailure = true;
                $currenttime = $plausiblebaseline + $maxplausibleadvance;
            }
        }
        if ($visibility === 'hidden' && !empty($payload->playing)) {
            $suspicious = true;
        }

        $verifiedadvance = 0.0;
        if ($currenttime >= $lastposition) {
            $verifiedadvance = $currenttime - $lastposition;
        } else if (!$instance->allowrewind) {
            $suspicious = true;
            $integrityfailure = true;
            $currenttime = $lastposition;
        }

        $maxverifiedposition = max((float) $progress->maxverifiedposition, $currenttime);

        return (object) [
            'currenttime' => round($currenttime, 2),
            'duration' => round($duration, 2),
            'playbackrate' => $playbackrate,
            'visibility' => $visibility,
            'suspicious' => $suspicious,
            'integrityfailure' => $integrityfailure,
            'verifiedadvance' => round($verifiedadvance, 2),
            'maxverifiedposition' => round($maxverifiedposition, 2),
            'allowedposition' => round($maxverifiedposition + (int) $instance->graceseconds, 2),
        ];
    }
}
