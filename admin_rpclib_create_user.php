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

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/editadvanced_form.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

$needsdisplay = true;

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$useradvancedstr = get_string('pluginname', 'tool_mnetusers');
$url = new moodle_url('/admin/tool/mnetusers/admin_rpclib_propagate_user.php');

$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);
$PAGE->navbar->add($useradvancedstr);
$PAGE->set_heading($useradvancedstr);
$PAGE->set_title($useradvancedstr);

$form = new user_editadvanced_form();

// Get available mnet_hosts.

$hosts = $DB->get_records_select('mnet_host', " deleted = 0 AND applicationid = 1 ", array());
$hostopt = array();

foreach ($hosts as $host) {
    if (!empty($host->wwwroot) && $host->wwwroot != $CFG->wwwroot) {
        $hostopt[$host->wwwroot] = $host->name;
    }
}

$form->_form->addElement('static', 'bouncelabel', get_string('targetremotehost', 'local'));
$fselect = & $form->_form->addElement('select', 'bounce', '', $hostopt);
$fselect->setMultiple(true);

$ouptut = '';

if (!$form->is_cancelled()) {
    if ($userobj = $form->get_data()) {
        $output .= "creating user with RPC cascade";
        $bounceto = implode(';', $userobj->bounce);
        unset($userobj->bounce);

        // Invoke local XML-RPC mnetadmin-rpc_create_user call.
        $caller = new StdClass();
        $caller->username = $USER->username;
        $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
        $caller->remoteuserhostroot = $userhostroot;
        $caller->remotehostroot = $CFG->wwwroot;
        if ($return = mnetadmin_rpc_create_user($caller, $userobj->username, (array)$userobj, '', $bounceto, false)) {
            $response = json_decode($return);
            if ($response->status != RPC_SUCCESS) {
                $output .= "XML RPC Remote Function Error";
            }
        } else {
            $output .= "XML RPC Direct Call Error";
        }
        $needsdisplay = false;
        $output .= $OUTPUT->continue_button($CFG->wwwroot.'/admin/index.php');
   }
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox', '80%');

if (!empty($output)) {
    echo $OUTPUT->box_start('generalbox', '80%');
    echo $output;
    echo $OUTPUT->box_end();
}

if ($needsdisplay) {
    $form->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();