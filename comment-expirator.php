<?php


	/*
	Plugin Name: Comment Expirator
	Description: The plugin lets you set a specific closing date and time for comments on any post type.
	Version: 1.1.1
	Author: Andreas Färnstrand <andreas@farnstranddev.se>
  Author URI: http://www.farnstranddev.se
  Text Domain: comment-expirator
	*/

	/*  Copyright 2014  Andreas Färnstrand  (email : andreas@farnstranddev.se)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/


	// Don't allow direct access
	if ( !defined( 'ABSPATH' ) ) exit;

	define( 'COMMENT_EXPIRATOR_PLUGIN_PATH', dirname( __FILE__ ) );
	define( 'COMMENT_EXPIRATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
 	define( 'COMMENT_EXPIRATOR_TEXTDOMAIN', 'comment-expirator' );
  define( 'COMMENT_EXPIRATOR_TEXTDOMAIN_PATH', dirname( plugin_basename( __FILE__) ) .'/languages' );
  define( 'COMMENT_EXPIRATOR_VERSION', '1.1.1' );

	// Require necessary classes
	require_once( 'classes/comment.php' );

	use CommentExpirator\Comment;

	if( class_exists( 'CommentExpirator\\Comment' ) ) {

		$comment = new Comment();

	}

?>