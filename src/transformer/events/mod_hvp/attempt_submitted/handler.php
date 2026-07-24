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
 * Handler for the HVP attempt submitted event.
 *
 * @package     logstore_xapi
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace src\transformer\events\mod_hvp\attempt_submitted;

use src\transformer\utils as utils;
use src\transformer\events\mod_hvp\question_answered as question_answered;

/**
 * Handler for the HVP attempt submitted event.
 *
 * @param array $config The transformer config settings.
 * @param \stdClass $event The event to be transformed.
 * @return array
 */
function handler(array $config, \stdClass $event) {
    $repo = $config['repo'];
    $coursemodule = $repo->read_record_by_id('course_modules', $event->contextinstanceid);
    $hvp = $repo->read_record_by_id('hvp', $coursemodule->instance);

    $questionattempts = $repo->read_records('hvp_xapi_results', [
        'user_id' => $event->userid,
        'content_id' => $hvp->id,
    ]);
    $questionattempts = array_filter($questionattempts, fn ($question) => $question->interaction_type !== 'compound');

    return [
        ...attempt_submitted($config, $event),
        ...array_reduce($questionattempts, function ($result, $questionattempt) use ($config, $event) {
            return [
                ...$result,
                ...question_answered\handler($config, $event, $questionattempt),
            ];
        }, []),
    ];
}
