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
 * LDAP invitation helper.
 *
 * @package    tool_mnetusers
 * @copyright  2010 Valery Feemaux
 * @author     Valery Fremaux - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    list($hasconfig, $hassiteconfig) = local_adminsettings_access();
} else {
    // Standard Moodle code
    $hasconfig = $hassiteconfig = has_capability('moodle/site:config', context_system::instance());
}

if ($hassiteconfig) {
    //--- general settings -----------------------------------------------------------------------------------
    $ADMIN->add('mnet', new admin_externalpage('toolmnetusers', get_string('pluginname', 'tool_mnetusers'), "{$CFG->wwwroot}/admin/tool/mnetusers/admin_rpclib_propagate_user.php"));
}
