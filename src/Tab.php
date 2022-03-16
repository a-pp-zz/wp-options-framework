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
        $validator = Arr::get ($extra, 'validator');

        if (empty($validator)) {
            switch ($type) {
                case 'datetime':
                    $validator = '\AppZz\Wp\Options\Validation::datetime_local';
                break;

                case 'date':
                    $validator = '\AppZz\Wp\Options\Validation::date';
                break;

                case 'time':
                    $validator = '\AppZz\Wp\Options\Validation::time_local';
                break;
            }
        }

        $field = array (
            'fid'       => $fid,
            'title'     => $title,
            'type'      => $type,
            'section'   => $section,
            'std'       => $std,
            'choices'   => Arr::get ($extra, 'choices'),
            'validator' => $validator,
            'class'     => Arr::get ($extra, 'class'),
            'desc'      => Arr::get ($extra, 'desc'),
            'attrs'     => Arr::get ($extra, 'attrs', array()),
        );

        $this->fields[] = $field;

        return $this;
    }
}
