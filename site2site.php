<?php

/*
 * Plugin Name: site2site
 * Description: Copies any content from one site to another in a multisite installation
 * Author: Alan Cesarini
 * Version: 1.0.0
 * Author URI: http://alancesarini.com
 * License: GPL2+
 */

if( !class_exists( 'Site2Site' ) ) {

	class Site2Site {

		private static $_this;

		private static $_version;

		private static $s2s_copier;

		function __construct() {
		
			if( isset( self::$_this ) )
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
			self::$_this = $this;

			self::$_version = '1.0.4';

			require( 'includes/class_copier.php' );

			self::$s2s_copier = new S2S_copier();

			add_action( 'wp_loaded', array( $this, 'register_assets' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );

			add_action( 'wp_ajax_s2s_load_cpts', array( $this, 'load_cpts' ) );

			add_action( 'wp_ajax_s2s_load_authors', array( $this, 'load_authors' ) );

			add_action( 'wp_ajax_s2s_copy_item', array( $this, 'copy_item' ) );			
			
		}

		function load_cpts() {

			$site = intval( $_POST[ 'site' ] );
			echo self::$s2s_copier->get_cpts_json( $site );
			die();

		}
		
		function load_authors() {

			$site = intval( $_POST[ 'site' ] );
			echo self::$s2s_copier->get_authors_json( $site );
			die();

		}

		function copy_item() {

			$args = array(
				'post_id' => intval( $_POST[ 'id' ] ),
				'origin_site' => intval( $_POST[ 'origin' ] ),
				'target_site' => intval( $_POST[ 'target' ] ),
				'author_option' => intval( $_POST[ 'option' ] ),
				'selected_author' => intval( $_POST[ 'author' ] )
			);

			$response = self::$s2s_copier->copy_item( $args );

			if( $response ) {
				die( json_encode(array( 'response' => 'OK' )));
			} else {
				die( json_encode(array( 'response' => 'KO' )));
			}

		}

		function add_network_page() {

			add_submenu_page( 'settings.php', 'site2site', 'Copy content to another site', 'manage_options', 'site2site', array( $this, 'render_admin_page' ) );

		}

		function render_admin_page() {

			$selected_origin = ( isset( $_POST[ 's2s-origin-site' ] ) ? intval( $_POST[ 's2s-origin-site' ] ) : -1 ); 
			$selected_target = ( isset( $_POST[ 's2s-target-site' ] ) ? intval( $_POST[ 's2s-target-site' ] ) : -1 );
			$selected_author_option = ( isset( $_POST[ 's2s-author-options' ] ) ? $_POST[ 's2s-author-options' ] : 1 );
			$selected_author = ( isset( $_POST[ 's2s-author' ] ) ? intval( $_POST[ 's2s-author' ] ) : 1 ); 
			$selected_cpt = ( isset( $_POST[ 's2s-cpt' ] ) ? sanitize_text_field( $_POST[ 's2s-cpt' ] ) : 'post' );

		?>

			<div class="wrap">
				<h2>site2site</h2>
				<form method="post">
					<table class="form-table">
						<tr>
							<th><label for="s2s-origin-site"><?php _e( 'Select the site to copy from', 'site2site' ); ?></label></th>
							<td>
								<select name="s2s-origin-site" id="s2s-origin-site">
									<?php self::$s2s_copier->render_combo_sites( $selected_origin ); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="s2s-target-site"><?php _e( 'Select the target site', 'site2site' ); ?></label></th>
							<td>
								<select name="s2s-target-site" id="s2s-target-site">
									<?php self::$s2s_copier->render_combo_sites( $selected_target ); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="s2s-cpt"><?php _e( 'Select the post type', 'site2site' ); ?></label></th>
							<td>
								<select name="s2s-cpt" id="s2s-cpt">
									<?php self::$s2s_copier->render_combo_cpts( $selected_origin, $selected_cpt ); ?>
								</select>
							</td>
						</tr>												
						<tr>
							<th><label for="s2s-author-option"><?php _e( 'Keep the original author?', 'site2site' ); ?></label></th>
							<td>
								<?php self::$s2s_copier->render_author_options( $selected_author_option ); ?>
							</td>
						</tr>
						<tr id="s2s-row-select-author" <?php if( 1 == $selected_author_option ) { ?> style="display:none" <?php } ?>>
							<th><label for="s2s-author"><?php _e( 'Select the new owner of the content', 'site2site' ); ?></label></th>
							<td>
								<select name="s2s-author" id="s2s-author">
									<?php self::$s2s_copier->render_combo_authors( $selected_target, $selected_author ); ?>
								</select>							
							</td>
						</tr>
						<tr>
							<td></td>
							<td>
								<input type="hidden" id="s2s-selected-cpt" name="s2s-selected-cpt" value="<?php echo $selected_cpt; ?>">
								<input type="hidden" id="s2s-selected-author" name="s2s-selected-author" value="<?php echo $selected_author; ?>">
								<input type="submit" name="s2s-copy" id="s2s-copy" value="<?php _e( 'Copy content now', 'site2site' ); ?>" class="button button-primary" />
							</td>
						</tr>
					</table>
				</form>

		<?php
			if( isset( $_POST[ 's2s-copy'] ) ) {

				$args = array(
					'origin' => $selected_origin,
					'target' => $selected_target,
					'post_type' => $selected_cpt,
					'author_option' => $selected_author_option,
					'author' => $selected_author
				);
				
				echo '<div class="s2s-container">';

				self::$s2s_copier->render_content( $args );

				echo '</div>';
			}
		}

		function register_assets() {

			wp_register_script( 's2s-admin-js', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), self::$_version );
			wp_register_style( 's2s-admin-style', plugins_url( 'assets/css/admin.css', __FILE__ ), false, self::$_version );

		}

		function enqueue_assets() {

			wp_enqueue_script( 's2s-admin-js' );
			wp_enqueue_style( 's2s-admin-style' );

		}	

		static function this() {
		
			return self::$_this;
		
		}

	}

}	

new Site2Site();
