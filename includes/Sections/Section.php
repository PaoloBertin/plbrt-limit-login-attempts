<?php

namespace plbrt\limit_login_attempts\Sections;

use plbrt\limit_login_attempts\Options\Options;
use plbrt\limit_login_attempts\Sections\Fields\Field;

if (! defined('ABSPATH')) {
    exit;
}

class Section
{

    /**
     * @var Field[] Section field objects.
     */
    protected $fields = array();

    /**
     * @var Options An instance of `Options`.
     */
    private $options;

    /**
     * @var string Section title.
     */
    private $title;

    /**
     * @var string Section ID.
     */
    private $id;

    /**
     * @var string Slug-name of the settings page this section belongs to.
     */
    private $page;

    /**
     * @var string Section description.
     */
    private $description;

    /**
     * Section constructor.
     *
     * @param string  $id               Section ID.
     * @param string  $page             Slug-name of the settings page.
     * @param Options $options_instance An instance of `Options`.
     * @param array   $properties       Properties.
     */
    public function __construct($id, $page, $options_instance, $properties = array())
    {
        $properties = wp_parse_args(
            $properties,
            array(
                'title'       => __('Section', 'plbrt-limit-login-attempts'),
                'description' => ''
            )
        );

        $this->options = $options_instance;

        $this->title       = $properties['title'];
        $this->description = $properties['description'];
        $this->page        = $page;
        $this->id          = $id;

        add_settings_section(
            $id,
            $this->title,
            array($this, 'print_description'),
            $page
        );
    }

    /**
     * Print the section description.
     */
    public function print_description()
    {
        echo esc_html($this->description);
    }

    /**
     * Create and add a new field object to this section.
     *
     * @param array $properties Field properties.
     */
    public function add_field($properties)
    {
        $field = new Field($this->id, $this->page, $this->options, $properties);

        $this->fields[] = $field;

        return $field;
    }
}
