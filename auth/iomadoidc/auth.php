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
 * OpenID Connect authentication plugin declaration.
 *
 * @package auth_iomadoidc
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft, Inc. (http://microsoft.com/)
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/login/lib.php');

/**
 * OpenID Connect Authentication Plugin.
 */
class auth_plugin_iomadoidc extends \auth_plugin_base {
    /** @var string Authentication plugin type - the same as db field. */
    public $authtype = 'iomadoidc';

    /** @var object Plugin config. */
    public $config;

    /** @var object extending \auth_iomadoidc\loginflow\base */
    public $loginflow;

    /**
     * Constructor.
     *
     * @param null $forceloginflow
     * @throws moodle_exception
     */
    public function __construct($forceloginflow = null) {
        global $SESSION;
        $loginflow = 'authcode';

        if (isset($SESSION->stateadditionaldata) && !empty($SESSION->stateadditionaldata) &&
            isset($SESSION->stateadditoinaldata['forceflow'])) {
            $loginflow = $SESSION->stateadditoinaldata['forceflow'];
        } else {
            if (!empty($forceloginflow) && is_string($forceloginflow)) {
                $loginflow = $forceloginflow;
            } else {
                $configuredloginflow = get_config('auth_iomadoidc', 'loginflow' . $this->postfix);
                if (!empty($configuredloginflow)) {
                    $loginflow = $configuredloginflow;
                }
            }
        }
        $loginflowclass = '\auth_iomadoidc\loginflow\\'.$loginflow;
        if (class_exists($loginflowclass)) {
            $this->loginflow = new $loginflowclass($this->config);
        } else {
            throw new moodle_exception('errorbadloginflow', 'auth_iomadoidc');
        }
        $this->config = $this->loginflow->config;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set() {
        return true;
    }

    /**
     * Returns a list of potential IdPs that this authentication plugin supports. Used to provide links on the login page.
     *
     * @param string $wantsurl The relative url fragment the user wants to get to.
     * @return array Array of idps.
     */
    public function loginpage_idp_list($wantsurl) {
        return $this->loginflow->loginpage_idp_list($wantsurl);
    }

    /**
     * Set an HTTP client to use.
     *
     * @param \auth_iomadoidc\httpclientinterface $httpclient
     * @return mixed
     */
    public function set_httpclient(\auth_iomadoidc\httpclientinterface $httpclient) {
        return $this->loginflow->set_httpclient($httpclient);
    }

    /**
     * Hook for overriding behaviour of login page.
     * This method is called from login/index.php page for all enabled auth plugins.
     */
    public function loginpage_hook() {
        global $frm;  // Can be used to override submitted login form.
        global $user; // Can be used to replace authenticate_user_login().
        if ($this->should_login_redirect()) {
            $this->loginflow->handleredirect();
        }
        return $this->loginflow->loginpage_hook($frm, $user);
    }

    /**
     * Determines if we will redirect to the redirecturi.
     *
     * @return bool If this returns true then redirect
     */
    public function should_login_redirect() {
        global $CFG, $SESSION;

        $iomadoidc = optional_param('iomadoidc', null, PARAM_BOOL);
        // Also support noredirect param - used by other auth plugins.
        $noredirect = optional_param('noredirect', 0, PARAM_BOOL);
        if (!empty($noredirect)) {
            $iomadoidc = 0;
        }
        if (!isset($this->config->forceredirect) || !$this->config->forceredirect) {
            return false; // Never redirect if we haven't enabled the forceredirect setting.
        }
        // Never redirect on POST.
        if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
            return false;
        }

        // Check whether we've skipped the login page already.
        // This is here because loginpage_hook is called again during form submission (all of login.php is processed) and
        // ?iomadoidc=off is not preserved forcing us to the IdP.
        //
        // This isn't needed when duallogin is on because $iomadoidc will default to 0 and duallogin is not part of the request.
        if ((isset($SESSION->iomadoidc) && $SESSION->iomadoidc == 0)) {
            if (!isset($SESSION->silent_login_mode)) {
                return false;
            }
        }

        // If the user is redirectred to the login page immediately after logging out, don't redirect.
        $silentloginmodesetting = get_config('auth_iomadoidc', 'silentloginmode' . $this->postfix);
        $forceredirectsetting = get_config('auth_iomadoidc', 'forceredirect' . $this->postfix);
        $forceloginsetting = get_config('core', 'forcelogin');
        if ($silentloginmodesetting && $forceredirectsetting && $forceloginsetting && isset($_SERVER['HTTP_REFERER']) &&
            strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot) !== false) {
            return false;
        }

        // Never redirect if requested so.
        if ($iomadoidc === 0) {
            $SESSION->iomadoidc = $iomadoidc;
            return false;
        }
        // We are off to IOMAD OIDC land so reset the force in SESSION.
        if (isset($SESSION->iomadoidc)) {
            unset($SESSION->iomadoidc);
        }

        return true;
    }

    /**
     * Will check if we have to redirect before going to login page
     */
    public function pre_loginpage_hook() {
        if ($this->should_login_redirect()) {
            $this->loginflow->handleredirect();
        }
    }

    /**
     * Handle requests to the redirect URL.
     *
     * @return mixed Determined by loginflow.
     */
    public function handleredirect() {
        return $this->loginflow->handleredirect();
    }

    /**
     * Handle IOMAD OIDC disconnection from Moodle account.
     *
     * @param bool $justremovetokens If true, just remove the stored IOMAD OIDC tokens for the user, otherwise revert login methods.
     * @param bool $donotremovetokens If true, do not remove tokens when disconnecting. This migrates from a login account to a
     *                                "linked" account.
     * @param moodle_url|null $redirect Where to redirect if successful.
     * @param moodle_url|null $selfurl The page this is accessed from. Used for some redirects.
     * @param null $userid
     * @return mixed
     */
    public function disconnect($justremovetokens = false, $donotremovetokens = false, \moodle_url $redirect = null,
                               \moodle_url $selfurl = null, $userid = null) {
        return $this->loginflow->disconnect($justremovetokens, $donotremovetokens, $redirect, $selfurl, $userid);
    }

    /**
     * This is the primary method that is used by the authenticate_user_login() function in moodlelib.php.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password = null) {
        global $CFG;
        // Short circuit for guest user.
        if (!empty($CFG->guestloginbutton) && $username === 'guest' && $password === 'guest') {
            return false;
        }
        return $this->loginflow->user_login($username, $password);
    }

    /**
     * Read user information from external database and returns it as array().
     *
     * @param string $username username
     * @return mixed array with no magic quotes or false on error
     */
    public function get_userinfo($username) {
        return $this->loginflow->get_userinfo($username);
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from get_userinfo() method.
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * @return bool Whether the plugin uses password hashes from Moodle user table for authentication.
     */
    public function is_internal() {
        return false;
    }

    /**
     * Post authentication hook.
     *
     * This method is called from authenticate_user_login() for all enabled auth plugins.
     *
     * @param object $user user object, later used for $USER
     * @param string $username (with system magic quotes)
     * @param string $password plain text password (with system magic quotes)
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        global $DB;
        if (!empty($user) && !empty($user->auth) && $user->auth === 'iomadoidc') {
            $tokenrec = $DB->get_record('auth_iomadoidc_token', ['userid' => $user->id]);
            if (!empty($tokenrec)) {
                // If the token record username is out of sync (ie username changes), update it.
                if ($tokenrec->username != $user->username) {
                    $updatedtokenrec = new \stdClass;
                    $updatedtokenrec->id = $tokenrec->id;
                    $updatedtokenrec->username = $user->username;
                    $DB->update_record('auth_iomadoidc_token', $updatedtokenrec);
                    $tokenrec = $updatedtokenrec;
                }
            } else {
                // There should always be a token record here, so a failure here means
                // the user's token record doesn't yet contain their userid.
                $tokenrec = $DB->get_record('auth_iomadoidc_token', ['username' => $username]);
                if (!empty($tokenrec)) {
                    $tokenrec->userid = $user->id;
                    $updatedtokenrec = new \stdClass;
                    $updatedtokenrec->id = $tokenrec->id;
                    $updatedtokenrec->userid = $user->id;
                    $DB->update_record('auth_iomadoidc_token', $updatedtokenrec);
                    $tokenrec = $updatedtokenrec;
                }
            }

            $eventdata = [
                'objectid' => $user->id,
                'userid' => $user->id,
                'other' => ['username' => $user->username],
            ];
            $event = \auth_iomadoidc\event\user_loggedin::create($eventdata);
            $event->trigger();
        }
    }

    /**
     * Log out user from Microsoft 365 if single sign off integration is enabled.
     *
     * @param stdClass $user
     *
     * @return bool
     */
    public function postlogout_hook($user) {
        global $CFG, $DB;

        $singlesignoutsetting = get_config('auth_iomadoidc', 'single_sign_off' . $this->postfix);

        if ($singlesignoutsetting) {
            $redirect = false;

            if ($user->auth == 'iomadoidc') {
                $redirect = true;
            } else if (auth_iomadoidc_is_local_365_installed()) {
                if ($DB->record_exists('local_o365_objects', ['type' => 'user', 'moodleid' => $user->id])) {
                    $redirect = true;
                }
            }

            if ($redirect) {
                $logouturl = get_config('auth_iomadoidc', 'logouturi' . $this->postfix);
                if (!$logouturl) {
                    $logouturl = 'https://login.microsoftonline.com/common/oauth2/logout?post_logout_redirect_uri=' .
                        urlencode($CFG->wwwroot);
                } else {
                    if (preg_match("/^https:\/\/login.microsoftonline.com\//", $logouturl) &&
                        preg_match("/\/oauth2\/logout$/", $logouturl)) {
                        $logouturl .= '?post_logout_redirect_uri=' . urlencode($CFG->wwwroot);
                    }
                }

                redirect($logouturl);
            }
        }

        return true;
    }
}
