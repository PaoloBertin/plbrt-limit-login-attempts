<?php

namespace plbrt\limit_login_attempts\Tables;

use plbrt\limit_login_attempts\Options\Options;

if (! defined('ABSPATH')) {
    exit;
}

class Lockout_Logs_Table extends Table
{

    /**
     * @var Options An instance of `Options`.
     */
    private $options;

    /**
     * Lockout_Logs_Table constructor.
     *
     * @param Options $options An instance of `Options`.
     */
    public function __construct($options)
    {
        parent::__construct();

        $this->options = $options;
    }

    /**
     * Return the rows.
     *
     * @return array
     */
    protected function get_rows()
    {
        $lockouts_logs = $this->options->get('lockout_logs');

        $rows = array();

        foreach ($lockouts_logs as $ip_address => $lockouts) {
            foreach ($lockouts as $username => $number_of_lockouts) {
                $rows[] = array(
                    'ip_address' => $ip_address,
                    'username'   => $username,
                    'lockouts'   => $number_of_lockouts
                );
            }
        }

        return $rows;
    }

    /**
     * Return the columns.
     *
     * @return array
     */
    protected function get_cols()
    {
        return array(
            'ip_address' => __('IP address', 'plbrt-limit-login-attempts'),
            'username'   => __('Tried to login as', 'plbrt-limit-login-attempts'),
            'lockouts'   => __('Lockout(s)', 'plbrt-limit-login-attempts')
        );
    }

    /**
     * Return the CSS classes of this table.
     *
     * @return array
     */
    protected function get_css_classes()
    {
        return array('lockout-logs');
    }
}
