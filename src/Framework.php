<?php
/**
 *
 * @package WP_Options\Framework
 * @since 4.3
 *
 **/
namespace AppZz\Wp\Options;
use AppZz\Helpers\Arr;

class Framework {

	private $_fields = array ();
	private $_tabs   = array ();

	private $_init = false;
	private $_title;
	private $_menu_title;
	private $_option_key;
	private $_admin_notices;
	private $_cap;
	private $_page_name;

	private $_colorpicker;
	private $_datepicker;
	private $_mediaupload;

	const VERSION = '2.0.5';

	public function __construct (array $params = array ()) {

		$wp_version = $this->_getWpVersion();

		if ($wp_version[0] >= 4 AND $wp_version[0] >= 3) {

			$default = array (
				'title'         => 'My Options Page',
				'option_key'    => 'my_options',
				'page_name'     => 'options-general.php',
				'admin_notices' => false,
				'cap'           => 'manage_options'
			);

			$params = wp_parse_args ($params, $default);

			foreach ($params as $key=>$value) {
				$this->{'_'.$key} = $value;
			}

			if (empty($this->_menu_title)) {
				$this->_menu_title = $this->_title;
			}

			if ($this->_page_name == 'options-general.php') {
				$this->_admin_notices = false;
			}

		} else {
			wp_die ('Minimal supported version of Wordpress are 4.3, old versions not supported!');
		}
	}

	public function factory (array $params = array ())
	{
		return new Framework ($params);
	}

	public function Init ()
	{
		if (empty ($this->_tabs) OR empty ($this->_fields)) {
			return false;
		}

		$this->_init = true;
		add_action('admin_init', array( &$this, 'registerFields'));
		add_action('admin_enqueue_scripts', array(&$this, 'addScripts'));
		add_action('admin_footer', array(&$this, 'renderJS'), 9999);

		if ( ! empty ($this->_page_name)) {
			add_action('admin_menu', array(&$this, 'setMenu'), 9999);
		}

		if ($this->_admin_notices) {
			add_action('admin_notices', array(&$this, 'setNotices'));
		}

		foreach ($this->_tabs as $tab_id=>$tab) {
			$option = $this->_option_key . '_' . $tab_id;

			if ( ! get_option( $option ) ) {
				add_option ($option);
			}
		}
	}

	public function setMenu ()
	{
		add_submenu_page ($this->_page_name, $this->_title, $this->_menu_title, $this->_cap, $this->_option_key, array (&$this, 'renderPage') );
	}

  	public function addScripts ()
  	{
		wp_enqueue_script('jquery');

		if ($this->_mediaupload) {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
		}

		if ($this->_colorpicker) {
			wp_enqueue_script('wp-color-picker');
			wp_enqueue_style('wp-color-picker');
		}

		if ($this->_datepicker) {
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_style('jqueryui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css', false, null );
		}
	}

    public function setNotices ()
    {
      	settings_errors ();
  	}

