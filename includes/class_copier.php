<?php

if( !class_exists( 'S2S_copier' ) ) {
	
	class S2S_copier {

		function S2S_copier() {}

		function render_combo_sites( $selected_site ) {

			$sites = wp_get_sites();

			echo '<option value="-1">' . __( 'Select a site', 'site2site' ) . '</option>';
			foreach( $sites as $site ) {
				$details = get_blog_details( $site[ 'blog_id' ] );
				echo '<option value="' . $site[ 'blog_id' ] . '"';
				if( $site[ 'blog_id' ] == $selected_site ) {
					echo ' selected';
				} 
				echo '>' . $details->blogname . '</option>';
			}

		}

		function get_new_post_author( $args ) {

			// Option = 1 --> Keep original author
			if( 1 == $args[ 'option' ] ) {
				$selected_author = $args[ 'original_author' ];
			// Option = 2 --> Assign post to selected user
			} else {
				$selected_author = $args[ 'selected_author' ];
			}

			$author_email = '';
			$author_exists = false;
			$author_id = -1;
			$author_role = '';
			$author_info = NULL;

			// Look for the user in the origin site
			foreach( $args[ 'origin_users' ] as $user ) {
				if( $user->ID == $selected_author ) {
					$author_email = $user->user_email;
					$author_info = get_userdata( $user->ID );
					$author_role = implode( ', ', $author_info->roles );				
					break;
				}
			}

			// If the user exists in the origin site, look for him in the target site
			if( $author_email != '' ) {
				foreach( $args[ 'target_users' ] as $user ) {
					if( $user->user_email == $author_email ) {
						$author_id = $user->ID;
						break;
					}
				}

				// If the user does not exist in the target site, add him
				if( -1 == $author_id ) {
					add_user_to_blog( $args[ 'target_site' ], $selected_author, $author_role );
					$author_id = $selected_author;
				}
			}

			return $author_id;
		}

		function render_author_options( $selected_option ) {

			echo '<input type="radio" id="s2s-author-option-1" name="s2s-author-options" value="1"';
			if( 1 == $selected_option ) {
				echo ' checked';
			}
			echo '>' . __( 'Keep the original author', 'site2site' );
			echo '<br/><input type="radio" id="s2s-author-option-2" name="s2s-author-options" value="2"';
			if( 2 == $selected_option ) {
				echo ' checked';
			}
			echo '>' . __( 'Assign the content to another user', 'site2site' );
		
		}

		function render_combo_authors( $selected_origin, $selected_author ) {

			if( $selected_origin > -1 ) {
				$users = get_users( array( 'blog_id' => $selected_origin ) );

				echo '<option value="-1">' . __( 'Select a user', 'site2site' ) . '</option>';
				foreach( $users as $user ) {
					$the_user = get_userdata( $user->ID );
					echo '<option value="' . $user->ID . '"';
					if( $user->ID == $selected_author ) {
						echo ' selected';
					} 
					echo '>' . $the_user->display_name . '</option>';
				}
			}

		}

		function get_authors_json( $site ) {

			$json = array();
			$json[ 'response' ] = 'OK';
			$json[ 'items' ] = array();
			$json[ 'items' ][] = array(
				'ID' => -1,
				'name' => __( 'Select a user', 'site2site' )
			);
			if( $site > -1 ) {
				// Switch to the origin site
				switch_to_blog( $site );

				$users = get_users( array( 'blog_id' => $site ) );

				if( count( $users ) > 0 ) {
					foreach( $users as $user ) {
						$json[ 'items' ][] = array(
							'id' => $user->ID,
							'name' => $user->display_name
						);
					}
				}

				// Get back to the current blog
				restore_current_blog();		
			}

			return json_encode( $json );

		}

		function render_combo_cpts( $origin, $selected_cpt ) {

			if( $origin > -1 ) {
				// Switch to the origin site
				switch_to_blog( $origin );

				$cpts = get_post_types();

				echo '<option value="-1">' . __( 'Select a post type', 'site2site' ) . '</option>';
				foreach( $cpts as $cpt ) {
					echo '<option value="' . $cpt . '"';
					if( $cpt == $selected_cpt ) {
						echo ' selected';
					} 
					echo '>' . $cpt . '</option>';
				}

				// Get back to the current blog
				restore_current_blog();		
			}

		}

		function get_cpts_json( $site ) {

			$json[ 'response' ] = 'OK';
			$json[ 'items' ] = array();
			$json[ 'items' ][] = array(
				'ID' => -1,
				'name' => __( 'Select a post type', 'site2site' )
			);
			if( $site > -1 ) {
				// Switch to the origin site
				switch_to_blog( $site );

				$cpts = get_post_types();

				if( count( $cpts ) > 0 ) {
					foreach( $cpts as $cpt ) {
						$json[ 'items' ][] = array(
							'id' => $cpt,
							'name' => $cpt
						);
					}
				}

				// Get back to the current blog
				restore_current_blog();		
			}

			return json_encode( $json );

		}

		function render_content( $args ) {

			// Switch to the origin site
			switch_to_blog( $args[ 'origin' ] );

			// Get all items that match the criteria
			$args2 = array(
				'post_type' => $args[ 'post_type' ],
				'posts_per_page' => -1
			);
			$items = get_posts( $args2 );

			foreach( $items as $item ) {
				echo '<div id="s2s-item-' . $item->ID . '" class="s2s-item" data-id="' . $item->ID . '"></div>';
			}

			// Get back to the current blog
			restore_current_blog();	
		
		}

		function copy_item( $args ) {

			global $wpdb;

			$result = true;

			// Get all users in origin site
			$origin_users = get_users( array( 'blog_id' => $args[ 'origin_site' ] ) );

			// Get all users in target site
			$target_users = get_users( array( 'blog_id' => $args[ 'target_site' ] ) );

			// Switch to the origin site
			switch_to_blog( $args[ 'origin_site' ] );

			// Get origin upload folder
			$origin_upload_dir = wp_upload_dir();
			$origin_folder = $origin_upload_dir[ 'basedir' ];
			$origin_upload_url = $origin_upload_dir[ 'url' ];

			$the_post = get_post( $args[ 'post_id' ] );

			$post_author_args = array(
				'option' => $args[ 'author_option' ],
				'selected_author' => $args[ 'selected_author' ],
				'original_author' => $the_post->post_author,
				'target_site' => $args[ 'target_site' ],
				'origin_users' => $origin_users,
				'target_users' => $target_users
			);

			// Get post data
			$args_new_post = array(
				'comment_status' => $the_post->comment_status,
				'ping_status'    => $the_post->ping_status,
				'post_author'    => self::get_new_post_author( $post_author_args ),
				'post_content'   => $the_post->post_content,
				'post_excerpt'   => $the_post->post_excerpt,
				'post_name'      => $the_post->post_name,
				'post_parent'    => $the_post->post_parent,
				'post_password'  => $the_post->post_password,
				'post_status'    => $the_post->post_status,
				'post_title'     => $the_post->post_title,
				'post_type'      => $the_post->post_type,
				'to_ping'        => $the_post->to_ping,
				'menu_order'     => $the_post->menu_order
			);

			// Get post taxonomies
			$taxonomies = get_object_taxonomies($the_post->post_type);
			$all_terms = array();
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms( $the_post->ID, $taxonomy, array( 'fields' => 'slugs' ) );
				$all_terms[ $taxonomy ] = $post_terms;
				
			}

			// Get post meta
			$post_meta_infos = $wpdb->get_results( 'SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = ' . $the_post->ID );

			// Get all attachments
			$args_attach = array(
				'post_type' => 'attachment',
				'post_parent' => $the_post->ID,
				'posts_per_page' => -1
			);
			$attachments = get_posts( $args_attach );
			foreach( $attachments as $attachment ) {
				$attachment_meta = $wpdb->get_results( 'SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = ' . $attachment->ID );
				$attachment->meta = $attachment_meta;
				$attached_file = get_post_meta( $attachment->ID, '_wp_attached_file', true );
				$attachment->attached_file = $attached_file;
			}

			// Switch to the target site
			switch_to_blog( $args[ 'target_site' ] );

			// Insert the post in the target site
			$new_post_id = wp_insert_post( $args_new_post );

			if( $new_post_id > 0 ) {

				// Insert the taxonomies
				foreach( $taxonomies as $taxonomy ) {
					wp_set_object_terms( $new_post_id, $all_terms[ $taxonomy ], $taxonomy, false );
				}

				// Insert the post meta
				foreach( $post_meta_infos as $meta_info ) {
					add_post_meta( $new_post_id, $meta_info->meta_key, addslashes( $meta_info->meta_value ) );
				}

				// Insert the attachments
				foreach( $attachments as $attachment ) {
					$path_origin = $origin_folder . '/' . $attachment->attached_file;
					$target_upload_dir = wp_upload_dir();
					$index = strrpos( $attachment->attached_file, '/' );
					$filename = substr( $attachment->attached_file, $index+1 );
					$path_destination = $target_upload_dir[ 'path' ] . '/' . $filename;
					$target_upload_url = $target_upload_dir[ 'url' ];

					// Copy the file to destination
					copy( $path_origin, $path_destination );
					$file_url = $target_upload_dir[ 'url' ] . '/' . $filename;
					$file_path = ABSPATH . 'wp-content/uploads/' . date( 'Y' ) . '/' . date( 'm' ) . '/' . $filename;
					$file_path2 = date( 'Y' ) . '/' . date( 'm' ) . '/' . $filename;
					$filetype = wp_check_filetype( basename( $file_url ), null );
					$attachment = array(
						'guid'           => $file_url, 
						'post_mime_type' => $filetype[ 'type' ],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_url ) ),
						'post_content'   => '',
						'post_status'    => 'inherit',
						'post_parent'	 => $new_post_id
					);
					$attach_id = wp_insert_attachment( $attachment, $file_path, 0 );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					// Update the attachment metadata to include the correct url	
					update_post_meta( $attach_id, '_wp_attached_file', $file_path2 );
					$attachment_metadata = get_post_meta( $attach_id, '_wp_attachment_metadata', true );
					$attachment_metadata[ 'file' ] = $file_path2;
					update_post_meta( $attach_id, '_wp_attachment_metadata', $attachment_metadata );

					// Generate thumbnails
					$img = wp_get_image_editor( $path_destination );
					$sizes = self::get_image_sizes();
					$img->multi_resize( $sizes );

					// Set featured image
					update_post_meta( $new_post_id, '_thumbnail_id', $attach_id ); 

					// Fix all references to attachments inside the post content 
					$the_post = get_post( $new_post_id );
					$the_post->post_content = str_replace( $origin_upload_url, $target_upload_url, $the_post->post_content );
					wp_update_post( $the_post );

				}

			} else {
				$result = false;
			}	

			// Get back to the current blog
			restore_current_blog();

			return $result;

		}

		function get_image_sizes() {

		    global $_wp_additional_image_sizes;

		    $sizes = array();
		    $get_intermediate_image_sizes = get_intermediate_image_sizes();

		    // Create the full array with sizes and crop info
		    foreach( $get_intermediate_image_sizes as $_size ) {

		            if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

		            		$size = array(
		            			'width' => get_option( $_size . '_size_w' ),
		            			'height' => get_option( $_size . '_size_h' ),
		            			'crop' => (bool) get_option( $_size . '_crop' )
		            		);
		            		$sizes[] = $size;

		            } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

		                    $sizes[] = array( 
		                            'width' => $_wp_additional_image_sizes[ $_size ]['width'],
		                            'height' => $_wp_additional_image_sizes[ $_size ]['height'],
		                            'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
		                    );

		            }

		    }

		    return $sizes;
		}		

	}

}