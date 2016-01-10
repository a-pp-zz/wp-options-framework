<?php
/**
 * 
 * @package WP_Options_Framework
 * @since 2.7
 * 
 **/

if ( !class_exists ('WP_Options_Framework') ) {

	class WP_Options_Framework {
		
		private $fields = array ();
		private $tabs   = array ();
		private $title;

		private $option_key;
		private $flush_cache;
		private $show_messages;
		private $init = FALSE;

		public $page_name;
		
		public function __construct ( 
			$title         = 'My Options Page',
			$option_key    = 'my_options',
			$page_name     = NULL,
			$show_messages = FALSE,
			$flush_cache   = FALSE
									) {
			
			$this->option_key    = $option_key;			
			$this->title         = $title;
			$this->page_name     = $page_name;
			$this->show_messages = $show_messages;
			$this->flush_cache   = $flush_cache;				
		}

		public function factory ( $title, $option_key, $page_name, $show_messages, $flush_cache ) {
			return new WP_Options_Framework ( $title, $option_key, $page_name, $show_messages, $flush_cache );
		}

		public function init () {
			if ( empty ($this->tabs) || empty ($this->fields) )
				return FALSE;
			$this->init = TRUE;
			add_action( 'admin_init', array( &$this, 'register_fields' ) );		
			add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );				
			if ( !empty ($this->page_name) )
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

			if ( $this->show_messages )
				add_action( 'admin_notices', array(&$this, 'admin_notices') );						

			foreach ( $this->tabs as $tab_id=>$tab) {
				$option = $this->option_key . '_' . $tab_id;

				if ( $this->flush_cache ) {
					wp_cache_delete( $option, 'options' );
				}

				if ( ! get_option( $option ) ) {
					add_option ($option);
					$this->initialize_fields ($tab_id);
				}				
			}
		}
		
		public function admin_menu () {
			if ( !empty ($this->page_name) ) {
				add_submenu_page ( $this->page_name, $this->title, $this->title, 'manage_options', $this->option_key, array (&$this, 'display_page') ); 		
			}
		}

	  	public function admin_enqueue_scripts() {
	          wp_enqueue_script('jquery');
	          wp_enqueue_script('media-upload');
	          wp_enqueue_script('thickbox');
	          wp_enqueue_style('thickbox');          

	    	  wp_enqueue_script('wp-color-picker');          
	    	  wp_enqueue_style('wp-color-picker');		

	    	  $assets_dir = dirname (__FILE__) . '/assets/';
	    	  $assets_url = get_bloginfo('url') . '/' . str_replace (ABSPATH, '', $assets_dir);

			  wp_enqueue_script('maskedinput', $assets_url . 'jquery.maskedinput.min.js', array ('jquery') );
			  wp_enqueue_script('maskedinput-handler', $assets_url . 'maskedinput.handler.js', array ('jquery', 'maskedinput') );
		}		

	    public function admin_notices() {
	      	settings_errors();
	  	}		
		
		private function create_setting ( $args = array(), $tab ) {
			
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

			extract( wp_parse_args( $args, $defaults ) );
			
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
					
			add_settings_field( $id, $title, array( $this, 'display_fields' ), $option, $section, $field_args );
		}
		
		public function display_page() {

			if ( $this->init !== TRUE )
				return FALSE;

			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->get_first_tab();
			$option = $this->option_key . '_' . $tab;
			
			echo '<div class="wrap wp-options-framework">';
			$this->render_tabs();
			
			echo '<form action="options.php" method="post">';
			wp_nonce_field( 'update-options' );
			settings_fields( $option );
			do_settings_sections( $option );
			submit_button();
			
			echo '</form></div>';

			$option = $this->option_key . '_' . $tab;
			
		}
		
		public function display_fields ( $args = array() ) {
			
			extract( $args );
			$option  = $this->option_key . '_' . $tab;
			$options = get_option( $option );
			
			if ( ! isset( $options[$id] ) && $type != 'checkbox' )
				$options[$id] = $std;
			elseif ( ! isset( $options[$id] ) )
				$options[$id] = 0;
			
			$field_class = '';
			if ( $class != '' )
				$field_class = ' ' . $class;
			
			switch ( $type ) {

				case 'checkbox':
				
					echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="'.$option.'[' .$section. '][' . $id . ']" value="1" ' . checked( $options[$section][$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
				
				break;				
				
				case 'select':
					echo '<select class="select' . $field_class . '" name="'.$option.'[' .$section. '][' . $id . ']">';
					
					foreach ( $choices as $value => $label )
						echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$section][$id], $value, false ) . '>' . $label . '</option>';
					
					echo '</select>';
					
					if ( $desc != '' )
						echo '<p class="description">' . $desc . '</p>';
					
				break;
				
				case 'radio':
					$i = 0;
					foreach ( $choices as $value => $label ) {
						echo '<input class="radio' . $field_class . '" type="radio" name="'.$option.'[' .$section. '][' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$section][$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
						if ( $i < count( $choices ) - 1 )
							echo '<br />';
						$i++;
					}
					
					if ( $desc != '' )
						echo '<p class="description">' . $desc . '</p>';
					
				break;

				case 'checkboxes':
					$i = 0;
					foreach ( $choices as $value => $label ) {
						$checked = ( isset ($options[$section][$id]) && is_array ( $options[$section][$id] ) && in_array ( $value, $options[$section][$id] ) ) ? 'checked="checked"' : '';
						echo '<input class="checkbox' . $field_class . '" type="checkbox" name="'.$option.'[' .$section. '][' . $id . '][]" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . $checked . '> <label for="' . $id . $i . '">' . $label . '</label>';
						if ( $i < count( $choices ) - 1 )
							echo $args['sep'];
						$i++;
					}
					
					if ( $desc != '' )
						echo '<p class="description">' . $desc . '</p>';
					
				break;
				
				case 'textarea':
					echo '<textarea class="' . $field_class . '" id="' . $id . '" name="'.$option.'[' .$section. '][' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre( $options[$section][$id] ) . '</textarea>';
					
					if ( $desc != '' )
						echo '<p class="description">' . $desc . '</p>';
					
				break;
				
				case 'password':
					echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="'.$option.'[' .$section. '][' . $id . ']" value="' . esc_attr( $options[$section][$id] ) . '" />';
					
					if ( $desc != '' )
						echo '<p class="description">' . $desc . '</p>';
					
				break;

		        case 'editor':
		        	wp_editor( $options[$section][$id], $id, array( 'textarea_name' => $option.'[' .$section. '][' . $id . ']' ) );
		        	if($desc)  echo '<p class="description">'. $desc .'</p>';
		        break;	

			    case 'file':
	            $val = esc_attr($options[$section][$id]);
			        echo '<input type="text" name="'.$option.'[' .$section. '][' . $id . ']" id="'. $id .'" value="'. $val .'" class="regular-text'. $class .'" /> ';
	            echo '<input type="button" class="button wpsf-browse" id="'. $id .'_button" value="'.__('Browse').'" />';
	            echo '<script type="text/javascript">
	                jQuery(document).ready(function($){
	            		$(".wp-options-framework #'. $id .'_button").click(function() {
	            			tb_show("", "media-upload.php?post_id=0&amp;type=file&amp;TB_iframe=true");
	            			window.original_send_to_editor = window.send_to_editor;
	                    	window.send_to_editor = function(html) {
													$(html).filter("a").each( function(k, v){
													   $("#'. $id .'").val($(v).attr("href"));
													});                    		
	                    		tb_remove();
	                    		window.send_to_editor = window.original_send_to_editor;
	                    	};
	            			return false;
	            		});
	                });
	                </script>';
	        	break;     

				case 'color':
			 		echo '<input class="regular-text wp-color-picker' . $field_class . '" type="text" id="' . $id . '" name="'.$option.'[' .$section. '][' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$section][$id] ) . '" />';
			 		
			 		if ( $desc != '' )
			 			echo '<p class="description">' . $desc . '</p>';

			 		echo '<script type="text/javascript">
			 					jQuery(document).ready(function($){
    							$(".wp-options-framework .wp-color-picker").wpColorPicker();
								});
						</script>';
			 		
			 	break;           				
				
				case 'text':
				default:
			 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="'.$option.'[' .$section. '][' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$section][$id] ) . '" />';
			 		
			 		if ( $desc != '' )
			 			echo '<p class="description">' . $desc . '</p>';
			 		
			 	break;			 	
			}			
		}
		
		private function initialize_fields ( $tab = '' ) {			
			$option = $this->option_key . '_' . $tab;
			$default_fields = array();			
			foreach ( $this->fields[$tab] as $id => $setting ) {
				$default_fields[$setting['section']][$setting['fid']] = $setting['std'];
			}
			update_option( $option, $default_fields );			
		}
		
		public function register_fields() {
			foreach ( $this->tabs as $tab_id=>$tab) {
				$option = $this->option_key . '_' . $tab_id;				
				register_setting( $option, $option, array ( &$this, 'validate_fields' ) );

				if ( isset ( $tab['sections'] ) && is_array ( $tab['sections'] ) ) {
					foreach ( $tab['sections'] as $slug => $title ) {
							add_settings_section( $slug, $title, NULL, $option );
					}						
				}
				
				if ( isset ( $this->fields[$tab_id] ) && is_array ( $this->fields[$tab_id] ) ) {

					foreach ( $this->fields[$tab_id] as $id => $setting ) {
						$setting['id'] = $id;	
						$this->create_setting( $setting, $tab_id );
					}	
				}			
			}									
		}

		private function get_first_tab () {
			if ( $this->init !== TRUE )
				return FALSE;			
			$ret = array_shift ( (array_keys ($this->fields) ) );
			return $ret;
		}

		private function render_tabs () {
			if ( $this->init !== TRUE )
				return FALSE;			
		    $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->get_first_tab();
		    $page = !empty ($this->page_name) ? $this->page_name : '';

		    echo '<h2>' . $this->title . '</h2>';
		    echo '<h2 class="nav-tab-wrapper">';
		    foreach ( $this->tabs as $tab_key => $tab ) {
		        $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
		        $args = array ('page'=>$this->option_key, 'tab'=>$tab_key);
		        $option_url = $page . '?' . http_build_query($args);
		        echo '<a class="nav-tab ' . $active . '" href="'. $option_url . '">' . $tab['name'] . '</a>';
		    }
		    echo '</h2>';			
		}

		public function validate_field ( $field_value = '', $validator = '' ) {
			if ( is_array ($validator) 
						&& isset ($validator[0]) 
						&& isset ($validator[1]) 
						&& method_exists ( $validator[0], $validator[1] ) ) {

						$field_value = call_user_func ( $validator, $field_value);
			}
			elseif ( is_string ($validator) && !empty ($validator) && function_exists ($validator) ) {
				    $field_value = call_user_func ( $validator, $field_value);
			}

			return $field_value;
		}

		public function validate_fields ( $input ) {

			$option = isset ($_POST) ? $_POST['option_page'] : '';

			if ( !empty ($option ) ) {
				
				$tab = str_replace ( $this->option_key . '_', '', $option);

				if ( isset ($this->fields[$tab]) ) {

					foreach ($this->fields[$tab] as $id=>$setting) {

						$options = get_option ( $option );

						$field_value = ( isset ( $setting['section'] ) && isset ( $setting['fid'] ) && isset ( $input[$setting['section']][$setting['fid']] ) ) ? $input[$setting['section']][$setting['fid']] : '';
						$validator   = isset ( $setting['validator'] ) ? $setting['validator'] : '';

						if ( is_array ( $field_value ) ) {
							foreach ( $field_value as $k=>&$v) {
								$v = $this->validate_field ( $v, $validator );								
							}
						}
						else {
							$field_value = $this->validate_field ( $field_value, $validator );	
						}

						if ( isset ($setting['type']) && $setting['type'] == 'checkbox' && isset( $options[$id] ) && !isset ( $field_value ) ) {
							$field_value = 0;
						}

						$input[$setting['section']][$setting['fid']] = $field_value;	
						
					}
				}	
			}

			return $input;
			
		}

		public static function formatDate ( $date ) {
			if ( preg_match ('#(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}\:\d{2}\:\d{2})#iu', $date, $pr) )
				return ($pr[3] . '-' . $pr[2] . '-' . $pr[1] . ' ' . $pr[4] );
			else
				return $date;
		}

		public static function Get ( $option_path, $option_key ) {    
		    if ( !$option_path )
		        return NULL;
		    $array = get_option( $option_key );
		    $segments = explode('.', $option_path);
		    $cur = &$array;
		    foreach ($segments as $segment) {
		        if ( !isset ($cur[$segment]) )
		            return NULL;
		        $cur = $cur[$segment];
		    }
		    return $cur;
		}	

		public static function GetAll ( $option_key ) {			
			return ( get_option( $option_key ) );
		}		

		public function addTab ( $name = 'Options', $slug = 'options', array $sections = array () ) {
			if ( !array_key_exists($slug, $this->tabs) )
				$this->tabs[$slug] = array ( 'name'=>$name, 'sections'=>$sections );
		}

		public function addFields ( $tab, array $fields ) {
			if ( array_key_exists($tab, $this->tabs) )
				$this->fields[$tab] = $fields;
		}		

		/**
		 * Callbacks
		 */

		/* is url */
		public static function cb_url ( $val ) {
			return filter_var ($val, FILTER_VALIDATE_URL) ? $val : '';
		}	

		/* is у email */
		public static function cb_email ( $val ) {
			return is_email ( $val ) ? $val : '';
		}	

		/* sanitize text */
		public static function cb_text ( $val ) {
			return sanitize_text_field( $val );
		}		

		/* check for hex color */
		public static function cb_color ( $val ) {
			$test = preg_match ( '/#[a-f0-9]{2}[a-f0-9]{2}[a-f0-9]{2}/iu', $val );
			return $test ? $val : '';
		}											
	}
}