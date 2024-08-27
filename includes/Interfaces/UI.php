<?php

namespace plbrt\limit_login_attempts\Interfaces;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * UI is a generic interface, meant to be used from any class of the plugin.
 */
interface UI
{
    /**
     * Render the UI element.
     *
     * @return void
     */
    public function render();
}
