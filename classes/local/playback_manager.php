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
 * Playback session management for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\local;

use context_module;
use stdClass;
/**
 * Facade around playback tracking and completion logic.
 * @package mod_modernvideoplayer
 */
class playback_manager {
    /** @var progress_repository */
    protected $repository;
    /** @var validation */
    protected $validation;
    /** @var completion_manager */
    protected $completionmanager;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->repository = new progress_repository();
        $this->validation = new validation();
        $this->completionmanager = new completion_manager();
    }

    /**
     * Prepare player state for the current user.
     *
     * @param stdClass $instance activity
     * @param int $userid user id
     * @return stdClass
     */
    public function get_state(stdClass $instance, int $userid): stdClass {
        $progress = $this->repository->get_or_create($instance->id, $userid);
        if (empty($progress->sessiontoken)) {
            $progress->sessiontoken = bin2hex(random_bytes(16));
            $this->repository->save($progress);
        }

        return $progress;
    }

    /**
     * Process a client heartbeat.
     *
     * @param stdClass $course course
     * @param stdClass $cm course module
     * @param stdClass $instance activity
     * @param int $userid user id
     * @param stdClass $payload heartbeat
     * @return stdClass
     */
    public function heartbeat(stdClass $course, stdClass $cm, stdClass $instance, int $userid, stdClass $payload): stdClass {
        $progress = $this->repository->get_or_create($instance->id, $userid);

        if (empty($progress->sessiontoken) || $progress->sessiontoken !== $payload->sessiontoken) {
            if (!empty($progress->sessiontoken) && !empty($progress->lastheartbeat)) {
                $progress->suspiciousflags++;
                $progress->integrityfailures++;
            }
            $progress->sessiontoken = $payload->sessiontoken;
        }

        $result = $this->validation->validate_heartbeat($instance, $progress, $payload);
        $previousposition = (float) $progress->lastposition;

        if ($result->duration > 0) {
            $progress->duration = $result->duration;
        }
        $progress->lastposition = $result->currenttime;
        $progress->maxverifiedposition = $result->maxverifiedposition;
        $progress->lastplaybackrate = $result->playbackrate;
        $progress->lastvisibility = $result->visibility;
        $progress->lastheartbeat = time();
        if ($result->suspicious && !empty($instance->showsuspiciousflags)) {
            $progress->suspiciousflags++;
            $event = \mod_modernvideoplayer\event\suspicious_seek_detected::create([
                'context' => context_module::instance($cm->id),
                'objectid' => $instance->id,
                'relateduserid' => $userid,
            ]);
            $event->trigger();
        }

        if ($result->verifiedadvance > 0) {
            $this->repository->add_segment($progress->id, $previousposition, $result->currenttime);
        }
        if ($result->integrityfailure) {
            $progress->integrityfailures++;
        }
        $progress->totalsecondswatched = $this->repository->get_total_watched_seconds($progress->id);
        $progress->percentcomplete = $progress->duration > 0
            ? round(min(100.0, ($progress->totalsecondswatched / $progress->duration) * 100.0), 2)
            : (float) $progress->percentcomplete;

        // Flush the updated percentcomplete and position fields to the DB *before* triggering
        // Moodle completion. custom_completion::get_state() does a fresh DB query, so if we
        // call update() first the completion class would read the previous heartbeat's stale
        // percentcomplete and return COMPLETION_INCOMPLETE even when the threshold is met.
        $this->repository->save($progress);
        $completed = $this->completionmanager->update($course, $cm, $instance, $progress);
        // Save again to persist any fields completion_manager::update() wrote in-memory
        // (e.g. $progress->completed, $progress->completiontime).
        $this->repository->save($progress);

        $event = \mod_modernvideoplayer\event\progress_updated::create([
            'context' => context_module::instance($cm->id),
            'objectid' => $instance->id,
            'relateduserid' => $userid,
        ]);
        $event->trigger();

        return (object) [
            'status' => 'ok',
            'allowedposition' => $result->allowedposition,
            'maxverifiedposition' => $progress->maxverifiedposition,
            'percentcomplete' => $progress->percentcomplete,
            'completed' => $completed,
            'suspiciousflags' => $progress->suspiciousflags,
            'integrityfailures' => $progress->integrityfailures,
            'sessiontoken' => $progress->sessiontoken,
        ];
    }

    /**
     * Reset all learner progress to zero and return fresh initial state.
     *
     * @param stdClass $course course
     * @param stdClass $cm course module
     * @param stdClass $instance activity
     * @param int $userid user id
     * @return stdClass fresh progress with new session token
     */
    public function reset_progress(stdClass $course, stdClass $cm, stdClass $instance, int $userid): stdClass {
        $progress = $this->repository->reset($instance->id, $userid);

        // Do NOT touch Moodle's completion record here. Completion is sticky:
        // resetting watch progress lets the learner re-watch from the start,
        // but any previously-earned completion tick is preserved. Teachers who
        // need to revoke completion must use Moodle's standard tools.

        return (object) [
            'sessiontoken' => $progress->sessiontoken,
            'lastposition' => 0.0,
            'maxverifiedposition' => 0.0,
            'allowedposition' => (float) $instance->graceseconds,
            'percentcomplete' => 0.0,
            'completed' => false,
        ];
    }
}
