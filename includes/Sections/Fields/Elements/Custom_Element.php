<?php

namespace plbrt\limit_login_attempts\Sections\Fields\Elements;

use plbrt\limit_login_attempts\Interfaces\HTML;
use plbrt\limit_login_attempts\Options\Options;

if (! defined('ABSPATH')) {
    exit;
}

class Custom_Element extends Element
{

    /**
     * @var string HTML to display.
     */
    private $html = null;

    /**
     * Render the element.
     */
    public function render()
    {
?>

        <div class="custom-element">

            <?php
            if (! empty($this->html)) {
                echo $this->html;
            }
            ?>

        </div>

<?php
    }

    /**
     * Custom_Element constructor.
     *
     * @param string  $section_id       Section ID.
     * @param Options $options_instance An instance of `Options`.
     * @param array   $properties       Element properties.
     */
    public function __construct($section_id, $options_instance, $properties = array())
    {
        parent::__construct($section_id, $options_instance, $properties);

        $this->html = $properties['html'];

        if ($properties['html'] instanceof HTML) {
            $this->html = $properties['html']->get_html();
        }
    }
}
