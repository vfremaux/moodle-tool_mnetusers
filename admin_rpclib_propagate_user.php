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
 */
require('../../../config.php');
require_once($CFG->dirroot.'/admin/tool/mnetusers/admin_user_choice_form.php');
require_once($CFG->dirroot.'/blocks/vmoodle/rpclib.php');
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
                        $userobj = $DB->get_record('user', array('id' => $userid));
                        $userobj->auth = 'mnet';
                        $userobj->password = '';
                        $output .= get_string('propagating', 'tool_mnetusers', fullname($userobj));
                        $userhost = $DB->get_record('mnet_host', array('id' => $USER->mnethostid));
                        $caller = new StdClass();
                        $usermnethostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
                        $caller->username = $USER->username;
                        $caller->remoteuserhostroot = $usermnethostroot;
                        $caller->remotehostroot = $usermnethostroot;

                        // Check if exists.
                        $exists = false;
                        if ($return = mnetadmin_rpc_user_exists($caller, $userobj->username, $propagatedhost, true)) {
                            $response = json_decode($return);
                            if (empty($response)) {
                                debugging(print_object($return), DEBUG_DEVELOPER);
                            }
                            if ($response->status != 200 || empty($response->user)) {
                                $output .= "-&gt; {$userobj->username} did not exist. Will create it.\n<br/>";
                                $exists = false;
                            } else {
                                if (!empty($response->user->deleted)) {
                                    $output .= "-&gt; {$userobj->username} was there but deleted. Reviving \n<br/>";
                                    $exists = false;
                                } else {
                                    $output .= "-&gt; {$userobj->username} was there \n<br/>";
                                    $exists = true;
                                }
                            }
                        }
    
                        $created = false;
                        if (!$exists) {
                            // Call remote user creation function locally using bounce effect.
                            if ($return = mnetadmin_rpc_create_user($caller, $userobj->username, $userobj, '', $propagatedhost, true)) {
                                $response = json_decode($return);

                                if (empty($response)) {
                                    debugging(print_object($return), DEBUG_DEVELOPER);
                                }

                                if ($response->status != 200) {
                                    debugging(print_object($response), DEBUG_DEVELOPER);
                                } else {
                                    $created = true;

                                    /** in case we have user_mnet_hosts, give them logical access */
                                    if (file_exists($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php')) {
                                        include_once($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php');
                                        if ($result = user_mnet_hosts_add_access($userobj, $propagatedhost)) {
                                            $output .= '<br/>'.$result;
                                        }
                                    }
                                }
                            }
                        }

                        if ($exists || $created) {
                            if (!empty($data->unassign)) {
                                $data->role = '-'.$data->role; // Send unassign sign.
                            }

                            if (!empty($data->addsiteadmin)) {
                                $data->role = '+'.$data->role; // Send site admin addition.
                            }
    
                            $rpc_client = new mnet_xmlrpc_client();
                            $rpc_client->set_method('blocks/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_assign_role');
                            $rpc_client->add_param($caller, 'struct'); // Username.
                            $rpc_client->add_param($userobj->username, 'string');
                            $rpc_client->add_param($data->role, 'string');

                            $mnet_host = new mnet_peer();
                            if ($mnet_host->set_wwwroot($propagatedhost)) {
                                $result = $rpc_client->send($mnet_host);
                                if (empty($result)) {
                                    $response->errors[] = ' remote failed assign in '.$propagatedhost;
                                    if (is_array($rpc_client->error)) {
                                        $response->errors += $rpc_client->error;
                                    }
                                    $response->error = ' remote failed assign in '.$propagatedhost;
                                } else {
                                    // Whatever we have, aggregate eventual remote errors to error stack.
                                    $response = json_decode($rpc_client->response);
                                    if ($response->status == 200) {
                                        $output .= $response->message."<br/>\n";
                                    } else {
                                        if (!empty($response->errors)) {
                                            foreach ($response->errors as $remoteerror) {
                                                $response->errors[] = $remoteerror;
                                            }
                                            $response->error = ' remote failed assign in '.$propagatedhost;
                                            debugging(print_object($response->errors), DEBUG_DEVELOPER);
                                        }
                                    }
                                }
                            }
                        }
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
