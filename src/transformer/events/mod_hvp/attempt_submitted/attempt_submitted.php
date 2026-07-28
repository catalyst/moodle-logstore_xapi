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
 * Transformer for hvp attempt event.
 *
 * @package     logstore_xapi
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace src\transformer\events\mod_hvp\attempt_submitted;

use src\transformer\utils as utils;

/**
 * Transformer for hvp attempt event.
 *
 * @param array $config The transformer config settings.
 * @param \stdClass $event The event to be transformed.
 * @return array
 */
function attempt_submitted(array $config, \stdClass $event) {
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->userid);
    $course = $repo->read_record_by_id('course', $event->courseid);
    $lang = utils\get_course_lang($course);
    $coursemodule = $repo->read_record_by_id('course_modules', $event->contextinstanceid);
    $hvp = $repo->read_record_by_id('hvp', $coursemodule->instance);
    $gradeitem = $repo->read_record('grade_items', [
        'itemmodule' => 'hvp',
        'iteminstance' => $hvp->id,
    ]);
    $grade = $repo->read_record('grade_grades', [
        'itemid' => $gradeitem->id,
        'userid' => $user->id,
    ]);

    $gradepass = $gradeitem->gradepass;
    $finalgrade = $grade->finalgrade;

    $verb = $finalgrade >= $gradepass ?
        utils\get_verb('passed', $config, $lang) :
        utils\get_verb('failed', $config, $lang);

    return [[
        'actor' => utils\get_user($config, $user),
        'verb' => $verb,
        'object' => utils\get_activity\course_module(
            $config,
            $course,
            $event->contextinstanceid
        ),
        'result' => utils\get_hvp_attempt_result($config, $gradeitem, $grade),
        'context' => [
            'language' => $lang,
            'extensions' => utils\extensions\base($config, $event, $course),
            'contextActivities' => [
                'parent' => utils\context_activities\get_parent(
                    $config,
                    $event->contextinstanceid
                ),
                'category' => [
                    utils\get_activity\site($config),
                ],
            ],
        ],
    ]];
}