  	public function renderJS ()
  	{
 		echo '
 		<style type="text/css">
 			.wp-options-framework label {
 				margin-right: 10px;
 			}

 			.wp-options-framework-copyright {
 				font-style: italic;
 				font-weight: bold;
 			}
 		</style>
 		<script type="text/javascript">
			jQuery(document).ready(function($) {

				if ($.isFunction($.fn.wpColorPicker)) {
					$(\'.wp-options-framework .wp-color-picker\').wpColorPicker();
				}

				if ($.isFunction($.fn.datepicker)) {
					$(\'.wp-options-framework .wp-date-picker\').datepicker({ dateFormat: \'yy-mm-dd\' });
				}

				$(\'.wp-options-framework .wpsf-browse\').click(function() {
				    var receiver = $(this).prev("input");
				    tb_show("", "media-upload.php?post_id=0&amp;type=file&amp;TB_iframe=true");

				    window.original_send_to_editor = window.send_to_editor;

				    window.send_to_editor = function(html) {

			            $(html).filter("a").each( function(k, v) {
			                $(receiver).val($(v).attr("href"));
			            });

			            $(html).filter("img").each( function(k, v) {
			                $(receiver).val($(v).attr("src"));
			            });

				        tb_remove();
				        window.send_to_editor = window.original_send_to_editor;
				    }

				    return false;
				});
			});
		</script>
		';
  	}

	public function renderPage ()
	{
		if ( ! $this->_init) {
			return false;
		}

		$tab = Arr::get ($_GET, 'tab', $this->_getFirstTab());
		$option = $this->_getOptionKey($tab);

		echo '<div class="wrap wp-options-framework">';
		$this->_renderTabs();

		echo '<form action="options.php" method="post">';

		wp_nonce_field( 'update-options' );
		settings_fields( $option );
		do_settings_sections( $option );
		submit_button();

		echo '</form></div>';
		echo '<p class="wp-options-framework-copyright"><small>Powered by WP Options Framework v'.Framework::VERSION.'</small></p>';
	}

	public function displayFields ($args = array())
	{
		extract( $args );
		$option  = $this->_option_key . '_' . $tab;
		$options = (array)get_option($option, array());

		if ( ! isset($options[$id] ) AND $type != 'checkbox') {
			$options[$id] = $std;
		}
		elseif ( ! isset($options[$id])) {
			$options[$id] = 0;
		}

		$field_class = '';

		if ( ! empty ($class)) {
			$field_class = ' ' . $class;
		}

		if ( ! isset($std)) {
			$std = null;
		}

		$option_val  = Arr::path ($options, $section.'.'.$id, '.', $std);
		$option_name = $option.'[' .$section. '][' . $id . ']';

		switch ($type) :

			case 'checkbox':
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="' . $option_name . '" value="1" ' . checked($option_val, 1, false) . ' /> <label for="' . $id . '">' . $desc . '</label>';
			break;

			case 'select':
				echo '<select class="select' . $field_class . '" name="' . $option_name . '">';

				foreach ($choices as $value => $label) :
					echo '<option value="' . esc_attr( $value ) . '"' . selected($option_val, $value, false) . '>' . $label . '</option>';
				endforeach;

				echo '</select>';
			break;

			case 'radio':
				$i = 0;
				foreach ($choices as $value => $label) :
					echo '<label><input class="radio' . $field_class . '" type="radio" name="' . $option_name . '" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked($option_val, $value, false) . '>' . $label . '</label>';
					$i++;
				endforeach;

				if ($desc) {
					echo '<p class="description">' . $desc . '</p>';
				}
			break;

			case 'checkboxes':
				$option_name .= '[]';
				$option_val = (array) $option_val;
				echo '<div class="wp-options-checkboxes">', "\n";
				foreach ( $choices as $value => $label ) {
					$checked = in_array ($value, $option_val) ? 'checked="checked"' : '';
					echo '<label><input class="checkbox' . $field_class . '" type="checkbox" name="' . $option_name . '" value="' . esc_attr ($value) . '" ' . $checked . '>' . $label . '</label>';
				}
				echo '</div>', "\n";
			break;

			case 'textarea':
				$html_esc_func = 'format_for_editor';
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="' . $option_name . '" placeholder="' . $std . '" rows="5" cols="30">' . call_user_func($html_esc_func, $option_val) . '</textarea>';
			break;

			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="' . $option_name . '" value="' . esc_attr($option_val) . '" />';
			break;

	        case 'editor':
	        	wp_editor(Arr::path($options, $section . '.' . $id), $id, array('textarea_name' => $option_name));
	        break;

		    case 'file':
		        echo '<input type="text" name="' . $option_name . '" id="'. $id .'" value="'. esc_attr ($option_val) .'" class="regular-text'. $class .'" /> ';
            echo '<input type="button" data-wpsw-browse="1" class="button wpsf-browse" value="Выбрать файл" />';
        	break;

			case 'color':
		 		echo '<input class="regular-text wp-color-picker' . $field_class . '" type="text" id="' . $id . '" name="' . $option_name . '" placeholder="' . $std . '" value="' . esc_attr( $option_val ) . '" />';
		 	break;

			case 'date':
		 		echo '<input class="regular-text wp-date-picker' . $field_class . '" type="text" id="' . $id . '" name="' . $option_name . '" placeholder="' . $std . '" value="' . esc_attr( $option_val ) . '" />';
		 	break;

			case 'text':
			default:
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="' . $option_name . '" placeholder="' . $std . '" value="' . esc_attr($option_val) . '" />';
		 	break;
		endswitch;

		if (! empty ($desc)) {
			echo '<p class="description">' . $desc . '</p>';
		}
	}

	public function registerFields ()
	{
		foreach ($this->_tabs as $tab_id=>$tab)
		{
			$option = $this->_getOptionKey($tab_id);
			register_setting($option, $option, array ( &$this, 'validateFields'));

			if ( isset ($tab['sections'] ) && is_array ( $tab['sections'] ) ) {
				foreach ($tab['sections'] as $slug => $title ) {
					add_settings_section ($slug, $title, null, $option);
				}
			}

			if (isset ($this->_fields[$tab_id] ) && is_array ( $this->_fields[$tab_id] ) ) {
				foreach ($this->_fields[$tab_id] as $id => $setting) {
					$setting['id'] = $id;
					$this->_createField ($setting, $tab_id);
				}
			}
		}
	}

	public function validateFields ($input)
	{
		$option = Arr::get ($_POST, 'option_page', '');

		if ( ! empty ($option)) {

			$tab = str_replace ($this->_option_key . '_', '', $option);

			if ( ! empty ($this->_fields[$tab])) {

				foreach ($this->_fields[$tab] as $id=>$setting) {

					$options     = get_option ($option);
					$section     = Arr::get ($setting, 'section');
					$fid         = Arr::get ($setting, 'fid');
					$field_value = Arr::path ($input, $section.'.'.$fid);
					$validator   = Arr::path ($setting, 'validator');
					$type        = Arr::path ($setting, 'type');

					if (is_array ($field_value)) {
						foreach ( $field_value as $k=>&$v) {
							$v = $this->_validateField ($v, $validator);
						}
					}
					else {
						$field_value = $this->_validateField ($field_value, $validator);
					}

					if ($type == 'checkbox' && ! $field_value) {
						$field_value = 0;
					}

					$input[$setting['section']][$setting['fid']] = $field_value;
				}
			}
		}

		return $input;
	}

	public function addTab (Tab $tab)
	{
		if ( ! array_key_exists($tab->slug, $this->_tabs)) {
			$this->_tabs[$tab->slug] = array ('name'=>$tab->name, 'sections'=>$tab->sections);
			$this->_addFields ($tab->slug, $tab->fields);
		}
	}

	public function getAll ($path = NULL)
	{
		$ret = array ();

		if ( ! empty ($this->_tabs)) {
			$tabs = array_keys ($this->_tabs);

			foreach ($tabs as $tab) {
				$option_key = $this->_option_key . '_' . $tab;
				$ret[$tab] = get_option ($option_key);
			}
		}

		if ( ! empty ($ret) AND ! empty ($path)) {
			$ret = Arr::path ($ret, $path);
		}

		return $ret;
	}

	public static function Get ($section = NULL, $option_key, $default = NULL)
	{
	    if (empty ($option_key)) {
	        return false;
	    }

	    $ret = get_option ($option_key, $default);

	    if ($section) {
	    	return Arr::path ($ret, $section, '.', $default);
	    }

	    return $ret;
	}

	public static function getOption ($option_key, $tab, $section = null, $option = null, $default = array ())
	{
		if (empty($option_key) OR empty($tab)) {
			return false;
		}

		$option_key .= '_' . $tab;

		$ret = get_option ($option_key, $default);

		if ( ! $ret) {
			return false;
		}

		if ( ! empty($section)) {
			if ( ! empty ($option)) {
				$section .= '.' . $option;
			}
			return Arr::path ($ret, $section);
		}

		return $ret;
	}

	public static function flushCache ($option_key = '', $tabs = array ())
	{
		$tabs = (array) $tabs;

		if ( ! empty ($option_key) AND ! empty ($tabs)) {
			foreach ($tabs as $tab_id) {
				$option = $option_key . '_' . $tab_id;
				wp_cache_delete($option, 'options');
			}

			return true;
		}

		return false;
	}

	private function _getWpVersion ()
	{
		$version_str = file_get_contents (ABSPATH . '/wp-includes/version.php');
		$regex = "wp_version.*'(?<wp_version>.*)'";

		if (preg_match('#'.$regex.'#iu', $version_str, $matches)) {
		 	$ver_str = $matches['wp_version'];

		 	if (strpos ($ver_str, '.')) {
		 		return explode ('.', $ver_str);
		 	}
		 	else {
		 		return array ($ver_str, 0, 0);
		 	}
		}

		return array (0, 0, 0);
	}

	private function _addFields ($tab, array $fields)
	{
		if (array_key_exists($tab, $this->_tabs)) {
			$this->_fields[$tab] = $fields;

			foreach ($fields as $f) {
				$type = Arr::get ($f, 'type');

				switch ($type) {
					case 'color':
						$this->_colorpicker = true;
					break;

					case 'date':
						$this->_datepicker = true;
					break;

					case 'file':
						$this->_mediaupload = true;
					break;
				}
			}
		}
	}

	private function _validateField ($field_value = '', $validator = '')
	{
		if (is_string ($validator) AND ! empty ($validator)) {

			if (strpos ($validator, ':') !== false) {
				list ($class, $method) = explode ('::', $validator);

				if (method_exists ($class, $method)) {
					$field_value = call_user_func (array ($class, $method), $field_value);
				}
			}
			elseif (function_exists ($validator)) {
				$field_value = call_user_func ($validator, $field_value);
			}
		}

		return $field_value;
	}

	private function _createField ($args = array(), $tab = '')
	{
		$defaults = array(
			'id'        => 'default_field',
			'fid'       => 'default_field_id',
			'title'     => 'default field',
			'desc'      => 'This is a default description.',
			'std'       => '',
			'type'      => 'text',
			'section'   => 'general',
			'choices'   => array(),
			'class'     => '',
			'validator' => '',
			'sep'       => ''
		);

		extract(wp_parse_args($args, $defaults));

		$field_args = array(
			'type'      => $type,
			'id'        => $fid,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $fid,
			'class'     => $class,
			'sep'       => $sep,
			'section'   => $section,
			'tab'       => $tab,
			'validator' => $validator
		);

		add_settings_field ($id, $title, array( $this, 'displayFields' ), $this->_getOptionKey ($tab), $section, $field_args);
	}

	private function _getFirstTab ()
	{
		if ( ! $this->_init) {
			return false;
		}

		$keys = array_keys ($this->_fields);
		return array_shift ($keys);
	}

	private function _getOptionKey ($tab_id)
	{
		return $this->_option_key . '_' . $tab_id;
	}

	private function _renderTabs ()
	{
		if ( ! $this->_init) {
			return false;
		}

	    $current_tab = ! empty ($_GET['tab']) ? $_GET['tab'] : $this->_getFirstTab();
	    $page = ! empty ($this->_page_name) ? $this->_page_name : '';

	    echo '<h2>' . $this->_title . '</h2>';

	    if (count($this->_tabs) > 1):
		    echo '<h2 class="nav-tab-wrapper">';

		    foreach ($this->_tabs as $tab_key => $tab ) :
		        $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
		        $args = array ('page'=>$this->_option_key, 'tab'=>$tab_key);
		        $option_url = $page . '?' . http_build_query($args);
		        echo '<a class="nav-tab ' . $active . '" href="'. $option_url . '">' . $tab['name'] . '</a>';
		    endforeach;

		    echo '</h2>';
	    endif;
	}
}
