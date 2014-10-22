<?php

  /**
   * Class Comment
   * 
   * The main class for Comment Expirator plugin.
   * 
   * @package CommentExpirator
   * @author Andreas Färnstrand <andreas@farnstranddev.se>
   */

	namespace CommentExpirator;

	require_once( COMMENT_EXPIRATOR_PLUGIN_PATH . '/classes/settings.php' );
  require_once( COMMENT_EXPIRATOR_PLUGIN_PATH . '/classes/schedule.php' );

  use CommentExpirator\Schedule;
  use CommentExpirator\Settings;

	class Comment {

		public function __construct() {

      // Initialize the settings page
      if( is_admin() ) {
			 $settings = new Settings();
      }

      /*
      |-----------------------------
      | Add hooks
      |-----------------------------
      */

      add_action( 'plugins_loaded', array( $this , 'load_translations') );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) ); // Add a metabox on the admin page
      add_action( 'admin_enqueue_scripts', array( $this, 'add_plugin_resources' ) ); // Add scripts
      add_action( 'save_post', array( $this, 'save' ) ); // Filter on save post
      add_action( 'comment_expirator', array( $this, 'execute_expiration' ), 10, 1 ); // Add filter on scheduled execution


		}


    /**
     * load_translations
     * 
     * Load the correct plugin translation file
     */
    public function load_translations() {

      load_plugin_textdomain( 'comment-expirator', false, COMMENT_EXPIRATOR_TEXTDOMAIN_PATH );

    }


    /**
     * save
     * 
     * Action for save post functionality
     * Adds CommentExpirator metadata to the post
     * 
     * @param integer $post_id
     */
		public function save( $post_id ) {

			global $post, $typenow, $post_type;	

      if( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
      if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

      // Get the plugin options
      $options = Settings::get_options();

      // No options, no dice
      if( !isset( $options ) ) return $post_id;

      // Get the valid post types set in options page
      $options = !empty( $options['allowed_posttypes'] ) ? $options['allowed_posttypes'] : array();

      // Abort if not a valid post type
      if( !in_array( $post_type, $options ) ) return $post_id;

      $schedule = Schedule::fromId( $post_id );
      $old_timestamp = $schedule->date;

      $date       = isset( $_POST['expirator']['date'] ) && strlen( $_POST['expirator']['date'] ) == 10 ? sanitize_text_field( $_POST['expirator']['date'] ) : null;
      $time       = isset( $_POST['expirator']['time'] ) && strlen( $_POST['expirator']['time'] ) == 5 ? sanitize_text_field( $_POST['expirator']['time'] ) : null;
      $use        = isset( $_POST['expirator']['use'] ) ? true : false;
      $disable_pt = isset( $_POST['expirator']['pt'] ) ? 'true' : 'false';


      // Only do this if checkbox is checked and we actually have a date and time
      if( !empty( $date ) && !empty( $time ) && $use ) {

        // Abort if we cannot create a new timestamp from data
        if( !$new_timestamp = Schedule::create_timestamp( $date, $time ) ) {
          return $post_id;
        }

        // Remove old scheduled event and post meta tied to the post
        if( isset( $old_timestamp ) ) {
          $schedule->delete( $post_id );
        }

        apply_filters( 'comment_expirator_pre_scheduling', $post_id );

        // If we cannot get a real gmt time. Abort.
        if( !$gmt = Schedule::check_gmt_against_system_time( $new_timestamp ) ) return $post_id;

        apply_filters( 'comment_expirator_post_scheduling', $post_id );

        // Clear old scheduled time if there is one
        $schedule->delete();

        // Schedule a new event
        $scheduling_result = wp_schedule_single_event( $gmt, 'comment_expirator', array( $post_id ) );
        $scheduling_result = isset( $scheduling_result ) && $scheduling_result == false ? false : true;

        // Update the post meta tied to the post
        if( $scheduling_result ) {

          $schedule->run = true;
          $schedule->date = $date;
          $schedule->time = $time;
          $schedule->disable_pt = $disable_pt;
          $schedule->save();
          
          apply_filters( 'comment_expirator_scheduling_success', $post_id );

        } else {

          apply_filters( 'comment_expirator_scheduling_error', $post_id );

        }

      } else {

        // Clear the scheduled event and remove all post meta if
        // user removed the scheduling
        if( isset( $old_timestamp ) ) {
          $schedule->delete();
        }

      }

		}


    /**
     * execute expiration
     * 
     * The function that gets executed upon scheduled cron.
     * Updates the post and closes commenting and possibly
     * also pingbacks and trackbacks.
     * 
     * @param integer $post_id
     */
    public function execute_expiration( $post_id ) {

      $schedule = Schedule::fromId( $post_id );

      // Setup the new post data
      $post = array(
        'ID' => $post_id,
        'comment_status' => 'closed',
      );

      apply_filters( 'comment_expirator_pre_schedule_execution', $post, $post_id );

      // Disable trackbacks and pingback
      if( $schedule->disable_pt  == 'true' ) {
        $post['ping_status'] = 'closed';
      }

      // Remove the metadata and remove scheduled cron
      $schedule->delete();

      // Update the post
      wp_update_post( $post );

      apply_filters( 'comment_expirator_post_schedule_execution', $post_id );

    }


    /**
     * add_meta_boxes
     * 
     * Add the meta box to the correct admin pages
     */
		public function add_meta_boxes() {

			$options = get_option( 'comment_expirator' );
			
			$allowed_posttypes = ( isset( $options['allowed_posttypes'] ) && is_array( $options['allowed_posttypes'] ) ) ? $options['allowed_posttypes'] : array();
 			
			if( count( $allowed_posttypes ) > 0 ) {

				foreach( $allowed_posttypes as $posttype ) {

					add_meta_box( 'expirator-options', __( 'Comment expiration', COMMENT_EXPIRATOR_TEXTDOMAIN ), array( $this, 'expiration_options_metabox' ), $posttype, 'side' );					

				}

			}

		}


    /**
     * expiration_options_metabox
     * 
     * The html output of the comment expiration options
     * on the admin page
     */
		public function expiration_options_metabox() {

      global $post;

      $schedule = Schedule::fromId( $post->ID );
      $checked = $schedule->run;

      $options = Settings::get_options();

      if( $checked ) {
        $checked = ' checked="checked" ';
      } else {
        $checked = '';
      }


      $pt_checked = $schedule->disable_pt;
      if( empty( $pt_checked ) && ( isset( $options['disable_pt'] ) && $options['disable_pt'] == true ) ) {
        $pt_checked = ' checked="checked" ';
      }

      if( $pt_checked != 'false' && strlen( $pt_checked ) > 0 ) {
        $pt_checked = ' checked="checked" ';
      } else {
        $pt_checked = '';
      }


      if( empty( $schedule->date ) ) {
        $default_time = $options['default_time'];
        if( !empty( $default_time ) && is_int( $default_time ) ) {
          $schedule->date = date('Y-m-d', strtotime("+$default_time days"));
          $schedule->time = date_i18n('H:i');
        }
      }


      $show = !$schedule->run ? ' style="display: none;" ' : '';

			echo '<div class="misc-pub-section misc-pub-section-last" id="expirator-wrapper">'
        . '<label id="expirator-label"> <input type="checkbox" id="expirator-use" name="expirator[use]" ' . $checked . ' />' . __( 'Deactivate comments', COMMENT_EXPIRATOR_TEXTDOMAIN ) . '</label> '
        . '<div id="expirator-settings" ' . $show . ' >'
        . '<label id="expirator-date-label">' . __( 'Date', COMMENT_EXPIRATOR_TEXTDOMAIN ) . '</label> '
        . '<input type="text" id="expiratordate" name="expirator[date]" value="' .$schedule->date. '" maxlengt="10" readonly="true" /><br /> '
        . '<label id="expirator-time-label">' . __( 'Time', COMMENT_EXPIRATOR_TEXTDOMAIN ) . '</label> '
        . '<input type="text" id="expiratortime" name="expirator[time]" value="' . $schedule->time . '" maxlength="5" readonly="true" /><br /><br />'
        . '<label><input type="checkbox" id="expirator-pt" name="expirator[pt]"  ' . $pt_checked . '/> ' . __( 'Also disable trackbacks and pingbacks', COMMENT_EXPIRATOR_TEXTDOMAIN ) . '</label>'
        . '</div>'
        . '</div>';
       
		}


    /**
     * add_plugin_resources
     * 
     * Add the necessary resources fot he plugin
     */
		public static function add_plugin_resources( $hook ) {

			$current_screen = get_current_screen();
			$options = Settings::get_options();

      if( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && isset( $options['allowed_posttypes'] ) && in_array( $current_screen->post_type, $options['allowed_posttypes'] ) ) {
        
        wp_enqueue_script( 'jquery-timepicker-js', COMMENT_EXPIRATOR_PLUGIN_URL . '/js/jquery.ui.timepicker.js', array( 'jquery', 'jquery-ui-core' ), false, true );
        wp_enqueue_script( 'expirator-js', COMMENT_EXPIRATOR_PLUGIN_URL . '/js/commentexpirator.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), false, true );
        wp_enqueue_style( array( 'dashicons' ) );
        
        wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
        wp_enqueue_style( 'jquery-ui' );

        wp_register_style('jquery-timepicker-css', COMMENT_EXPIRATOR_PLUGIN_URL . '/css/jquery.ui.timepicker.css' );
        wp_enqueue_style( 'jquery-timepicker-css' );

        wp_enqueue_style( 'expirator-style', COMMENT_EXPIRATOR_PLUGIN_URL . '/css/commentexpirator.css' );

        // Add filter so developers can add their own assets
        apply_filters( 'comment_expirator_plugin_resources', COMMENT_EXPIRATOR_PLUGIN_URL );
        
      }

		}
	
	}

?>