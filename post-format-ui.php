<?php
/*
Plugin Name: Post Format UI
Plugin URI: http://www.markoheijnen.com
Description: 
Version: 1.0
Author: markoheijnen
Author URI: http://www.markoheijnen.com
License: GPL2

Copyright 2013  Marko Heijnen  (email: info@markoheijnen.com)

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

class Post_Format_UI {
	public $compat = false;

	public function __construct() {
		add_action( 'current_screen', array( $this, 'current_screen' ) );
	}

	public function current_screen( $screen ) {
		global $wp_version;

		if ( 'post' != $screen->base || 'post' != $screen->post_type || ! post_type_supports( 'post', 'post-formats' ) )
			return;

		// Before 3.6
		if ( version_compare( $wp_version, "3.5.9", "<" ) ) {
			include 'inc/compat.php';
			$this->compat = new Post_Format_UI_Compat( $screen );
		}

		if ( 'add' == $screen->action ) {
			add_action( 'admin_head', array( $this, 'clean_screen' ) );
			add_action( 'edit_form_after_title', array( $this, 'show_postformats' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'get_user_option_screen_layout_' . $screen->id, array( $this, 'change_editscreen_columns' ) );
		}
		else {
			add_action( 'admin_head', array( $this, 'set_post_icon' ) );
		}

		add_action( 'save_post', array( $this, 'metabox_selector_save' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'new_title' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}


	public function clean_screen() {
		global $wp_meta_boxes, $_wp_post_type_features;

		$screen = get_current_screen();

		unset( $_wp_post_type_features[ $screen->post_type ]['title'] );
		unset( $_wp_post_type_features[ $screen->post_type ]['editor'] );

		$wp_meta_boxes[ $screen->id ] = array();
	}

	public function show_postformats() {
		$all_post_formats = get_post_format_strings();

		foreach( $all_post_formats as $slug => $label ) {
			echo '<div class="term_selectbox">';
			echo '<input name="content-type" type="submit" value="' . $slug . '" style="background-image: url(' . $this->get_icon( $slug ) . ')" />';
			echo '<div class="title">' . $label . '</div>';
			echo '</div>';
		}

		wp_nonce_field( plugin_basename( __FILE__ ), 'post_format_ui_nonce' );
		echo '<div class="clear"></div>';
	}

	public function metabox_selector_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( ! isset( $_POST['post_format_ui_nonce'] ) || ! wp_verify_nonce( $_POST['post_format_ui_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		if( isset( $_POST['content-type'] ) )
			set_post_format( $post_id, $_POST['content-type'] );
	}

	public function new_title( $data, $postarr ) {
		if( isset( $_POST['content-type'] ) )
			$data['post_title'] = $data['post_name']  = '';

		else if( isset( $data['ID'] ) && $data['post_name'] == $data['ID'] )
			$data['post_name']  = sanitize_title( $data['post_title'] );

		return $data;
	}


	public function change_editscreen_columns() {
		return 1;
	}



	public function set_post_icon() {
		global $post;

		$format = get_post_format( $post->ID );

		if( $format ) {
			$icon_url = $this->get_icon( $format );
			echo '<style type="text/css" media="all">.wrap #icon-edit, .wrap #icon-post { background: url(' . $icon_url . ') center center no-repeat; background-size: 100%; }</style>';
		}
	}



	public function get_icon( $slug )  {
		switch ( $slug ) {
			case 'aside':
				$icon = 'status.png';
				break;
			case 'chat':
				$icon = 'chat.png';
				break;
			case 'gallery':
				$icon = 'gallery.png';
				break;
			case 'link':
				$icon = 'link.png';
				break;
			case 'image':
				$icon = 'image.png';
				break;
			case 'quote':
				$icon = 'quote.png';
				break;
			case 'status':
				$icon = 'status.png';
				break;
			case 'video':
				$icon = 'video.png';
				break;
			case 'audio':
				$icon = 'audio.png';
				break;
			default: // Default
				$icon = 'standard.png';
		}

		return esc_url( plugins_url( 'icons/' . $icon, __FILE__ ) );
	}


	public function enqueue_styles() {
		wp_enqueue_style( 'post_format_ui', plugins_url( '/css/admin.css', __FILE__ ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'custom_content_types', plugins_url( '/js/effects.js', __FILE__ ), array( 'jquery' ) );
	}
}

$Post_Format_UI = new Post_Format_UI();
