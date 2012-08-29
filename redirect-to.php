<?php
/*
Plugin Name: Redirect To
Plugin URI: https://github.com/jacobbuck/wp-redirect-to
Description: Lets you make a post or page redirect to another URL.
Version: 1.0
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

class Redirect_To {
	
	function __construct () {
		// Actions
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes') );
		add_action( 'save_post', array( &$this, 'save_post') );
		add_action( 'template_redirect', array( &$this, 'template_redirect') );
		// Filters 
		add_filter( 'page_link', array( &$this, 'post_link'), 10, 2 );
		add_filter( 'post_link', array( &$this, 'post_link'), 10, 2 );
	}
	
	function add_meta_boxes ( $post_type ) {
		add_meta_box( 
			'redirect_to_meta',
			__('Redirect'),
			array( &$this, 'redirect_to_meta_box' ),
			$post_type,
			'side',
			'low'
		);
	}
	
	function redirect_to_meta_box ( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'redirect_to_nonce' );
		?>
		<p><input type="checkbox" name="redirect_to_enabled" id="redirect_to_enabled" value="true" <?php echo get_post_meta( $post->ID, '_redirect_to_enabled', true ) ? 'checked="checked"' : ''; ?>>
			<label for="redirect_to_enabled"><?php _e("Redirect to new URL"); ?></label></p> 
		<div id="redirect_to_reveal">
			<p><label for="redirect_to_url"><?php _e('URL'); ?></label> 
				<br /><input type="url" name="redirect_to_url" id="redirect_to_url" value="<?php echo get_post_meta( $post->ID, '_redirect_to_url', true ); ?>" class="large-text" /></p>
		</div>
		<script>
			(function(doc){
				var enabled = doc.getElementById('redirect_to_enabled'),
					reveal  = doc.getElementById('redirect_to_reveal');
				function update () { reveal.style.display = enabled.checked ? 'block' : 'none' }
				enabled.onchange = update;
				update();
			})(document);
		</script>
		<?php 
	}
	
	function save_post ( $post_id ) {
		// Autosave
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) )
			return;
		// Validation
		if ( empty( $_POST['redirect_to_nonce'] ) || ! wp_verify_nonce( $_POST['redirect_to_nonce'], plugin_basename( __FILE__ ) ) )
			return;
		// Check if user can edit post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		// Save Post Meta
		update_post_meta( $post_id, '_redirect_to_enabled', ! empty( $_POST['redirect_to_enabled'] ) );	
		if ( isset( $_POST['redirect_to_url'] ) ) {
			update_post_meta( $post_id, '_redirect_to_url', empty( $_POST['redirect_to_url'] ) ? '' : trailingslashit( esc_url_raw( $_POST['redirect_to_url'] ) ) );
		}
	}
	
	function template_redirect () {
		// Get redirect metadata
		$enabled = get_post_meta( get_the_ID(), '_redirect_to_enabled', true );
		$url = get_post_meta( get_the_ID(), '_redirect_to_url', true );
		// Redirect if enabled
		if ( $enabled && $url ) {
			define( 'DONOTCACHEPAGE', true ); // wp-super-cache
			nocache_headers();
			wp_redirect( $url );
			exit;
		}
	}
	
	function post_link ( $permalink, $post_id ) {
		// Get redirect metadata
		$enabled = get_post_meta( $post_id, '_redirect_to_enabled', true );
		$url = get_post_meta( $post_id, '_redirect_to_url', true );
		// Return redirect URL if enabled
		if ( $enabled && $url )
			return $url;
		// Otherwise return original permalink
		return $permalink;
	}
	
}

$redirect_to = new Redirect_To;