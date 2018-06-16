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
 * @package    tool_mnetusers
 * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 */
require('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/mnetusers/admin_user_choice_form.php');
require_once($CFG->dirroot.'/admin/tool/mnetusers/locallib.php');
require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');
require_once($CFG->dirroot.'/mnet/xmlrpc/client.php');

$needsdisplay = true;

// Security.

require_login();
$systemcontext = context_system::instance();

require_capability('moodle/site:config', $systemcontext);

$useradvancedstr = get_string('pluginname', 'tool_mnetusers');
$url = new moodle_url('/admin/tool/mnetusers/admin_rpclib_propagate_user.php');

$PAGE->requires->js('/admin/tool/mnetusers/js/ajax_js.js');
$PAGE->requires->jquery();

$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->navbar->add($useradvancedstr);
$PAGE->set_heading($useradvancedstr);
$PAGE->set_title($useradvancedstr);

$form = new User_Choice_Form();

// Get available mnet_hosts.

$output = '';

if (!$form->is_cancelled()) {
    if ($data = $form->get_data()) {
        if (!empty($data->users)) {
            if (!empty($data->nodes)) {
                foreach ($data->nodes as $propagatedhost) {
                    foreach ($data->users as $userid) {
                        $output .= propagate_user($userid, $propagatedhost, $data);
                        $output .= '<br/>';
                    }
                }
            } else {
                $output .= get_string('notargettopropagateto', 'tool_mnetusers');
            }
        } else {
            $output .= get_string('nousertopropagate', 'tool_mnetusers');
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox', '80%');
echo $OUTPUT->heading(get_string('propagate', 'tool_mnetusers'), 3);

if ($output) {
    echo $OUTPUT->box_start('generalbox', '80%');
    echo $output;
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die;
}


if ($needsdisplay) {
    $form->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

