<?php

namespace plbrt\limit_login_attempts\Pages;

use plbrt\limit_login_attempts\Hooks\Actions;

use plbrt\limit_login_attempts\Hooks\Hooks_Manager;
use plbrt\limit_login_attempts\Sections\Fields\Elements\Element;
use plbrt\limit_login_attempts\Statistics\Active_Lockouts;
use plbrt\limit_login_attempts\Statistics\Total_Lockouts;
use plbrt\limit_login_attempts\Standalone\Lockout_Logs;
use plbrt\limit_login_attempts\Standalone\Admin_Notice;
use plbrt\limit_login_attempts\IP_Address;
use plbrt\limit_login_attempts\Options\Options;
use plbrt\limit_login_attempts\Plugin;
use plbrt\limit_login_attempts\Utils;

if (! defined('ABSPATH')) {
    exit;
}

class Settings_Page extends Admin_Page implements Actions
{

    /**
     * @var Hooks_Manager
     */
    private $hooks_manager;

    /**
     * @var Lockout_Logs
     */
    private $lockout_logs;

    /**
     * Settings_Page constructor.
     *
     * @param Hooks_Manager $hooks_manager
     * @param Options       $options
     * @param Lockout_Logs  $lockout_logs
     */
    public function __construct($options, $hooks_manager, $lockout_logs)
    {
        parent::__construct($options);

        $this->hooks_manager = $hooks_manager;
        $this->lockout_logs  = $lockout_logs;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions()
    {
        $actions = parent::get_actions();

        $connection_type_selected = $this->options->get('site_connection');
        $connection_type_guess    = IP_Address::guess_connection_type();

        if ($connection_type_selected !== $connection_type_guess) {
            $actions['admin_notices'] = array('display_connection_type_warning');
        }

        return $actions;
    }

    /**
     * Display incorrect connection type admin notice.
     */
    public function display_connection_type_warning()
    {
        $faq_link = 'https://wordpress.org/extend/plugins/limit-login-attempts/faq/';

        $link_beginning = sprintf(
            '<a href="%s" title="%s">',
            esc_url($faq_link),
            esc_attr(__('FAQ', 'plbrt-limit-login-attempts'))
        );

        $link_end = '</a>';

        /** @noinspection SpellCheckingInspection */
        $message = sprintf(
            '<strong>%s</strong> %s',
            __('Current setting appears to be invalid.', 'plbrt-limit-login-attempts'),
            /* translators: %s indicate the beginning and end of the link to the FAQ page */
            sprintf(
                __('Please make sure it is correct. Further information can be found %shere%s', 'plbrt-limit-login-attempts'),
                $link_beginning,
                $link_end
            )
        );

        $this->render_admin_notice($message, Admin_Notice::WARNING);
    }

    /**
     * Return the menu title.
     *
     * @return string
     */
    protected function get_menu_title()
    {
        return __('Limit Login Attempts', 'plbrt-limit-login-attempts');
    }

    /**
     * Return the page title.
     *
     * @return string
     */
    protected function get_page_title()
    {
        return __('Limit Login Attempts Settings', 'plbrt-limit-login-attempts');
    }

    /**
     * Return the menu icon as a dashicon.
     *
     * @link https://developer.wordpress.org/resource/dashicons/
     *
     * @return string
     */
    protected function get_icon_url()
    {
        return 'dashicons-shield-alt';
    }

    /**
     * Return page slug.
     *
     * @return string
     */
    protected function get_slug()
    {
        return Plugin::PREFIX . '_settings';
    }

    /**
     * Return the site connection description.
     *
     * @return string
     */
    private function get_site_connection_description()
    {
        $is_behind_proxy = IP_Address::is_behind_proxy();

        if ($is_behind_proxy) {
            return sprintf(
                /* translators: %1$s is the proxy IP address and %2$s is the IP address of the user. */
                __('It appears the site is reached through a proxy server (proxy IP: %1$s, your IP: %2$s)', 'plbrt-limit-login-attempts'),
                IP_Address::get_reverse_proxy(),
                IP_Address::get_direct()
            );
        }

        return sprintf(
            /* translators: %s is the IP address of the user. */
            __('It appears the site is reached directly (from your IP: %s)', 'plbrt-limit-login-attempts'),
            IP_Address::get_direct()
        );
    }

    /**
     * Register the Statistics section.
     */
    private function register_statistics()
    {
        $statistics_section = $this->register_presentation_section(
            'statistics',
            array('title' => __('Statistics', 'plbrt-limit-login-attempts'))
        );

        $total_lockouts_field = $statistics_section->add_field(
            array('label' => __('Total lockouts', 'plbrt-limit-login-attempts'))
        );

        $total_lockouts_field->add_element(
            Element::CUSTOM_ELEMENT,
            array(
                'html' => new Total_Lockouts($this->options, $this->hooks_manager)
            )
        );

        $active_lockouts_field = $statistics_section->add_field(
            array('label' => __('Active lockouts', 'plbrt-limit-login-attempts'))
        );

        $active_lockouts_field->add_element(
            Element::CUSTOM_ELEMENT,
            array(
                'html' => new Active_Lockouts($this->options, $this->hooks_manager)
            )
        );
    }

    /**
     * Register the General Options section.
     */
    private function register_general_options()
    {
        $general_options_section = $this->register_section(
            'general_options',
            array('title' => __('General Options', 'plbrt-limit-login-attempts'))
        );

        $lockout_field = $general_options_section->add_field(
            array('label' => __('Lockout', 'plbrt-limit-login-attempts'))
        );

        $lockout_field->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'    => __('Allow retries', 'plbrt-limit-login-attempts'),
                'name'     => 'allowed_retries',
                'validate' => array(Utils::class, 'is_greater_than_zero')
            )
        );

