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

      $this->options = self::get_options();

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
        array( $this, 'print_posttype_section_info' ), // Callback
        'comment-expirator' // Page
      );

      add_settings_field(
        'allowed_posttypes', // ID
        __( 'Check the post types you wish to display the comment expiration options on', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title 
        array( $this, 'allowed_posttype_callback' ), // Callback
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

      /* SECTION DEFAULT DATE */

      add_settings_section(
        'default_time', // ID
        __( 'Default time', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title
        array( $this, 'print_default_date_section_info' ), // Callback
        'comment-expirator' // Page
      );

      add_settings_field(
        'default_time', // ID
        __( 'Set a default expiration time in days', COMMENT_EXPIRATOR_TEXTDOMAIN ), // Title 
        array( $this, 'default_time_callback' ), // Callback
        'comment-expirator', // Page
        'default_time' // Section           
      );

    }


    public function print_default_date_section_info() {
      
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

      if( !empty( $input['default_time'] ) ) {
        if( intval( $input['default_time'] ) > 0 ) {
          $new_input['default_time'] = (int) intval( $input['default_time'] );  
        }
      }

      return $new_input;
      
    }

    /** 
     * Print the Section text
     */
    public function print_posttype_section_info() {
        
        print __( 'Enter your settings below:', COMMENT_EXPIRATOR_TEXTDOMAIN );

    }


    public function pingbacks_and_trackbacks_callback() {

      $disable_pt = !empty( $this->options['disable_pt'] ) ? true : false;

      $checked = '';
      if( $disable_pt ) $checked = ' checked="checked" '; 

      echo sprintf( '<input type="checkbox" name="comment_expirator[disable_pt]" %s/>', $checked );

    }


    /**
     * id_number_callback
     * 
     * Callback for the posttypes allowed
     */
    public function allowed_posttype_callback() {

      // Get all valid public post types
      $post_types = get_post_types( array( 'public' => true ) );

      if( count( $post_types ) > 0 ) {
        
        foreach( $post_types as $post_type ) {

          if( !in_array( $post_type, $this->disallowed_posttypes ) ) {

          	if( post_type_supports( $post_type, 'comments' ) ) {

            	$checked = '';
            	if( isset( $this->options['allowed_posttypes'] ) && is_array( $this->options['allowed_posttypes'] ) && count( $this->options['allowed_posttypes'] ) > 0 ) {
              	if( in_array( $post_type, $this->options['allowed_posttypes'] ) ) {
              
                	$checked = 'checked="checked"';
            
              	}
            	}

              echo sprintf( '<label for="posttype-%s">', esc_attr( $post_type ) );
            	echo sprintf( '<input id="posttype-%s" type="checkbox" name="comment_expirator[allowed_posttypes][%s]" value="%s" %s /> %s', esc_attr( $post_type ), esc_attr( $post_type ), esc_attr( $post_type ), $checked, esc_attr( $post_type ) );
              echo '</label><br />';

            }

          }
        
        }

      }

    }


    /** 
     * Print the Default time section text
     */
    public function print_default_time_section_info() {
        
        print __( 'Enter :', COMMENT_EXPIRATOR_TEXTDOMAIN );

    }


    /**
     * The output for the default time setting
     */ 
    public function default_time_callback() {

      $default_time = isset( $this->options['default_time'] ) ? esc_attr( $this->options['default_time'] ) : null;

      echo '<input type="number" name="comment_expirator[default_time]" min="0" max="10000" value="' . $default_time . '" /><br />';
      echo '<p class="description">(' . __('Empty this field if you do not want a default time.', COMMENT_EXPIRATOR_TEXTDOMAIN) . ')</p>';


    }

  }


?>