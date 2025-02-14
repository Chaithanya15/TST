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
 * @package   mod_trainingevent
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * As of the implementation of this block and the general navigation code
 * in Moodle 2.0 the body of immediate upgrade work for this block and
 * settings is done in core upgrade {@see lib/db/upgrade.php}
 *
 * There were several reasons that they were put there and not here, both becuase
 * the process for the two blocks was very similar and because the upgrade process
 * was complex due to us wanting to remvoe the outmoded blocks that this
 * block was going to replace.
 *
 * @global moodle_database $DB
 * @param int $oldversion
 * @param object $block
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_trainingevent_upgrade($oldversion) {
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011111701) {

        // Define table trainingevent_users to be created.
        $table = new xmldb_table('trainingevent_users');

        // Adding fields to table trainingevent_users.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('trainingeventid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table trainingevent_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for trainingevent_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Label savepoint reached.
        upgrade_mod_savepoint(true, 2011111701, 'trainingevent');
    }

    if ($oldversion < 2014012301) {

        // Define field approvaltype to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('approvaltype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'classroomid');

        // Conditionally launch add field approvaltype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2014012301, 'trainingevent');
    }

    if ($oldversion < 2020091600){

        // Define field approvaltype to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('coursecapacity', XMLDB_TYPE_INTEGER, '10');

        // Conditionally launch add field approvaltype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2020091600, 'trainingevent');
    }

    if ($oldversion < 2020092100) {

        // Define field id to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('waitlisted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2020092100, 'trainingevent');
    }

    if ($oldversion < 2020111800) {

        // Define field coursecapacity to be added to trainingevent as it was missing for xmldb install.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('coursecapacity', XMLDB_TYPE_INTEGER, '10');

        // Conditionally launch add field coursecapacity.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field waitlisted to be added to trainingevent_users as it was missing for xmldb install.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('waitlisted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, null);

        // Conditionally launch add field waitlisted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field haswaitinglist to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('haswaitinglist', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'approvaltype');

        // Conditionally launch add field haswaitinglist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2020111800, 'trainingevent');
    }

    if ($oldversion < 2022032400) {

        // Define field sendreminder to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('sendreminder', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'coursecapacity');

        // Conditionally launch add field sendreminder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field emailteachers to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('emailteachers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sendreminder');

        // Conditionally launch add field emailteachers.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field lockdays to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('lockdays', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'emailteachers');

        // Conditionally launch add field lockdays.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field exclusive to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('isexclusive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'lockdays');

        // Conditionally launch add field exclusive.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2022032400, 'trainingevent');
    }

    if ($oldversion < 2024030100) {

        // Define field setreminder to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('setreminder', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sendreminder');

        // Conditionally launch add field setreminder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2024030100, 'trainingevent');
    }

    if ($oldversion < 2025010600) {

        // Define field booking_notes to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'userid');

        // Conditionally launch add field booking_notes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_format to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes_format', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'booking_notes');

        // Conditionally launch add field booking_notes_format.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025010600, 'trainingevent');
    }

    if ($oldversion < 2025011000) {

        // Define field approved to be added to trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('approved', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'waitlisted');

        // Conditionally launch add field approved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_format to be dropped from trainingevent_users.
        $table = new xmldb_table('trainingevent_users');
        $field = new xmldb_field('booking_notes_format');

        // Conditionally launch drop field booking_notes_format.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Mark all current records for the training events users as approved.
        $DB->set_field('trainingevent_users', 'approved', 1);

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025011000, 'trainingevent');
    }

    if ($oldversion < 2025012300) {

        // Define field remindersent to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('remindersent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isexclusive');

        // Conditionally launch add field remindersent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update all of the past events and events which will already have sent a reminder.
        $runtime = time();
        $DB->set_field_select('trainingevent',
                              'remindersent',
                               1,
                              "setreminder = 1 AND startdatetime < :runtime",
                              ['runtime' => $runtime]);
        $DB->set_field_select('trainingevent',
                              'remindersent',
                               1,
                              "setreminder = 1 AND sendreminder > 0 AND (sendreminder - 1) *24 * 60 * 60 + :runtime > startdatetime",
                              ['runtime' => $runtime]);

        // Define field requirenotes to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('requirenotes', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'remindersent');

        // Conditionally launch add field requirenotes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field booking_notes_default to be added to trainingevent.
        $table = new xmldb_table('trainingevent');
        $field = new xmldb_field('booking_notes_default', XMLDB_TYPE_TEXT, null, null, null, null, null, 'requirenotes');

        // Conditionally launch add field booking_notes_default.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingevent savepoint reached.
        upgrade_mod_savepoint(true, 2025012300, 'trainingevent');
    }

    return $result;
}
