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

    /* is email */
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

    /* check date */
    public static function date ($val)
    {
        if (preg_match ('#^(?<yy>\d{4})\-(?<mm>\d{2})\-(?<dd>\d{2})\s?(?<time>\d{2}\:\d{2}\:\d{2})?$#iu', $val, $parts)) {
            $mm = sprintf ('%01d', $parts['mm']);
            $dd = sprintf ('%01d', $parts['dd']);
            $yy = sprintf ('%04d', $parts['yy']);

            return checkdate ($mm, $dd, $yy) ? $val : '';
        }

        return '';
    }

    public static function datetime_local ($val)
    {
        if (preg_match ('#^(?<date>\d{4}\-\d{1,2}\-\d{1,2})T(?<time>\d{1,2}\:\d{1,2})$#iu', $val, $parts)) {
            return sprintf ('%s %s:00', $parts['date'], $parts['time']);
        }

        return '';
    }

    public static function time_local ($val)
    {
        if (preg_match ('#^\d{1,2}\:\d{1,2}$#iu', $val)) {
            return sprintf ('%s:00', $val);
        }

        return '';
    }
}
