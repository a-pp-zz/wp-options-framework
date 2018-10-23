<?php
/**
 *
 * @package WP_Options\Framework
 * @since 4.3
 *
 **/
namespace AppZz\Wp\Options;
use AppZz\Helpers\Arr;

class Validation {

    /* is url */
    public static function url ($val)
    {
        return filter_var ($val, FILTER_VALIDATE_URL) ? $val : '';
    }

    /* is у email */
    public static function email ($val)
    {
        return is_email ($val) ? $val : '';
    }

    /* sanitize text */
    public static function text ($val)
    {
        return sanitize_text_field ($val);
    }

    /* check for hex color */
    public static function color ($val)
    {
        return preg_match ('/#[a-f0-9]{2}[a-f0-9]{2}[a-f0-9]{2}/iu', $val) ? $val : '';
    }
}
