<?php

// Exit if accessed directly.
if( ! defined( 'ABSPATH' ) ){
	
	exit;
}

class WP_SVG_Upload {

		private $current_role     = '';
		private $user_upload_mode = '';
		
		function __construct(){
			
			// Lanuage init
			add_action( 'init', array( $this, 'language_init' ) );
			
			// Checking site params. Otherwise, we stop further execution
			if( ! $this->check_version_php() || ! $this->check_version_wp() ){
				
				return;
			}
			
			// Current user WP role
			add_action( 'plugins_loaded', array( $this, 'current_user_role' ) );
			
			// Register svg mime in WP
			add_filter( 'upload_mimes', array( $this, 'allow_mime_type_svg' ) );
			
			// Correct mimetype
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'correct_mimetype_svg' ), 100, 4 );
			
			// The plugin configuration is available only to the administrator role
			add_action( 'plugins_loaded', array( $this, 'admin_page_view' ) );
			
			// Set upload mode
			add_action( 'plugins_loaded', array( $this, 'can_user_upload_svg' ) );
						
			// Save SVG
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'svg_upload_process' ) );
						
			// Set meta WP
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'svg_attache_meta' ), 10, 2 );
			
			// Style load
			add_action( 'admin_footer', array( $this, 'load_assets' ) );
			
			add_filter( 'wp_calculate_image_srcset_meta', array( $this, 'srcset_off' ), 10, 4 );
		}
		
		/*
		**
		 *
		 * Clear POST data
		 *
		 */
		 
		public function valid_clear_post( $str ) {
			
			if( array_key_exists( $str, $_POST ) )
			{
				// Sanitize
				$str = sanitize_text_field( $_POST[ $str ] );
				
				return str_replace( "'", "", $str );
			}
			else
			{
				return '';
			}
		}
		
		/*
		**
		 *   Plugin styles & script
		 *
		 */
		
		public function load_assets(){
			wp_enqueue_style ( SVGUPL_PLUGIN_SHORT_NAME, SVGUPL_PLUGIN_URL . 'assets/css/plugin_style.css', array(), SVGUPL_PLUGIN_VERSION );
		}
		
		/*
		**
		 *   Set current user role
		 *
		 */
		 
		public function current_user_role(){
			$user_roles = wp_get_current_user()->roles;
			$this->current_role = array_shift( $user_roles );
		}
		
		/*
		**
		 *  Loading language
		 *
		 */
		 
		public function language_init(){
			load_plugin_textdomain( 'upload-svg', false, SVGUPL_PLUGIN_NAME . '/languages/' );
		}
		
		/*
		**
		 *  Checking the current PHP version 
		 *
		 *  @return bool
		 */
		
		private function check_version_php(){
			
			if( version_compare( PHP_VERSION, SVGUPL_PHP_MIN_VERSION, '<' ) ){
				
				$this->view_notice( 
					'notice-error', 
					'<b>' . SVGUPL_PLUGIN_NAME . ':</b> ' . __( 'Minimum required PHP version ( or later ) ', 'upload-svg' ) . SVGUPL_PHP_MIN_VERSION
				);
				return false;
			}
			return true;
		}
		
		/*
		**
		 *  Checking the current WP version 
		 *
		 *  @return bool
		 */
		
		private function check_version_wp(){
			
			if( version_compare( PHP_VERSION, SVGUPL_WP_MIN_VERSION, '<' ) ){
				$this->view_notice( 
					'notice-error', 
					'<b>' . SVGUPL_PLUGIN_NAME . ':</b> ' . __( 'Minimum required WP version ( or later ) ', 'upload-svg' ) . SVGUPL_WP_MIN_VERSION
				);
				return false;
			}
			return true;
		}
		
		/*
		**
		 *  Displays a notification in the admin panel
		 *
		 *  @param string $type Accepts 'notice-error', 'notice-success', 'notice-warning', 'notice-info', 'is-dismissible'
		 *  @param string $message
		 */
		
		public function view_notice( $type,  $message ){
			
			?>
				<div class="notice <?php echo wp_kses_post( $type ); ?>">
					<p>
						<?php echo wp_kses_post( $message ); ?>
					</p>
				</div>
			<?php
		}
		
		/*
		**
		 *  The plugin settings page is available only to the system administrator
		 *
		 */
		
		public function admin_page_view(){
			
			if( ! current_user_can('administrator') ){
				
				return;
			}
			
			// Keep all existing roles
			add_action( 'current_screen', array( $this, 'save_all_users_roles' ) );
			
			// Add menu
			add_action( 'admin_menu', array( $this, 'admin_page_view_menu' ) );
			
			// Add link
			add_filter( 'plugin_action_links_' . SVGUPL_PLUGIN_NAME . '/' . SVGUPL_PLUGIN_NAME . '.php', array( $this, 'admin_plugin_link' ), 10, 2 );
		}

		/*
		**
		 *  Menu item
		 *
		 */
		
		public function admin_page_view_menu(){
			
			if( ! current_user_can('administrator') ){
				
				return;
			}

			add_menu_page(  
				__( 'Upload SVG', 'upload-svg' ),
				__( 'Upload SVG', 'upload-svg' ),       
				'administrator',          
				'upload-svg',                   
				array( $this, 'admin_setting_page'),
				'dashicons-upload', 90
			);  
		}
		
		/*
		**
		 *  Link to settings
		 *
		 *  @return string
		 */
		
		public function admin_plugin_link( $links, $file )
		{
			if( ! current_user_can('administrator') ){
				
				return;
			}
			
			array_unshift( 
				$links, 
				'<a href="admin.php?page=' . SVGUPL_PLUGIN_NAME . '">' . __( 'Settings', 'upload-svg' ) . '</a>' 
			);
			
			return $links;
		}
		
		/*
		**
		 *  View setting HTML page
		 *
		 */
		 
		public function admin_setting_page(){
			
			if( ! current_user_can('administrator') ){
				
				return;
			}
			
			$user_roles = get_option( SVGUPL_PLUGIN_SHORT_NAME . '_user_roles' );
			$params     = get_option( SVGUPL_PLUGIN_SHORT_NAME . '_params' );
			$file_limit = get_option( SVGUPL_PLUGIN_SHORT_NAME . '_file_limit' );

			// Processing the data of the submitted form
			if( $this->valid_clear_post('save_params') == 1 ){
				
				if( wp_verify_nonce( $this->valid_clear_post('params_nonce'), 'save_current_config' ) ){
					
					$params = array();
					foreach( $user_roles as $role_slug ){
						
						if( ! empty( $role_slug ) ){
							
							$post_value = $this->valid_clear_post( 'role_' . $role_slug );
							
							if( ! empty($post_value) ){
								
								$params[ $role_slug ] = $post_value;
							}
						}
					}
					
					// Save the received values
					update_option( SVGUPL_PLUGIN_SHORT_NAME . '_params', $params );
					
					// File limit
					$file_limit = 0;
					
					if( $this->valid_clear_post('file_limit') > 0 ) {
						
						$file_limit = $this->valid_clear_post('file_limit');
					}
					
					// Save
					update_option( SVGUPL_PLUGIN_SHORT_NAME . '_file_limit', $file_limit );
					
					$this->view_notice( 
						'notice-success', 
						__( 'The changes were saved successfully.', 'upload-svg' )
					);
				}
				else 
				{
					// Form submission error
					$this->view_notice( 
						'notice-error', 
						__( 'Form submission error. Repeat the request or contact the developer.', 'upload-svg' )
					);
				}
			}
			
			// Settings are not saved, we create by default
			if( ! is_array( $params ) && $user_roles ){
				
				$params = array();
				foreach( $user_roles as $role_slug ){
					
					if( $role_slug == 'administrator' ){
						
						$params[ $role_slug ] = 'sanitized';
					}else{
						
						$params[ $role_slug ] = 'disabled';
					}
				}
				
				// Save option file limit default
				update_option( SVGUPL_PLUGIN_SHORT_NAME . '_file_limit', 0 );
				
				// Saving parameters by default
				update_option( SVGUPL_PLUGIN_SHORT_NAME . '_params', $params );
			}
		
			if( is_array( $user_roles ) && current_user_can('administrator') ){
				
				$option_array = array(
					'sanitized'     => esc_html__( 'Enable SVG Upload with Sanitization', 'upload-svg' ),
					'non-sanitized' => esc_html__( 'Enable SVG Upload without Sanitization', 'upload-svg' ),
					'disabled'      => esc_html__( 'Disable SVG Upload', 'upload-svg' )
				);

				?>
					<div class="wrap">
						<h2><?php echo esc_html__( 'Upload SVG', 'upload-svg' ); ?></h2>
						
						<div class="postbox" style="margin-top:10px">
							<div class="inside" style="padding-bottom:2px">
								<h3><?php echo esc_html__( 'Important Security Notice', 'upload-svg'); ?></h3>
								
								<p>
									<?php echo esc_html__( 'SVG files may contain malicious code, including JavaScript, making them a potential security risk.', 'upload-svg'); ?>
								</p>
								
								<p>
									<?php echo esc_html__( 'Configure SVG file upload mode for each user role in the dropdown lists. Choose whether to enable sanitization for uploaded SVG files to enhance security. Consider limiting the file size of uploaded SVGs for additional safety.', 'upload-svg'); ?>
								</p>
								
								<p class="notice notice-large notice-warning">
									<?php echo esc_html__( 'We strongly recommend enabling the upload mode only with sanitization and only for trusted user roles.', 'upload-svg'); ?>
								</p>
							</div>
						</div>
						
						
						<div class="postbox" style="margin-top:10px">
							<div class="inside" style="padding-bottom:2px">
								<div class="settings_select_block_info">
									<?php 
										echo esc_html__( 'Available user roles and permissions for uploading SVG images. Select the desired settings options.', 'upload-svg' );
									?>
								</div>
								<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . SVGUPL_PLUGIN_NAME ) ); ?>" name="save_params" method="post" >
									
									<?php 
										wp_nonce_field( 'save_current_config', 'params_nonce' ); 
									
										// Roles
										foreach( $user_roles as $role_slug ){
											
											if( !empty( $role_slug ) ){
												?>
													<div class="settings_select_block">
														<span><?php echo esc_html( $role_slug ); ?></span>
														
														<select name="role_<?php echo esc_html( $role_slug ); ?>" >
															<?php
																// Options
																foreach( $option_array as $mode => $option ){
																	
																	$selected = '';
																	if( 
																		array_key_exists( $role_slug,  $params) 
																		&& ! empty( $params[$role_slug] ) 
																		&& $params[$role_slug] == $mode 
																	){
																		$selected = 'selected';
																	}
																	?>
																		<option value="<?php echo esc_html( $mode ); ?>" <?php echo esc_html( $selected ); ?> ><?php echo esc_html( $option ); ?></option>
																	<?php
																}
															?>
														</select>
													</div>
												<?php
											}
										}
									?>
									
									<div class="settings_limit_block">
										<span ><?php echo esc_html__( 'SVG file upload limit, KB (0 - unlimited)', 'upload-svg' ); ?></span>
										
										<input type="number" name="file_limit" value="<?php echo esc_html( $file_limit ); ?>" >
									</div>
									
									<p class="submit">
										<input type="submit" class="button button-primary" value="<?php echo esc_html__( 'Save сhanges', 'upload-svg' ); ?>" >
									</p>
									<input type="hidden" name="save_params" value="1">
								</form>
							</div>
						</div>	
					</div>
				<?php
			}
		}
		
		/*
		**
		 *   We check that the admin is on the plugin settings page. 
		 *   We save all user roles
		 *
		 */
		
		public function save_all_users_roles(){
			
			global $wp_roles;
			
			// Check current page
			$screen = get_current_screen();
			if( 'toplevel_page_' . SVGUPL_PLUGIN_NAME === $screen->id && current_user_can('administrator') ){
				
				$user_roles = array();
				
				// Forming an array of roles
				foreach( $wp_roles->roles as $role_slug => $role ){
					
					if( !empty( $role_slug ) ){
						
						$user_roles[] = esc_attr( $role_slug );
					}
				}
				
				// Save user roles
				update_option( SVGUPL_PLUGIN_SHORT_NAME . '_user_roles', $user_roles );
			}else{
				return;
			}
		}
		
		/*
		**
		 *   Checking the user's rights to be able to download svg files.
		 *
		 *   @return string
		 */
		
		public function can_user_upload_svg()
		{
			$params = get_option( SVGUPL_PLUGIN_SHORT_NAME . '_params' );
			
			// Default can not
			$this->user_upload_mode = '';
			
			if( 
				! empty( $this->current_role ) 
				&& is_array( $params ) 
				&& array_key_exists( $this->current_role, $params )
				&& ! empty( $params[ $this->current_role ] )
				&& 'disabled' != $params[ $this->current_role ]
			){
				// Set a value other than "disabled"
				$this->user_upload_mode = $params[ $this->current_role ];
			}
		}
		
		/*
		**
		 *   Register mime type SVG
		 *
		 *   @param $mimes WP types array
		 *
		 *   @return array
		 */
		
		public function allow_mime_type_svg( $mimes ){
			
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
			
			return $mimes;
		}
		
		/*
		** Fixes unable to correctly identify SVGs and version php
		 *
		 *
		 * @param array    $data     Values for the extension, mime type, and corrected filename.
		 * @param string   $file     Full path to the file.
		 * @param string   $filename The name of the file.
		 * @param string[] $mimes    Array of mime types keyed by their file extension regex.
		 *
		 * @return arr
		 */
		 
		public function correct_mimetype_svg( $data, $file, $filename, $mimes ){
			$extension = isset( $data['ext'] ) ? $data['ext'] : '';
			
			if( strlen( $extension ) < 1 ){
				$exploded  = explode( '.', $filename );
				$extension = strtolower( end( $exploded ) );
			}
			
			if( 'svg' === $extension ){
				
				$data['type'] = 'image/svg+xml';
				$data['ext']  = 'svg';
				
			}elseif( 'svgz' === $extension ){
				
				$data['type'] = 'image/svg+xml';
				$data['ext']  = 'svgz';
			}
			return $data;
		}
		
		/*
		**
		 *   Checking the downloaded file
		 *
		 *   @param $file WP tmp file
		 *
		 *   @return mixed
		 */
		public function svg_upload_process( $file ){
			
			$file_limit = get_option( SVGUPL_PLUGIN_SHORT_NAME . '_file_limit' );
						
			// Check filetype
			if( isset( $file[ 'name' ] ) ){
				
				$filetype = wp_check_filetype_and_ext( $file[ 'tmp_name' ], $file[ 'name' ] );
				
				if( 
					is_array( $filetype ) 
					&& array_key_exists( 'type', $filetype ) 
					&& 'image/svg+xml' === $filetype[ 'type' ] 
					&& empty( $file[ 'error' ] )
				){
					// Check filesize
					if( $file_limit > 0 && $file[ 'size' ] > 0 ){
						
						$filesize_kb = $file[ 'size' ] / 1024;
						
						if( $file_limit < $filesize_kb ){
							
							$file['error'] = sprintf( 
								__( ' Sorry, the allowed file size has been exceeded. Allowed size: %s Kb', 'upload-svg' ), 
								$file_limit 
							);
						}
					}
					
					// Check any type
					if( $this->user_upload_mode != 'sanitized' && $this->user_upload_mode != 'non-sanitized' && empty( $file[ 'error' ] ) ){
						
						$file['error'] = __(
								'Sorry, You cannot upload SVG files. Contact your system administrator',
								'upload-svg'
							);
					}
					
					if( $this->user_upload_mode === 'sanitized' && empty( $file[ 'error' ] ) ){
						
						if( ! $this->action_sanitize( $file['tmp_name'] ) ){
							
							$file[ 'error' ] = __(
								'Could not sanitize this SVG file!',
								'upload-svg'
							);
						}
					}
				}
			}
			return $file;
		}
		
		/*
		**
		 *   Get the SVG file sizes
		 *
		 *   @param $file_path 
		 *
		 *   @return array
		 */
		
		public function svg_sizes( $file_path ){
			
			$svg    = simplexml_load_file( $file_path );
			$width  = 0;
			$height = 0;
			
			if( $svg ){
				
				$attributes = $svg->attributes();
				
				// Data from size
				if( isset( $attributes->width, $attributes->height ) ){
					
					if( substr( trim( $attributes->width ), -1 ) != '%' ){
						
						$width = floatval( $attributes->width );
					}
					if( substr( trim( $attributes->height ), -1 ) != '%' ){
						
						$height = floatval( $attributes->height );
					}
				}
				
				// Data from viewBox
				if( ( ! $width || ! $height ) && isset( $attributes->viewBox ) ){
					
					$sizes = explode( ' ', $attributes->viewBox );
					
					if( isset( $sizes[2], $sizes[3] ) ){
						
						$width  = floatval( $sizes[2] );
						$height = floatval( $sizes[3] );
					}
				}
			}
			return array( 
				'width'       => $width, 
				'height'      => $height,
				'orientation' => ( $width > $height ) ? 'landscape' : 'portrait',
			);
		}
		
		/*
		**
		 *   Get the SVG file sizes
		 *
		 *   @param array $metadata      An array of attachment meta data.
		 *   @param int   $attachment_id Attachment Id to process.
		 *
		 *   @return mixed Metadata for attachment.
		 */
		
		public function svg_attache_meta( $metadata, $attachment_id ){
			
			$mime = get_post_mime_type( $attachment_id );
			
			if( 'image/svg+xml' === $mime ){
				
				$svg_path               = get_attached_file( $attachment_id );
				$upload_dir             = wp_upload_dir();
				
				$relative_path = str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $svg_path );
				$filename      = basename( $svg_path );

				$svg_sizes = $this->svg_sizes( $svg_path );

				if( ! $svg_sizes ){
					
					return $metadata;
				}
				
				$svg_width  = intval( $svg_sizes[ 'width' ] );
				$svg_height = intval( $svg_sizes[ 'height' ] );
			
				$metadata = array(
					'width'  => $svg_width,
					'height' => $svg_height,
					'file'   => $relative_path,
				);
			}
			return $metadata;
		}
		
		/*
		**
		 *   Clearing data
		 *
		 *   @param string $file_url 
		 *
		 *   @return bool
		 */
		
		protected function action_sanitize( $file_url ){
						
			$data = file_get_contents( $file_url );
			
			$is_zipped = $this->is_gzipped( $data );
			if( $is_zipped ){
				
				$data = gzdecode( $data );

				if( $data === false ){
					
					return false;
				}
			}
			
			// load sanitizer
			$this->sanitizer = new \enshrined\svgSanitize\Sanitizer();
			$this->sanitizer->removeXMLTag( true );
			$this->sanitizer->minify( true );

			$clean = $this->sanitizer->sanitize( $data );
			
			if( $clean === false ){
				
				return false;
			}

			if( $is_zipped ){
				$clean = gzencode( $clean );
			}
			
			// Save cleaned file
			file_put_contents( $file_url, $clean );

			return true;
		}
		
		/*
		**
		 *   Check svg data on gzipped
		 *
		 *   @param string $data
		 *
		 *   @return string
		 */
		
		public function is_gzipped( $data ){
			
			if( function_exists('mb_strpos') ){
				
				return 0 === mb_strpos( $data, "\x1f" . "\x8b" . "\x08" );
			}else{
				
				return 0 === strpos( $data, "\x1f" . "\x8b" . "\x08" );
			}
		}
		
		/*
		**
		 *   Disable generate srcset
		 *
		 *   @return array
		 */
		
		public function srcset_off( $image_meta, $size_array, $image_src, $attachment_id ){
			
			if( $attachment_id && 'image/svg+xml' === get_post_mime_type( $attachment_id ) ){
				
				$image_meta['sizes'] = array();
			}
			return $image_meta;
		}
}