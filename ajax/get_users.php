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
 * @package   tool_mnetusers
 * @category  tool
 * @author    Valery Fremaux <valery.fremaux@gmail.com>
 */

require('../../../../config.php');

// Security.

require_login();
require_capability('moodle/site:config', context_system::instance());

$filter = optional_param('filter', '', PARAM_TEXT);

$filterclause = (!empty($filter)) ? " AND lastname LIKE '%$filter%' " : '' ;

$select = " deleted = 0 AND mnethostid = ? $filterclause";

if ($users = $DB->get_records_select('user', $select, array($CFG->mnet_localhost_id), 'username', 'id, '.get_all_user_name_fields(true, ''))) {
    foreach ($users as $user) {
        $useropts[$user->id] = fullname($user);
    }
} else {
    $useropts = array();
}

echo json_encode($useropts);