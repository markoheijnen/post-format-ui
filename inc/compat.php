<?php
/**
 * Compat layer before 3.6
 */

class Post_Format_UI_Compat {

	public function __construct( $screen ) {
		global $wp_version;

		if ( '' == $screen->action ) {
			add_action( 'edit_form_after_title', array( $this, 'post_format_fields' ) );
			add_action( 'save_post', array( $this, 'post_format_fields_save' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Before 3.5 the hook "edit_form_after_title" didn't exists. 
		if ( version_compare( $wp_version, "3.5", "<" ) ) {
			//add_action( 'admin_enqueue_script', array( $this, 'enqueue_script' ) );
			//add_action( 'wp_ajax_post_format_load', array( $this, 'ajax_load_template' ) );
		}
	}

	public function post_format_fields() {
		global $post_type, $post_ID;

		if ( post_type_supports( $post_type, 'post-formats' ) ) {
			$format      = get_post_format( $post_ID );
			$format_meta = $this->get_post_format_meta( $post_ID );

			if ( isset( $format_meta['image'] ) )
				$image = is_numeric( $format_meta['image'] ) ? wp_get_attachment_url( $format_meta['image'] ) : $format_meta['image'];
			else
				$image = false;

			wp_nonce_field( plugin_basename( __FILE__ ), 'post_format_ui_compat_nonce' );
			?> 

			<div class="post-formats-fields"> 
				<input type="hidden" name="post_format" id="post_format" value="<?php echo esc_attr( $post_format ); ?>" /> 

				<?php if( 'quote' == $format ) { ?>
				<div class="field wp-format-quote">
					<label for="_wp_format_quote" class="screen-reader-text"><?php _e( 'Quote' ); ?>:</label>
					<textarea name="_wp_format_quote" placeholder="<?php esc_attr_e( 'Quote' ); ?>" class="widefat"><?php echo esc_textarea( $format_meta['quote'] ); ?></textarea>
				</div>

				<div class="field wp-format-quote">
					<label for="_wp_format_quote_source" class="screen-reader-text"><?php _e( 'Quote source' ); ?>:</label>
					<input type="text" name="_wp_format_quote_source" value="<?php echo esc_attr( $format_meta['quote_source'] ); ?>" placeholder="<?php esc_attr_e( 'Quote source' ); ?>" class="widefat" />
				</div>
				<?php } ?>

				<?php if( 'image' == $format ) { ?>
				<div class="field wp-format-image">
					<div id="wp-format-image-holder" class="hide-if-no-js<?php if ( ! $image ) echo ' empty'; ?>">
						<a href="#" id="wp-format-image-select"
							data-choose="<?php esc_attr_e( 'Choose an Image' ); ?>"
							data-update="<?php esc_attr_e( 'Select Image' ); ?>">
							<?php
								if ( $image )
									echo '<img src="' . esc_url( $image ) . '" />';
								else
									_e( 'Select Image' );
							?>
						</a>
					</div>
					<label for="_wp_format_image" class="screen-reader-text"><?php _e( 'Image ID or URL' ); ?>:</label>
					<input type="text" name="_wp_format_image" id="wp_format_image" value="<?php echo esc_attr( $format_meta['image'] ); ?>" placeholder="<?php esc_attr_e( 'Image ID or URL' ); ?>" class="widefat hide-if-js" />
				</div>
				<?php } ?>

				<?php if( 'link' == $format || 'quote' == $format || 'image' == $format ) { ?>
				<div class="field wp-format-link wp-format-quote wp-format-image">
					<label for="_wp_format_url" class="screen-reader-text"><?php _e( 'Link URL' ); ?>:</label>
					<input type="text" name="_wp_format_url" value="<?php echo esc_url( $format_meta['url'] ); ?>" placeholder="<?php esc_attr_e( 'Link URL' ); ?>" class="widefat" />
				</div>
				<?php } ?>

				<?php if( 'gallery' == $format ) { ?>
				<div class="field wp-format-gallery">
					<label for="_wp_format_gallery" class="screen-reader-text"><?php _e( 'Gallery shortcode' ); ?>:</label>
					<input type="text" name="_wp_format_gallery" id="wp_format_gallery" value="<?php echo esc_attr( $format_meta['gallery'] ); ?>" placeholder="<?php esc_attr_e( 'Gallery shortcode' ); ?>" class="widefat" />
				</div>
				<?php } ?>

				<?php if( 'audio' == $format || 'video' == $format ) { ?>
				<div class="field wp-format-audio wp-format-video">
					<label for="_wp_format_media" class="screen-reader-text"><?php _e( 'Embed code or URL' ); ?>:</label>
					<textarea name="_wp_format_media" placeholder="<?php esc_attr_e( 'Embed code or URL' ); ?>" class="widefat"><?php echo esc_textarea( $format_meta['media'] ); ?></textarea>
				</div>
				<?php } ?>
			</div>
			<?php
		}
	}

	public function post_format_fields_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( ! isset( $_POST['post_format_ui_compat_nonce'] ) || ! wp_verify_nonce( $_POST['post_format_ui_compat_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$post_data = &$_POST;

		if ( isset( $post_data[ '_wp_format_url' ] ) ) { 
			update_post_meta( $post_ID, '_wp_format_url', addslashes( esc_url_raw( stripslashes( $post_data['_wp_format_url'] ) ) ) ); 
		} 

		$format_keys = array( 'quote', 'quote_source', 'image', 'gallery', 'media' ); 
		foreach ( $format_keys as $key ) { 
			if ( isset( $post_data[ '_wp_format_' . $key ] ) ) 
				update_post_meta( $post_id, '_wp_format_' . $key, wp_filter_post_kses( $post_data[ '_wp_format_' . $key ] ) ); 
		} 
	}

	/**
	 * Retrieve post format metadata for a post
 	 *
	 * @since 1.0
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function get_post_format_meta( $post_id = 0 ) { 
		$values = array(
 			'quote'        => '',
 			'quote_source' => '',
 			'image'        => '',
 			'url'          => '',
 			'gallery'      => '',
 			'media'        => '',
		);

		foreach ( $values as $key => $value )
			$values[$key] = get_post_meta( $post_id, '_wp_format_' . $key, true );

		return $values;
	}


	public function enqueue_scripts() {
		wp_enqueue_script( 'post_format_ui_compat', plugins_url( '/js/compat.js', dirname( __FILE__ ) ), array( 'media-models' ), false, 1 );
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'post_format_ui_compat', plugins_url( '/css/compat.css', dirname( __FILE__ ) ) );
	}

}

