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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class User_Choice_Form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $select = " deleted = 0 AND mnethostid = ? ";
        $fields = 'id,'.get_all_user_name_fields(true, '');
        $numusers = $DB->count_records_select('user', $select, array($CFG->mnet_localhost_id));

        $users = $DB->get_records_select('user', $select, array($CFG->mnet_localhost_id), 'lastname,firstname', $fields, 0, 100);

        $hosts = $DB->get_records_select('mnet_host', " deleted = 0 AND applicationid = 1 ", array());

        $hostsopt = array();

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        foreach ($hosts as $host) {
            if (!empty($host->wwwroot) && $host->wwwroot != $CFG->wwwroot) {
                $hostsopt[$host->wwwroot] = $host->name;
            }
        }

        $select = & $mform->addElement('header', 'head1', get_string('chooseusers', 'tool_mnetusers'));

        if (!empty($users)) {
            foreach ($users as $user) {
                $usersopts[$user->id] = fullname($user);
            }

            $attrs = array( 'onchange' => "refreshuserlist(this);", 'size' => 15);
            $select = & $mform->addElement('text', 'filter', get_string('filter', 'tool_mnetusers'), $attrs);
            $mform->setType('filter', PARAM_TEXT);

            $select = & $mform->addElement('select', 'users', '', $usersopts);
            $select->setMultiple(true);

            if ($numusers > count($users)) {
                $mform->addElement('static', 'overcountadvice', '', get_string('moreusersusefilter', 'tool_mnetusers'));
            }

            $select = & $mform->addElement('header', 'head2', get_string('choosemnethosts', 'tool_mnetusers'));
            $mform->setExpanded('head2');
            $select = & $mform->addElement('select', 'nodes', '', $hostsopt);
            $select->setMultiple(true);

            $systemcontext = context_system::instance();
            $mform->addElement('header', 'head3', get_string('asrole', 'tool_mnetusers'));
            $mform->setExpanded('head3');
            $roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);

            $roleoptions = array();
            foreach ($roles as $rid => $role) {
                $roleoptions[$role->shortname] = $role->localname ;
            }
            array_unshift($roleoptions, get_string('none'));
            $group = array();
            $label = get_string('checklocalroleadvice', 'tool_mnetusers');
            $group[] = & $mform->createElement('select', 'role', $label, $roleoptions);
            $group[] = & $mform->createElement('checkbox', 'unassign', 0, get_string('unassign', 'tool_mnetusers'), 0);
            $mform->addGroup($group, 'group1', ' ', '', false);

            $radioarray = array();
            $radioarray[] =& $mform->createElement('radio', 'addsiteadmin', '', get_string('setsiteadmin', 'tool_mnetusers'), 1);
            $radioarray[] =& $mform->createElement('radio', 'addsiteadmin', '', get_string('unsetsiteadmin', 'tool_mnetusers'), 0);
            $mform->addGroup($radioarray, 'group2', '', array(' '), false);

            $mform->addElement('header', 'head4', get_string('propagate', 'tool_mnetusers'));
            $mform->setExpanded('head4');
            $mform->addElement('submit', 'go', get_string('launch', 'tool_mnetusers'));
        } else {
            $select = & $mform->addElement('header', 'head3', get_string('nothingtopropagate', 'tool_mnetusers'));
            $mform->addElement('submit', 'cancel', get_string('cancel'));
        }
    }
}