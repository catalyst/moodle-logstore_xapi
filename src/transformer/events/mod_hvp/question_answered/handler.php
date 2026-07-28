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
 * HVP handler for the question answered event.
 *
 * @package     logstore_xapi
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace src\transformer\events\mod_hvp\question_answered;

use src\transformer\utils as utils;

/**
 * Generic handler for hvp question answered event.
 *
 * @param array $config The transformer config settings.
 * @param \stdClass $event The event to be transformed.
 * @param \stdClass $questionattempt The questionattempt object.
 * @return array
 */
function handler(array $config, \stdClass $event, \stdClass $questionattempt) {
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->userid);
    $course = $repo->read_record_by_id('course', $event->courseid);
    $attempturl = $config['app_url'] . "/mod/hvp/review.php?id={$questionattempt->content_id}&user={$questionattempt->user_id}";
    $lang = utils\get_course_lang($course);

    $questiontype = $questionattempt->interaction_type;
    $score = $questionattempt->raw_score;
    $maxscore = $questionattempt->max_score;

    $object = [
        ...utils\get_activity\base(),
        'id' => $attempturl,
        'definition' => [
            'type' => 'http://adlnet.gov/expapi/activities/cmi.interaction',
            'name' => [
                'en' => $questionattempt->description,
            ],
            'description' => [
                'en' => $questionattempt->description,
            ],
            'interactionType' => $questiontype,
            'correctResponsesPattern' => [
                '0' => $questionattempt->correct_responses_pattern,
            ],
        ],
    ];

    $result = [
        'response' => $questionattempt->response,
        'score' => [
            'raw' => (int) $score,
            'min' => 0.0,
            'max' => (int) $maxscore,
            'scaled' => utils\get_scaled_score((int) $score, 0, (int) $maxscore),
        ],
    ];

    if ($questiontype === 'choice') {
        $additionals = json_decode($questionattempt->additionals);
        $choices = [];
        foreach ($additionals->choices as $choice) {
            if ((int) $choice->id === (int) $questionattempt->response) {
                $result['response'] = $choice->description->{'en-US'};
            }
            $choices[] = [
                'id' => (string) $choice->id,
                'description' => [
                    'en-US' => $choice->description->{'en-US'},
                ],
            ];
        }
        usort($choices, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        $object['definition']['choices'] = $choices;
    }

    return [[
        'actor' => utils\get_user($config, $user),
        'verb' => utils\get_verb('answered', $config, $lang),
        'object' => $object,
        'result' => $result,
        'context' => [
            'language' => $lang,
            'extensions' => utils\extensions\base($config, $event, $course),
            'contextActivities' => [
                'parent' => utils\context_activities\get_parent(
                    $config,
                    $event->contextinstanceid,
                    true
                ),
                'category' => [
                    utils\get_activity\site($config),
                ],
            ],
        ],
    ]];
}
