<?php

	/**
	 * Class Schedule
	 * @package CommentExpirator
	 * @author Andreas Färnstrand <andreas@farnstranddev.se>
	 */

	namespace CommentExpirator;

	class Schedule {

		private $values = array();

		/**
		 * fromId
		 * 
		 * Create an instance of the schedule class
		 * @param integer $post_id
		 * @return object
		 */
		public static function fromId( $post_id ) {

			$schedule = new Schedule();
			$schedule->post_id = $post_id;
			$schedule->run = false;

			$date = get_post_meta( $post_id, 'comment_expirator_date', true );
			$schedule->date = !empty( $date ) ? $date : null;
			$time = get_post_meta( $post_id, 'comment_expirator_time', true );
			$schedule->time = !empty( $time ) ? $time : null;
			$schedule->disable_pt = get_post_meta( $post_id, 'comment_expirator_disable_pt', true );

			if( !empty( $date ) && !empty( $time ) ) {
				$schedule->run = true;
			}

			return $schedule;

		}


		/**
		 * __set
		 * 
		 * Function for setting object properties
		 * 
		 * @param unknown $key
		 * @param unknown $value
		 */
		public function __set( $key, $value ) {
			
			$this->values[$key] = $value;

		}


		/**
		 * __get
		 * 
		 * Return the object property
		 * 
		 * @param unknown $key
		 * @return unknown
		 */
		public function __get( $key ) {

			return isset( $this->values[$key] ) ? $this->values[$key] : null;

		}


		/**
		 * __isset
		 * 
		 * Function for checking if an object propery is set
		 */
		public function __isset( $property ) {

			return isset( $this->values[$property] );

		}


		/**
		 * save
		 * 
		 * Save the comment expirator postmeta connected to the post
		 */
		public function save() {
			
			if( $this->run ) {

				update_post_meta( $this->post_id, 'comment_expirator_date', $this->date );
				update_post_meta( $this->post_id, 'comment_expirator_time', $this->time );
				update_post_meta( $this->post_id, 'comment_expirator_disable_pt', $this->disable_pt );

			}

		}


		/**
		 * delete
		 * 
		 * Delete the comment expirator postmeta connected to the post.
		 * Also remove the scheduled hook from cron
		 */
		public function delete() {

			delete_post_meta( $this->post_id, 'comment_expirator_date' );
			delete_post_meta( $this->post_id, 'comment_expirator_time' );
			delete_post_meta( $this->post_id, 'comment_expirator_disable_pt' );

			wp_clear_scheduled_hook( 'comment_expirator', array( $this->post_id ) );

		}


		/**
     * create_timestamp
     * 
     * Create a new timestamp from given date and time
     * 
     * @param string $date
     * @param string $time
     *
     * @return boolen|integer
     */
    public static function create_timestamp( $date, $time ) {

       $timestamp = strtotime( $date . ' ' . $time . ':00' );

        //Abort if not a valid timestamp
        if( !isset( $timestamp ) || !is_int( $timestamp ) ) return false;

        return $timestamp;

    }


    /**
     * check_gmt_against_system_time
     * 
     * @param integer $new_timestamp
     * 
     * @return integer $gmt;
     */
    public static function check_gmt_against_system_time( $new_timestamp ) {

      // Get the current system time to compare with the new scheduler timestamp
        $system_time = microtime( true );
        $gmt = get_gmt_from_date( date( 'Y-m-d H:i:s', $new_timestamp ),'U');

        // The gmt needs to be bigger than the current system time
        if( $gmt <= $system_time ) return false;

        return $gmt;

    }

	}


?>