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
 * Reporting helpers for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\local;

use stdClass;
/**
 * Reporting query helper.
 * @package mod_modernvideoplayer
 */
class reporting {
    /**
     * Return report rows and summaries.
     *
     * @param stdClass $instance activity instance
     * @param string $completionfilter filter
     * @param bool $suspiciousonly suspicious only
     * @param string $search free text search
     * @return array
     */
    public function get_report_data(
        stdClass $instance,
        string $completionfilter = 'all',
        bool $suspiciousonly = false,
        string $search = ''
    ): array {
        global $DB;

        $conditions = ['p.modernvideoplayerid = :instanceid'];
        $params = ['instanceid' => $instance->id];

        if ($completionfilter === 'completed') {
            $conditions[] = 'p.completed = :completed';
            $params['completed'] = 1;
        } else if ($completionfilter === 'incomplete') {
            $conditions[] = 'p.completed = :incomplete';
            $params['incomplete'] = 0;
        }

        if ($suspiciousonly) {
            $conditions[] = '(p.suspiciousflags > 0 OR p.integrityfailures > 0)';
        }

        if ($search !== '') {
            $conditions[] = '(' . $DB->sql_like('u.firstname', ':search1', false) .
                ' OR ' . $DB->sql_like('u.lastname', ':search2', false) .
                ' OR ' . $DB->sql_like('u.email', ':search3', false) . ')';
            $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search3'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $where = implode(' AND ', $conditions);
        $userfields = \core_user\fields::for_name()
            ->with_userpic(false)
            ->including('email')
            ->get_sql('u', false, '', '', false)
            ->selects;
        $sql = "SELECT p.*, {$userfields}
                  FROM {modernvideoplayer_progress} p
                  JOIN {user} u ON u.id = p.userid
                 WHERE {$where}
              ORDER BY u.lastname, u.firstname";

        $rows = array_values($DB->get_records_sql($sql, $params));
        $summary = $this->build_summary($rows);

        return [$rows, $summary];
    }

    /**
     * Build summary values.
     *
     * @param array $rows report rows
     * @return array
     */
    protected function build_summary(array $rows): array {
        $count = count($rows);
        $completed = 0;
        $totalsuspicious = 0;
        $totalintegrity = 0;
        $totalcoverage = 0.0;

        foreach ($rows as $row) {
            $completed += (int) $row->completed;
            $totalsuspicious += (int) $row->suspiciousflags;
            $totalintegrity += (int) $row->integrityfailures;
            $totalcoverage += (float) $row->percentcomplete;
        }

        return [
            'totallearners' => $count,
            'completedlearners' => $completed,
            'completionrate' => $count ? round(($completed / $count) * 100, 2) : 0.0,
            'averagecoverage' => $count ? round($totalcoverage / $count, 2) : 0.0,
            'suspiciousflags' => $totalsuspicious,
            'integrityfailures' => $totalintegrity,
        ];
    }
}
