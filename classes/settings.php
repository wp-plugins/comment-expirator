<?php

  /**
   * Settings
   * 
   * Settings class for the plugin Comment Expirator
   * 
   * @package CommentExpirator
   * @author Andreas Färnstrand <andreas@farnstranddev.se>
   * 
   */

  namespace CommentExpirator;

  class Settings {
    
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $disallowed_posttypes = array();

    /**
     * Start up
     */
    public function __construct() {

      
      add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
      add_action( 'admin_init', array( $this, 'page_init' ) );

    }


    public static function get_options() {

      return get_option( 'comment_expirator' );

    }


    /**
     * Add options page
     */
    public function add_plugin_page() {
      
      add_options_page(
          'Settings Admin', 
          __( 'Comment Expirator', COMMENT_EXPIRATOR_TEXTDOMAIN ), 
          'manage_options', 
          'comment-expirator', 
          array( $this, 'create_admin_page' )
      );

    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
      
      // Set class property
      $this->options = get_option( 'comment_expirator' );
      ?>
      <div class="wrap">
          <?php screen_icon(); ?>
          <h2><?php _e( 'Comment Expirator Settings', COMMENT_EXPIRATOR_TEXTDOMAIN ); ?></h2>           
          <form method="post" action="options.php">
          <?php
              // This prints out all hidden setting fields
              settings_fields( 'comment_expirator_posttypes_group' );   
              do_settings_sections( 'comment-expirator' );
              submit_button(); 
          ?>
          </form>
      </div>
      <?php

    }

    /**
     * Register and add settings
     */
    public function page_init() {        
      
      register_setting(
        'comment_expirator_posttypes_group', // Option group
        'comment_expirator', //Option name
        array( $this, 'sanitize' ) // Sanitize
      );

      /* SECTION POSTTYPES */

      add_settings_section(
        'posttypes', // ID
        __( 'Post Types', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title
        array( $this, 'print_section_info' ), // Callback
        'comment-expirator' // Page
      );

      add_settings_field(
        'allowed_posttypes', // ID
        __( 'Check the post types you wish to display the comment expiration options on', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title 
        array( $this, 'id_number_callback' ), // Callback
        'comment-expirator', // Page
        'posttypes' // Section           
      );

      add_settings_field(
        'disable_pingbacks_and_trackbacks', // ID
        __( 'Disable trackbacks and pingbacks by default', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title 
        array( $this, 'pingbacks_and_trackbacks_callback' ), // Callback
        'comment-expirator', // Page
        'posttypes' // Section           
      );

    }


    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {

      $new_input = array();
      if( isset( $input['allowed_posttypes'] ) ) {

        if( count( $input['allowed_posttypes'] ) > 0 ) {

          foreach( $input['allowed_posttypes'] as $key => $post_type ) {

            $new_input['allowed_posttypes'][$key] = esc_attr( $post_type );

          }

        }

      }

      if( !empty( $input['disable_pt'] ) ) {
        $new_input['disable_pt'] = true;
      }

      return $new_input;
      
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        
        print __( 'Enter your settings below:', COMMENT_EXPIRATOR_TEXTDOMAIN );

    }


    public function pingbacks_and_trackbacks_callback() {

      $options = self::get_options();
      $disable_pt = !empty( $options['disable_pt'] ) ? true : false;

      $checked = '';
      if( $disable_pt ) $checked = ' checked="checked" '; 

      echo sprintf( '<input type="checkbox" name="comment_expirator[disable_pt]" %s/>', $checked );

    }


    /**
     * id_number_callback
     * 
     * Callback for the posttypes allowed
     */
    public function id_number_callback() {

      // Get all valid public post types
      $post_types = get_post_types( array( 'public' => true ) );

      $options = get_option( 'comment_expirator' );

      if( count( $post_types ) > 0 ) {
        
        foreach( $post_types as $post_type ) {

          if( !in_array( $post_type, $this->disallowed_posttypes ) ) {

          	if( post_type_supports( $post_type, 'comments' ) ) {

            	$checked = '';
            	if( isset( $options['allowed_posttypes'] ) && is_array( $options['allowed_posttypes'] ) && count( $options['allowed_posttypes'] ) > 0 ) {
              	if( in_array( $post_type, $options['allowed_posttypes'] ) ) {
              
                	$checked = 'checked="checked"';
            
              	}
            	}

            	echo sprintf( '<input type="checkbox" name="comment_expirator[allowed_posttypes][%s]" value="%s" %s /> %s<br />', esc_attr( $post_type ), esc_attr( $post_type ), $checked, esc_attr( $post_type ) );

            }

          }
        
        }

      }

    }

  }


?>