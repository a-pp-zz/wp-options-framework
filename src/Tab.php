<?php
/**
 *
 * @package WP_Options\Framework
 * @since 4.3
 *
 **/
namespace AppZz\Wp\Options;
use AppZz\Helpers\Arr;

class Tab {

    public $fields;
    public $name;
    public $slug;
    public $sections;

    public function __construct ($name, $slug, array $sections = array ())
    {
        if (empty ($sections))
        {
            $sections = array ('global'=>'Основные настройки');
        }

        $this->name     = $name;
        $this->slug     = $slug;
        $this->sections = $sections;
    }

    public function addField ($fid, $title, $std = '', $type = 'text', $section = 'global', array $extra = array ())
    {
        $field = array (
            'fid'       => $fid,
            'title'     => $title,
            'type'      => $type,
            'section'   => $section,
            'std'       => $std,
            'choices'   => Arr::get ($extra, 'choices'),
            'validator' => Arr::get ($extra, 'validator'),
            'class'     => Arr::get ($extra, 'class'),
            'desc'      => Arr::get ($extra, 'desc'),
        );

        $this->fields[] = $field;

        return $this;
    }
}
