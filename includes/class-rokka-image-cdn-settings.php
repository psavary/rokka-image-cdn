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


    /**
     * @var Class_Rokka_Mass_Upload_Images
     */
    private $rokka_mass_upload;


        public function __construct ( $parent,  $rokka_mass_upload ) {

        $this->rokka_mass_upload = $rokka_mass_upload;
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
        //add progessbar for mass upload
        wp_enqueue_script( 'jquery-ui-progressbar' );
        wp_enqueue_style( 'rokka-jquery-ui', ROKKA_PLUGIN_PATH. '/assets/css/jquery-ui.min.css');
        wp_enqueue_style('rokka-jquery-ui');
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
                        'id' 			=> 'domain',
                        'label'			=> __( 'Rokka url' , 'rokka-image-cdn' ),
                        'description'	=> __( 'The domain where rokka images are stored. Don\'t change this value unless you know what you are doing', 'rokka-image-cdn' ),
                        'type'			=> 'url',
                        'default'		=> 'rokka.io',
                        'disabled'      => 'disabled',
                    ),
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
                if ($section === 'standard'){
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
        ?>
        <a href="#bla" class="button button-primary" id="create-rokka-stacks" ><?php echo esc_attr( __( 'Create stacks on Rokka' , 'rokka-image-cdn'));  ?></a>
        <div id="progress_info_stacks"></div>
        <br />
        <a href="#bla" class="button button-primary" id="mass-upload-everything" ><?php echo esc_attr( __( 'Upload images to Rokka' , 'rokka-image-cdn'));  ?></a>
        <div id="progressbar"></div>
        <div id="progress_info"></div>

        <?php
	}


	public function mass_upload_page () {


       ?>
        <script type="text/javascript" >
            jQuery(document).ready(function($) {



				$('#mass-upload-everything').click(function(e) {
                    console.log('start mass upload'); //todo remove


                    var image_ids_to_upload = <?php echo json_encode($this->rokka_mass_upload->get_images_for_upload()); ?>;
                    console.log(image_ids_to_upload);
                    console.log(image_ids_to_upload.length);
                    image_ids_to_upload = Object.keys(image_ids_to_upload).map(function(k) { return image_ids_to_upload[k] });
                    console.log(image_ids_to_upload);
                    console.log(image_ids_to_upload.length);


                    var progress_fraction = 100 / image_ids_to_upload.length;
                    var progress_step = 0;
                    if (image_ids_to_upload.length > 0) {
                        $("#progressbar").progressbar({
                            value: 0
                        });
                        $('#progress_info').append("<br />");
                        rokka_upload_image(image_ids_to_upload);
                        function rokka_upload_image(image_ids_to_upload) {
                            if (image_ids_to_upload.length > 0) {
                                var image_id = image_ids_to_upload.shift();
                                $.ajax({
                                    type: 'POST',
                                    url: ajaxurl,
                                    data: {action: 'rokka_upload_image', id: image_id},
                                    success: function (response) {
                                        console.log(progress_step * progress_fraction); //todo remove
                                        progress_step += 1;
                                        $("#progressbar").progressbar({
                                            value: progress_step * progress_fraction
                                        });
                                        $('#progress_info').append("uploaded of image id " + image_id + " successful <br />");
                                        rokka_upload_image(image_ids_to_upload);
                                    },
                                    error: function (response) {
                                        console.log(progress_step * progress_fraction); //todo remove
                                        console.log(response); //todo remove

                                        progress_step += 1;
                                        $("#progressbar").progressbar({
                                            value: progress_step * progress_fraction
                                        });
                                        $('#progress_info').append("uploaded of image id " + image_id + " failed! <br />");

                                        rokka_upload_image(image_ids_to_upload);
                                    }
                                });
                            }
                            else {
                                $('#progress_info').append("image upload done! <br />");
                            }
                        }
                    }
                    else {
                        $('#progress_info').append("Nothing to process here, all images are already uploaded to Rokka.<br />");
                    }
                });

                $('#create-rokka-stacks').click(function(e) {
                    $.ajax({
                        type: 'GET',
                        url: ajaxurl,
                        data: {action: 'rokka_create_stacks'},
                        success: function (response) {
                            $('#progress_info_stacks').append("stack creation successful! <br />");

                        },
                        error: function (response) {
                            $('#progress_info_stacks').append("stack creation failed! <br />");
                        }
                    });
                });
            });
        </script>
        <?php
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
	public static function instance ( $parent, $mass_upload ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent , $mass_upload);
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
