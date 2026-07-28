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
 * Transformer utility to determine the hvp attempt result.
 *
 * @package     logstore_xapi
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace src\transformer\utils;

/**
 * Transformer utility to determine the hvp attempt result.
 *
 * @param array $config The transformer config settings.
 * @param \stdClass $gradeitem The grade item object.
 * @param \stdClass $attemptgrade The attemptgrade object.
 * @return array
 */
function get_hvp_attempt_result(array $config, \stdClass $gradeitem, \stdClass $attemptgrade) {
    $gradesum = floatval(isset($attemptgrade->rawgrade) ? $attemptgrade->rawgrade : 0);
    $minscore = floatval($gradeitem->grademin ?: 0);
    $maxscore = floatval($gradeitem->grademax ?: 0);
    $passscore = floatval($gradeitem->gradepass ?: 0);

    $rawscore = cap_raw_score($gradesum, $minscore, $maxscore);
    $scaledscore = $passscore > 0 ? get_scaled_score($rawscore, $minscore, $maxscore) : 1;
    $success = $gradesum >= $passscore;

    return [
        'score' => [
            'raw' => $rawscore,
            'min' => $minscore,
            'max' => $maxscore,
            'scaled' => $scaledscore,
        ],
        'success' => $success,
    ];
}
