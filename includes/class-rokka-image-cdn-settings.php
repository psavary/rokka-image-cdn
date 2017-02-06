<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Rokka_Image_Cdn_Settings {

	/**
	 * The single instance of rokka-image-cdn_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'rokka_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'Rokka Settings', 'rokka-image-cdn' ) , __( 'Rokka Settings', 'rokka-image-cdn' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
    	wp_enqueue_media();

    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'rokka-image-cdn' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['standard'] = array(
			'title'					=> __( 'Rokka ', 'rokka-image-cdn' ),
			'description'			=> __( 'Please enter your credentials below', 'rokka-image-cdn' ),
			'fields'				=> array(
				array(
					'id' 			=> 'company_name',
					'label'			=> __( 'Company name' , 'rokka-image-cdn' ),
					'description'	=> __( 'Your Company name you have registered on Rokka with', 'rokka-image-cdn' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Company' )
				),
				array(
					'id' 			=> 'api_key',
					'label'			=> __( 'API Key' , 'rokka-image-cdn' ),
					'description'	=> __( 'Rokka API key', 'rokka-image-cdn' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __('Key' )
				),
				array(
					'id' 			=> 'api_secret',
					'label'			=> __( 'API Secret' , 'rokka-image-cdn' ),
					'description'	=> __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'rokka-image-cdn' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __('Secret' )
				),
				array(
					'id' 			=> 'rokka_enabled',
					'label'			=> __( 'Enable Rokka', 'rokka-image-cdn' ),
					'description'	=> __( 'This will enable the Rokka.io functionality.', 'rokka-image-cdn' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
			)
		);

		$settings['upload'] = array(
			'title'					=> __( 'Extra', 'rokka-image-cdn' ),
			'description'			=> __( 'These are some extra input fields that maybe aren\'t as common as the others.', 'rokka-image-cdn' ),
			'fields'				=> array(
				array(
					'id' 			=> 'number_field',
					'label'			=> __( 'A Number' , 'rokka-image-cdn' ),
					'description'	=> __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'rokka-image-cdn' ),
					'type'			=> 'number',
					'default'		=> '',
					'placeholder'	=> __( '42', 'rokka-image-cdn' )
				),
				array(
					'id' 			=> 'colour_picker',
					'label'			=> __( 'Pick a colour', 'rokka-image-cdn' ),
					'description'	=> __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'rokka-image-cdn' ),
					'type'			=> 'color',
					'default'		=> '#21759B'
				),
				array(
					'id' 			=> 'an_image',
					'label'			=> __( 'An Image' , 'rokka-image-cdn' ),
					'description'	=> __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'rokka-image-cdn' ),
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'multi_select_box',
					'label'			=> __( 'A Multi-Select Box', 'rokka-image-cdn' ),
					'description'	=> __( 'A standard multi-select box - the saved data is stored as an array.', 'rokka-image-cdn' ),
					'type'			=> 'select_multi',
					'options'		=> array( 'linux' => 'Linux', 'mac' => 'Mac', 'windows' => 'Windows' ),
					'default'		=> array( 'linux' )
				)
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page

                //todo refactor this to make it more OOP
                if ($section === 'upload'){
                    add_settings_section( $section, $data['title'], array( $this, 'mass_upload_page' ), $this->parent->_token . '_settings' );

                }
                else {
                    add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

                }

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Rokka Settings' , 'rokka-image-cdn' ) . '</h2>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'rokka-image-cdn' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}


	public function mass_upload_page () {

       $html =  "	<script type=\"text/javascript\" >

       jQuery(document).ready(function($) {

            var data = {
                'action': 'mass_upload_images',
			'whatever': 1234
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
            alert('Got this from the server: ' + response);
        });
	});
        </script>";

       $html2 =  '<script type="text/javascript" >
              jQuery(document).ready(function($) {

            var data = {
                \'action\': \'mass_upload_images\',
			\'whatever\': 1234
		};
       var myTrigger;
var progressElem = $(\'#progressCounter\');
$.ajax ({
    type            : \'GET\',
    url             : ajaxurl ,
    data            : data,
    beforeSend      : function (thisXHR)
    {
        myTrigger = setInterval (function ()
        {
            if (thisXHR.readyState > 2)
            {
                console.log(thisXHR.responseText);
                var progress     = thisXHR.responseText;
                progressElem.html (progress + "%");
            }
        }, 200);
    },
    complete        : function ()
    {
        clearInterval (myTrigger);
    },
    success         : function (response)
    {
            alert(\'Got this from the server: \' + response);
    }
});
});
 </script>';

        echo $html2;

    }

	/**
	 * Main rokka-image-cdn_Settings Instance
	 *
	 * Ensures only one instance of rokka-image-cdn_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see rokka-image-cdn()
	 * @return Main rokka-image-cdn_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
