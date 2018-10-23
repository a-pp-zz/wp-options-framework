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
	private $_cap  = 'manage_options';
	private $_page_name;

	public function __construct (array $params = array ()) {

		$wp_version = $this->_get_wp_version();

		if ($wp_version[0] >= 4 AND $wp_version[0] >= 3) {

			$default = array (
				'title'         => 'My Options Page',
				'menu_title'    => 'My Menu Page',
				'option_key'    => 'my_options',
				'page_name'     => null,
				'admin_notices' => false,
				'cap'           => 'manage_options'
			);

			$params = wp_parse_args ($params, $default);

			foreach ($params as $key=>$value) {
				$this->{'_'.$key} = $value;
			}

			$this->_init();

		} else {
			wp_die ('Minimal supported version of Wordpress are 4.3, old versions not supported!');
		}
	}

	public function factory (array $params = array ())
	{
		return new Framework ($params);
	}

	public function admin_menu ()
	{
		add_submenu_page ($this->_page_name, $this->_title, $this->_menu_title, $this->_cap, $this->_option_key, array (&$this, 'display_page') );
	}

  	public function admin_enqueue_scripts ()
  	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');

		wp_enqueue_script('wp-color-picker');
		wp_enqueue_style('wp-color-picker');
	}

    public function admin_notices ()
    {
      	settings_errors ();
  	}

  	public function js_handlers ()
  	{
 		echo '
 		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(\'.wp-options-framework .wp-color-picker\').wpColorPicker();
			});

			$(\'.wp-options-framework input[data-wpsw-browse="1"]\').each(function() {
				tb_show("", "media-upload.php?post_id=0&amp;type=file&amp;TB_iframe=true");
				window.original_send_to_editor = window.send_to_editor;

            	window.send_to_editor = function(html) {
						$(html).filter("a").each( function(k, v){
						   $(this).val($(v).attr("href"));
						});
            		tb_remove();
            		window.send_to_editor = window.original_send_to_editor;
            	};
    			return false;
			});
		</script>
		';
  	}

	public function display_page()
	{
		if ( ! $this->_init) {
			return false;
		}

		$tab = ! empty ($_GET['tab']) ? $_GET['tab'] : $this->_get_first_tab();
		$option = $this->option_key . '_' . $tab;

		echo '<div class="wrap wp-options-framework">';
		$this->_render_tabs();

		echo '<form action="options.php" method="post">';

		wp_nonce_field( 'update-options' );
		settings_fields( $option );
		do_settings_sections( $option );
		submit_button();

		echo '</form></div>';

		$option = $this->option_key . '_' . $tab;
	}

	public function display_fields ($args = array())
	{
		extract( $args );
		$option  = $this->option_key . '_' . $tab;
		$options = get_option($option);

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
					echo '<input class="radio' . $field_class . '" type="radio" name="' . $option_name . '" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked($option_val, $value, false) . '> <label for="' . $id . $i . '">' . $label . '</label>';

					if ($i < count( $choices ) - 1) {
						echo '<br />';
					}

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
	        	wp_editor($options[$section][$id], $id, array('textarea_name' => $option_name));
	        break;

		    case 'file':
		        echo '<input type="text" name="' . $option_name . '" id="'. $id .'" value="'. esc_attr ($option_val) .'" class="regular-text'. $class .'" /> ';
            echo '<input type="button" data-wpsw-browse="1" class="button wpsf-browse" value="'.__('Browse').'" />';
        	break;

			case 'color':
		 		echo '<input class="regular-text wp-color-picker' . $field_class . '" type="text" id="' . $id . '" name="' . $option_name . '" placeholder="' . $std . '" value="' . esc_attr( $options[$section][$id] ) . '" />';
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

	public function register_fields()
	{
		foreach ($this->_tabs as $tab_id=>$tab)
		{
			$option = $this->_option_key . '_' . $tab_id;
			register_setting($option, $option, array ( &$this, 'validate_fields'));

			if ( isset ($tab['sections'] ) && is_array ( $tab['sections'] ) ) {
				foreach ($tab['sections'] as $slug => $title ) {
					add_settings_section ($slug, $title, null, $option);
				}
			}

			if (isset ($this->_fields[$tab_id] ) && is_array ( $this->_fields[$tab_id] ) ) {
				foreach ($this->fields[$tab_id] as $id => $setting) {
					$setting['id'] = $id;
					$this->_create_setting ($setting, $tab_id);
				}
			}
		}
	}

	public function validate_field ($field_value = '', $validator = '')
	{
		if (is_array ($validator)
					&& isset ($validator[0])
					&& isset ($validator[1])
					&& method_exists ( $validator[0], $validator[1] ) ) {

					$field_value = call_user_func ( $validator, $field_value);
		}
		elseif (is_string ($validator) && ! empty ($validator) && method_exists ('Validation', $validator) ) {
			$field_value = call_user_func (array ('Validation', $validator), $field_value);
		}

		return $field_value;
	}

	public function validate_fields ($input)
	{
		$option = Arr::get ($_POST, 'option_page', '');

		if ( ! empty ($option)) {

			$tab = str_replace ($this->option_key . '_', '', $option);

			if ( isset ($this->fields[$tab]) ) {

				foreach ($this->fields[$tab] as $id=>$setting) {

					$options = get_option ($option);
					$section = Arr::get ($setting, 'section');
					$fid = Arr::get ($setting, 'fid');
					$field_value = Arr::path ($setting, $section.'.'.$fid);
					$validator = Arr::path ($setting, 'validator');
					$type = Arr::path ($setting, 'type');

					if (is_array ($field_value)) {
						foreach ( $field_value as $k=>&$v) {
							$v = $this->validate_field ($v, $validator);
						}
					}
					else {
						$field_value = $this->validate_field ($field_value, $validator);
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

	public function add_tab ($name = 'Options', $slug = 'options', array $sections = array ())
	{
		if ( ! array_key_exists($slug, $this->_tabs)) {
			$this->_tabs[$slug] = array ('name'=>$name, 'sections'=>$sections);
		}
	}

	public function add_fields ($tab, array $fields)
	{
		if (array_key_exists($tab, $this->_tabs)) {
			$this->_fields[$tab] = $fields;
		}
	}

	public static function get_option ($option_key, $path = null, $default = array ())
	{
		$ret = get_option ($option_key, $default);

		if ( ! $ret) {
			return false;
		}

		if (empty($path)) {
			return $ret;
		} else {
			return Arr::path ($ret, $path);
		}
	}

	public static function flush_cache ($option_key = '', $tabs = array ())
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

	private function _get_wp_version ()
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

	private function _init ()
	{
		if (empty ($this->tabs) OR empty ($this->fields)) {
			return false;
		}

		$this->_init = true;
		add_action('admin_init', array( &$this, 'register_fields'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		add_action('wp_footer', array(&$this, 'js_handlers'), 9999);

		if ( ! empty ($this->_page_name)) {
			add_action('admin_menu', array(&$this, 'admin_menu'), 9999);
		}

		if ($this->_admin_notices) {
			add_action('admin_notices', array(&$this, 'admin_notices'));
		}

		foreach ($this->_tabs as $tab_id=>$tab) {
			$option = $this->option_key . '_' . $tab_id;

			if ( ! get_option( $option ) ) {
				add_option ($option);
				//$this->_initialize_fields ($tab_id);
			}
		}
	}

	private function _create_setting ($args = array(), $tab = '')
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

		$option = $this->option_key . '_' . $tab;

		add_settings_field ($id, $title, array( $this, 'display_fields' ), $option, $section, $field_args);
	}

	private function _get_first_tab ()
	{
		if ( ! $this->init) {
			return false;
		}

		$ret = array_shift ((array_keys ($this->_fields)));
		return $ret;
	}

	private function _render_tabs ()
	{
		if ( ! $this->init) {
			return false;
		}

	    $current_tab = ! empty ($_GET['tab']) ? $_GET['tab'] : $this->_get_first_tab();
	    $page = ! empty ($this->_page_name) ? $this->_page_name : '';

	    echo '<h2>' . $this->_title . '</h2>';

	    if (count($this->tabs) > 1):
		    echo '<h2 class="nav-tab-wrapper">';

		    foreach ($this->tabs as $tab_key => $tab ) :
		        $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
		        $args = array ('page'=>$this->_option_key, 'tab'=>$tab_key);
		        $option_url = $page . '?' . http_build_query($args);
		        echo '<a class="nav-tab ' . $active . '" href="'. $option_url . '">' . $tab['name'] . '</a>';
		    endforeach;

		    echo '</h2>';
	    endif;
	}

	private function _initialize_fields ($tab = '')
	{
		$option = $this->option_key . '_' . $tab;
		$default_fields = array();

		foreach ($this->_fields[$tab] as $id => $setting)
		{
			$default_fields[$setting['section']][$setting['fid']] = $setting['std'];
		}

		update_option ($option, $default_fields);
	}
}
