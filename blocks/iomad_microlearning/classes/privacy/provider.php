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
 * Privacy Subsystem implementation for block_iomad_microlearning.
 *
 * @package    block_iomad_microlearning
 * @copyright  2021 Derick Turner
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_microlearning\privacy;

use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\request\helper;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\writer;
use \context_system;
use \context_user;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'microlearning_thread_user',
            [
                'id' => 'privacy:metadata:microlearning_thread_user:id',
                'userid' => 'privacy:metadata:microlearning_thread_user:userid',
                'threadid' => 'privacy:metadata:microlearning_thread_user:threadid',
                'nuggetid' => 'privacy:metadata:microlearning_thread_user:nuggetid',
                'groupid' => 'privacy:metadata:microlearning_thread_user:groupid',
                'schedule_date' => 'privacy:metadata:microlearning_thread_user:schedule_date',
                'due_date' => 'privacy:metadata:microlearning_thread_user:due_date',
                'reminder1_date' => 'privacy:metadata:microlearning_thread_user:reminder1_date',
                'reminder2_date' => 'privacy:metadata:microlearning_thread_user:reminder2_date',
                'messagetime' => 'privacy:metadata:microlearning_thread_user:messagetime',
                'message_delivered' => 'privacy:metadata:microlearning_thread_user:message_delivered',
                'reminder1_delivered' => 'privacy:metadata:microlearning_thread_user:reminder1_delivered',
                'reminder2_delivered' => 'privacy:metadata:microlearning_thread_user:reminder2_delivered',
                'timecompleted' => 'privacy:metadata:microlearning_thread_user:timecompleted',
                'accesskey' => 'privacy:metadata:microlearning_thread_user:accesskey',
                'timecreated' => 'privacy:metadata:microlearning_thread_user:timecreated',
            ],
            'privacy:metadata:microlearning_thread_user'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // System context only.
        $sql = "SELECT c.id
                  FROM {context} c
                WHERE contextlevel = :contextlevel";

        $params = [
            'userid'  => $userid,
            'contextlevel'  => CONTEXT_SYSTEM,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        $context = context_system::instance();

        // Get the microlearning_thread_user information.
        if ($microlearnings = $DB->get_records('microlearning_thread_user', array('userid' => $user->id))) {
            $microlearningout = (object) [];
            foreach ($microlearnings as $id => $microlearning) {
                if (!empty($microlearning->schedule_date)) {
                    $microlearnings[$id]->schedule_date = transform::datetime($microlearning->schedule_date);
                }
                if (!empty($microlearning->due_date)) {
                    $microlearnings[$id]->due_date = transform::datetime($microlearning->due_date);
                }
                if (!empty($microlearning->reminder1_date)) {
                    $microlearnings[$id]->reminder1_date = transform::datetime($microlearning->reminder1_date);
                }
                if (!empty($microlearning->reminder2_date)) {
                    $microlearnings[$id]->reminder2_date = transform::datetime($microlearning->reminder2_date);
                }
                if (!empty($microlearning->timecompleted)) {
                    $microlearnings[$id]->timecompleted = transform::datetime($microlearning->timecompleted);
                }
                if (!empty($microlearning->timecreated)) {
                    $microlearnings[$id]->timecreated = transform::datetime($microlearning->timecreated);
                }
            }
            $microlearningout->microlearning = $microlearnings;
            writer::with_context($context)->export_data(iarray(get_string('pluginname', 'block_iomad_microlearning')), $microlearningout);
        }
    }


    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }
        $DB->delete_records('microlearning_thread_user');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('microlearning_thread_user', array('userid' => $userid));
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $params = [
            'userid' => $context->id,
            'contextuser' => CONTEXT_USER,
        ];

        $sql = "SELECT i.userid as userid
                  FROM {microlearning_thread_user} i
                  JOIN {context} ctx
                       ON ctx.instanceid = i.userid
                       AND ctx.contextlevel = :contextuser
                 WHERE ctx.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof context_user) {
            $DB->delete_records('microlearning_thread_user', array('userid' => $context->id));
        }
    }
}
