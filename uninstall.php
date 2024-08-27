<?php

namespace plbrt\limit_login_attempts;

use plbrt\limit_login_attempts\Options\WP_Options;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/Options/WP_Options.php';

foreach (WP_Options::get_option_keys() as $option_key) {
    $db_option_name = 'plbrt_limit_login_attempts_' . $option_key;
    delete_option($db_option_name);
}