        $lockout_field->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'     => __('Lockout time (in minutes)', 'plbrt-limit-login-attempts'),
                'name'      => 'normal_lockout_time',
                'validate'  => array(Utils::class, 'is_greater_than_zero'),
                'pre_write' => array(Utils::class, 'minutes_to_seconds'),
                'post_read' => array(Utils::class, 'seconds_to_minutes')
            )
        );

        $lockout_field->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'    => __('Max lockouts', 'plbrt-limit-login-attempts'),
                'name'     => 'max_lockouts',
                'validate' => array(Utils::class, 'is_greater_than_zero')
            )
        );

        $lockout_field->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'     => __('Increased lockout time (in hours)', 'plbrt-limit-login-attempts'),
                'name'      => 'long_lockout_time',
                'validate'  => array(Utils::class, 'is_greater_than_zero'),
                'pre_write' => array(Utils::class, 'hours_to_seconds'),
                'post_read' => array(Utils::class, 'seconds_to_hours')
            )
        );

        $lockout_field->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'     => __('Hours until retries are reset', 'plbrt-limit-login-attempts'),
                'name'      => 'hours_until_retries_reset',
                'validate'  => array(Utils::class, 'is_greater_than_zero'),
                'pre_write' => array(Utils::class, 'hours_to_seconds'),
                'post_read' => array(Utils::class, 'seconds_to_hours')
            )
        );

        $site_connection_field = $general_options_section->add_field(
            array(
                'label'       => __('Site connection', 'plbrt-limit-login-attempts'),
                'description' => $this->get_site_connection_description()
            )
        );

        $site_connection_field->add_element(
            Element::RADIO_ELEMENT,
            array(
                'label' => __('Site connection', 'plbrt-limit-login-attempts'),
                'name'  => 'site_connection',
                'values'  => array(
                    'direct'        => __('Direct connection', 'plbrt-limit-login-attempts'),
                    'reverse_proxy' => __('From behind a reverse proxy', 'plbrt-limit-login-attempts')
                )
            )
        );

        $handle_cookie_login_field = $general_options_section->add_field(
            array('label' => __('Handle cookie login', 'plbrt-limit-login-attempts'))
        );

        $handle_cookie_login_field->add_element(
            Element::RADIO_ELEMENT,
            array(
                'name'   => 'handle_cookie_login',
                'values' => array(
                    'yes' => __('Yes', 'plbrt-limit-login-attempts'),
                    'no'  => __('No', 'plbrt-limit-login-attempts')
                )
            )
        );

        $notify_on_lockout = $general_options_section->add_field(
            array('label'  => __('Notify on lockout', 'plbrt-limit-login-attempts'))
        );

        $notify_on_lockout->add_element(
            Element::CHECKBOX_ELEMENT,
            array(
                'label' => __('Log IP', 'plbrt-limit-login-attempts'),
                'name'  => 'notify_on_lockout_log_ip'
            )
        );

        $notify_on_lockout->add_element(
            Element::CHECKBOX_ELEMENT,
            array(
                'label' => __('Email to admin', 'plbrt-limit-login-attempts'),
                'name'  => 'notify_on_lockout_email_to_admin'
            )
        );

        $notify_on_lockout->add_element(
            Element::NUMBER_ELEMENT,
            array(
                'label'    => __('After lockouts', 'plbrt-limit-login-attempts'),
                'name'     => 'notify_after_lockouts',
                'validate' => array(Utils::class, 'is_greater_than_zero')
            )
        );
    }

    /**
     * Register the Lockout Logs section.
     */
    private function register_lockout_logs()
    {
        $lockout_log_section = $this->register_presentation_section(
            'lockout_log',
            array(
                'title' => __('Lockout log', 'plbrt-limit-login-attempts')
            )
        );

        $log_field = $lockout_log_section->add_field(
            array('label' => __('Log', 'plbrt-limit-login-attempts'))
        );

        $log_field->add_element(
            Element::CUSTOM_ELEMENT,
            array(
                'html' => $this->lockout_logs
            )
        );
    }

    /**
     * Register sections.
     */
    public function register_sections()
    {
        $this->register_statistics();
        $this->register_general_options();
        $this->register_lockout_logs();
    }
}
