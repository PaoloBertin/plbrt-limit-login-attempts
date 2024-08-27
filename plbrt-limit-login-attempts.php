<?php

/**
 * Plugin Name: PLBRT Limit Login Attempts
 * Plugin URI:  
 * Description: Limit rate of login attempts, including by way of cookies, for each IP.
 * Author:      Paolo Bertin
 * Author URI:  
 * Text Domain: plbrt-limit-login-attempts
 * License:     GPL-2.0+
 * Version:     1.0.0
 *
 * An object-oriented WordPress plugin based on plbrt-limit-login-attempts
 * by Pressidium
 * 
 * Based on Limit Login Attempts (http://devel.kostdoktorn.se/limit-login-attempts)
 * by Johan Eenfeldt (http://devel.kostdoktorn.se)
 *
 * Licenced under the GNU GPL:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace plbrt\limit_login_attempts;

use plbrt\limit_login_attempts\Logging\Log;

use plbrt\limit_login_attempts\Options\Options;
use plbrt\limit_login_attempts\Options\WP_Options;
use plbrt\limit_login_attempts\Hooks\Hooks_Manager;
use plbrt\limit_login_attempts\Pages\Settings_Page;
use plbrt\limit_login_attempts\Login\Login_Attempts;
use plbrt\limit_login_attempts\Login\Login_Error;
use plbrt\limit_login_attempts\Login\Cookie_Login;
use plbrt\limit_login_attempts\Login\State\Retries;
use plbrt\limit_login_attempts\Login\State\Lockouts;
use plbrt\limit_login_attempts\Standalone\Lockout_Logs;
use plbrt\limit_login_attempts\Notifications\Email_Notification;

if (! defined('ABSPATH')) {
    exit;
}

class Plugin
{
    const PREFIX = 'plbrt_limit_login_attempts';

    /**
     * @var Options An instance of the `Options` class.
     */
    public $options;

    /**
     * @var Hooks_Manager An instance of the `Hooks_Manager` class.
     */
    public $hooks_manager;

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        $this->require_files();
        $this->setup_constants();

        add_action('plugins_loaded', array($this, 'init'));
        Log::debug(PLUGIN_NAME . ' initialized');
    }

    /**
     * Require files.
     */
    private function require_files()
    {
        // autooad file librerie
        require_once __DIR__ . '/vendor/autoload.php';

        // autoload classi del plugin
        require_once __DIR__ . '/autoload.php';
        $autoloader = new Autoloader();
        $autoloader->register();
    }

    /**
     * Setup constants.
     */
    private function setup_constants()
    {
        if (! defined(__NAMESPACE__ . '\PLUGIN_URL')) {
            define(__NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        if (! defined(__NAMESPACE__ . '\VERSION')) {
            define(__NAMESPACE__ . '\VERSION', '1.0.0');
        }

        if (! defined('\PLUGIN_NAME')) {
            define('PLUGIN_NAME', 'plbrt-limit-login-attempts');
        }
    }

    /**
     * Initialize the plugin once activated plugins have been loaded.
     */
    public function init()
    {
        $this->options = new WP_Options();
        Log::debug('definite le opzioni');

        $this->hooks_manager = new Hooks_Manager();

        IP_Address::init($this->options->get('site_connection'));

        $lockout_logs = new Lockout_Logs($this->options, $this->hooks_manager);

        $retries  = new Retries($this->options);
        $lockouts = new Lockouts($this->options, $retries, $lockout_logs);

        $settings_page = new Settings_Page($this->options, $this->hooks_manager, $lockout_logs);
        $this->hooks_manager->register($settings_page);

        $login_error    = new Login_Error($this->options, $retries, $lockouts);
        $login_attempts = new Login_Attempts($retries, $lockouts, $login_error);
        $this->hooks_manager->register($login_error);
        $this->hooks_manager->register($login_attempts);

        $should_handle_cookie_login = $this->options->get('handle_cookie_login') === 'yes';

        if ($should_handle_cookie_login) {
            $cookie_login = new Cookie_Login($login_attempts, $lockouts);
            $this->hooks_manager->register($cookie_login);
        }

        $email_notification = new Email_Notification($this->options);
        $this->hooks_manager->register($email_notification);
    }
}

new Plugin();
