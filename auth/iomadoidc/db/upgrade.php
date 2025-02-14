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
 * Plugin upgrade script.
 *
 * @package auth_iomadoidc
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft, Inc. (http://microsoft.com/)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/iomadoidc/lib.php');

/**
 * Update plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_iomadoidc_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014111703) {
        // Lengthen field.
        $table = new xmldb_table('auth_iomadoidc_token');
        $field = new xmldb_field('scope', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'username');
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, 2014111703, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015012702) {
        $table = new xmldb_table('auth_iomadoidc_state');
        $field = new xmldb_field('additionaldata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2015012702, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015012703) {
        $table = new xmldb_table('auth_iomadoidc_token');
        $field = new xmldb_field('iomadoidcusername', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'username');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2015012703, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015012704) {
        // Update IOMADOIDC users.
        $sql = 'SELECT u.id as userid,
                       u.username as username,
                       tok.id as tokenid,
                       tok.iomadoidcuniqid as iomadoidcuniqid,
                       tok.idtoken as idtoken,
                       tok.iomadoidcusername as iomadoidcusername
                  FROM {auth_iomadoidc_token} tok
                  JOIN {user} u ON u.username = tok.username
                 WHERE u.auth = ? AND deleted = ?';
        $params = ['iomadoidc', 0];
        $userstoupdate = $DB->get_recordset_sql($sql, $params);
        foreach ($userstoupdate as $user) {
            if (empty($user->idtoken)) {
                continue;
            }

            try {
                // Decode idtoken and determine iomadoidc username.
                $idtoken = \auth_iomadoidc\jwt::instance_from_encoded($user->idtoken);
                $iomadoidcusername = $idtoken->claim('upn');
                if (empty($iomadoidcusername)) {
                    $iomadoidcusername = $idtoken->claim('sub');
                }

                // Populate token iomadoidcusername.
                if (empty($user->iomadoidcusername)) {
                    $updatedtoken = new \stdClass;
                    $updatedtoken->id = $user->tokenid;
                    $updatedtoken->iomadoidcusername = $iomadoidcusername;
                    $DB->update_record('auth_iomadoidc_token', $updatedtoken);
                }

                // Update user username (if applicable), so user can use rocreds loginflow.
                if ($user->username == strtolower($user->iomadoidcuniqid)) {
                    // Old username, update to upn/sub.
                    if ($iomadoidcusername != $user->username) {
                        // Update username.
                        $updateduser = new \stdClass;
                        $updateduser->id = $user->userid;
                        $updateduser->username = $iomadoidcusername;
                        $DB->update_record('user', $updateduser);

                        $updatedtoken = new \stdClass;
                        $updatedtoken->id = $user->tokenid;
                        $updatedtoken->username = $iomadoidcusername;
                        $DB->update_record('auth_iomadoidc_token', $updatedtoken);
                    }
                }
            } catch (moodle_exception $e) {
                continue;
            }
        }
        upgrade_plugin_savepoint(true, 2015012704, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015012707) {
        if (!$dbman->table_exists('auth_iomadoidc_prevlogin')) {
            $dbman->install_one_table_from_xmldb_file(__DIR__.'/install.xml', 'auth_iomadoidc_prevlogin');
        }
        upgrade_plugin_savepoint(true, 2015012707, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015012710) {
        // Lengthen field.
        $table = new xmldb_table('auth_iomadoidc_token');
        $field = new xmldb_field('scope', XMLDB_TYPE_TEXT, null, null, null, null, null, 'iomadoidcusername');
        $dbman->change_field_type($table, $field);
        upgrade_plugin_savepoint(true, 2015012710, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015111904.01) {
        // Ensure the username field in auth_iomadoidc_token is lowercase.
        $authtokensrs = $DB->get_recordset('auth_iomadoidc_token');
        foreach ($authtokensrs as $authtokenrec) {
            $newusername = trim(\core_text::strtolower($authtokenrec->username));
            if ($newusername !== $authtokenrec->username) {
                $updatedrec = new \stdClass;
                $updatedrec->id = $authtokenrec->id;
                $updatedrec->username = $newusername;
                $DB->update_record('auth_iomadoidc_token', $updatedrec);
            }
        }
        upgrade_plugin_savepoint(true, 2015111904.01, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2015111905.01) {
        // Update old endpoints.
        $config = get_config('auth_iomadoidc');
        if ($config->authendpoint === 'https://login.windows.net/common/oauth2/authorize') {
            set_config('authendpoint', 'https://login.microsoftonline.com/common/oauth2/authorize', 'auth_iomadoidc');
        }

        if ($config->tokenendpoint === 'https://login.windows.net/common/oauth2/token') {
            set_config('tokenendpoint', 'https://login.microsoftonline.com/common/oauth2/token', 'auth_iomadoidc');
        }

        upgrade_plugin_savepoint(true, 2015111905.01, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2018051700.01) {
        $table = new xmldb_table('auth_iomadoidc_token');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'username');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $sql = 'SELECT tok.id, tok.username, u.username, u.id as userid
                      FROM {auth_iomadoidc_token} tok
                      JOIN {user} u ON u.username = tok.username';
            $records = $DB->get_recordset_sql($sql);
            foreach ($records as $record) {
                $newrec = new \stdClass;
                $newrec->id = $record->id;
                $newrec->userid = $record->userid;
                $DB->update_record('auth_iomadoidc_token', $newrec);
            }
        }
        upgrade_plugin_savepoint(true, 2018051700.01, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2020020301) {
        $oldgraphtokens = $DB->get_records('auth_iomadoidc_token', ['resource' => 'https://graph.windows.net']);
        foreach ($oldgraphtokens as $graphtoken) {
            $graphtoken->resource = 'https://graph.microsoft.com';
            $DB->update_record('auth_iomadoidc_token', $graphtoken);
        }

        $iomadoidcresource = get_config('auth_iomadoidc', 'iomadoidcresource');
        if ($iomadoidcresource !== false && strpos($iomadoidcresource, 'windows') !== false) {
            set_config('iomadoidcresource', 'https://graph.microsoft.com', 'auth_iomadoidc');
        }

        upgrade_plugin_savepoint(true, 2020020301, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2020071503) {
        $localo365singlesignoffsetting = get_config('local_o365', 'single_sign_off');
        if ($localo365singlesignoffsetting !== false) {
            set_config('single_sign_off', true, 'auth_iomadoidc');
            unset_config('single_sign_off', 'local_o365');
        }

        upgrade_plugin_savepoint(true, 2020071503, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2020110901) {
        if ($dbman->field_exists('auth_iomadoidc_token', 'resource')) {
            // Rename field resource on table auth_iomadoidc_token to tokenresource.
            $table = new xmldb_table('auth_iomadoidc_token');

            $field = new xmldb_field('resource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null, 'scope');

            // Launch rename field resource.
            $dbman->rename_field($table, $field, 'tokenresource');
        }

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2020110901, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2020110903) {
        // Part 1: add index to auth_iomadoidc_token table.
        $table = new xmldb_table('auth_iomadoidc_token');

        // Define index userid (not unique) to be added to auth_iomadoidc_token.
        $useridindex = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);

        // Conditionally launch add index userid.
        if (!$dbman->index_exists($table, $useridindex)) {
            $dbman->add_index($table, $useridindex);
        }

        // Define index username (not unique) to be added to auth_iomadoidc_token.
        $usernameindex = new xmldb_index('username', XMLDB_INDEX_NOTUNIQUE, ['username']);

        // Conditionally launch add index username.
        if (!$dbman->index_exists($table, $usernameindex)) {
            $dbman->add_index($table, $usernameindex);
        }

        // Part 2: update Authorization and token end point URL.
        $entratenant = get_config('local_o365', 'aadtenant');

        if ($entratenant) {
            $authorizationendpoint = get_config('auth_iomadoidc', 'authendpoint');
            if ($authorizationendpoint == 'https://login.microsoftonline.com/common/oauth2/authorize') {
                $authorizationendpoint = str_replace('common', $entratenant, $authorizationendpoint);
                set_config('authendpoint', $authorizationendpoint, 'auth_iomadoidc');
            }

            $tokenendpoint = get_config('auth_iomadoidc', 'tokenendpoint');
            if ($tokenendpoint == 'https://login.microsoftonline.com/common/oauth2/token') {
                $tokenendpoint = str_replace('common', $entratenant, $tokenendpoint);
                set_config('tokenendpoint', $tokenendpoint, 'auth_iomadoidc');
            }
        }

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2020110903, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2021051701) {
        // Migrate field mapping settings from local_o365.
        $existingfieldmappingsettings = get_config('local_o365', 'fieldmap');
        if ($existingfieldmappingsettings !== false) {
            $userfields = auth_iomadoidc_get_all_user_fields();

            $existingfieldmappingsettings = @unserialize($existingfieldmappingsettings);
            if (is_array($existingfieldmappingsettings)) {
                foreach ($existingfieldmappingsettings as $existingfieldmappingsetting) {
                    $fieldmap = explode('/', $existingfieldmappingsetting);

                    if (count($fieldmap) !== 3) {
                        // Invalid settings, ignore.
                        continue;
                    }

                    list($remotefield, $localfield, $behaviour) = $fieldmap;

                    if ($remotefield == 'facsimileTelephoneNumber') {
                        $remotefield = 'faxNumber';
                    }

                    set_config('field_map_' . $localfield, $remotefield, 'auth_iomadoidc');
                    set_config('field_lock_' . $localfield, 'unlocked', 'auth_iomadoidc');
                    set_config('field_updatelocal_' . $localfield, $behaviour, 'auth_iomadoidc');

                    if (($key = array_search($localfield, $userfields)) !== false) {
                        unset($userfields[$key]);
                    }
                }

                foreach ($userfields as $userfield) {
                    set_config('field_map_' . $userfield, '', 'auth_iomadoidc');
                    set_config('field_lock_' . $userfield, 'unlocked', 'auth_iomadoidc');
                    set_config('field_updatelocal_' . $userfield, 'always', 'auth_iomadoidc');
                }
            }

            unset_config('fieldmap', 'local_o365');
        }

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2021051701, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2022041901) {
        // Define field sid to be added to auth_iomadoidc_token.
        $table = new xmldb_table('auth_iomadoidc_token');
        $field = new xmldb_field('sid', XMLDB_TYPE_CHAR, '36', null, null, null, null, 'idtoken');

        // Conditionally launch add field sid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2022041901, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2022041906) {
        // Update idptype config.
        $idptypeconfig = get_config('auth_iomadoidc', 'idptype');
        $authorizationendpoint = get_config('auth_iomadoidc', 'authendpoint');
        if (empty($idptypeconfig)) {
            if (!$authorizationendpoint) {
                set_config('idptype', AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID, 'auth_iomadoidc');
            } else {
                $endpointversion = auth_iomadoidc_determine_endpoint_version($authorizationendpoint);
                switch ($endpointversion) {
                    case AUTH_IOMADOIDC_MICROSOFT_ENDPOINT_VERSION_1:
                        set_config('idptype', AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID, 'auth_iomadoidc');
                        break;
                    case AUTH_IOMADOIDC_MICROSOFT_ENDPOINT_VERSION_2:
                        set_config('idptype', AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM, 'auth_iomadoidc');
                        break;
                    default:
                        set_config('idptype', AUTH_IOMADOIDC_IDP_TYPE_OTHER, 'auth_iomadoidc');
                }
            }
        }

        // Update client authentication type configuration settings.
        $clientauthmethodconfig = get_config('auth_iomadoidc', 'clientauthmethod');
        if (empty($clientauthmethodconfig)) {
            $clientsecretconfig = get_config('auth_iomadoidc', 'clientsecret');
            $clientcertificateconfig = get_config('auth_iomadoidc', 'clientcert');
            $clientprivatekeyconfig = get_config('auth_iomadoidc', 'clientprivatekey');
            if (empty($clientsecretconfig) && !empty($clientcertificateconfig) && !empty($clientprivatekeyconfig)) {
                set_config('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_CERTIFICATE, 'auth_iomadoidc');
            } else {
                set_config('clientauthmethod', AUTH_IOMADOIDC_AUTH_METHOD_SECRET, 'auth_iomadoidc');
            }
        }

        // Update tenantnameorguid config.
        $tenantnameorguidconfig = get_config('auth_iomadoidc', 'tenantnameorguid');
        if (empty($tenantnameorguidconfig)) {
            $entratenant = get_config('local_o365', 'aadtenant');
            if ($entratenant) {
                set_config('tenantnameorguid', $entratenant, 'auth_iomadoidc');
            }
        }

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2022041906, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2022112801) {
        // Update tenantnameorguid config.
        unset_config('auth_iomadoidc', 'tenantnameorguid');

        // Oidc savepoint reached.
        upgrade_plugin_savepoint(true, 2022112801, 'auth', 'iomadoidc');
    }

    if ($oldversion < 2023100902) {
        // Set initial value for "clientcertsource" config.
        if (empty(get_config('auth_iomadoidc', 'clientcertsource'))) {
            set_config('clientcertsource', AUTH_IOMADOIDC_AUTH_CERT_SOURCE_TEXT, 'auth_iomadoidc');
        }

        upgrade_plugin_savepoint(true, 2023100902, 'auth', 'iomadoidc');
    }

    return true;
}
