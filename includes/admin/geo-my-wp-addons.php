<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * GMW_Licenses class
 */
class GMW_Addons {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		$this->addons 	= get_option( 'gmw_addons' );
		$this->licenses = get_option( 'gmw_license_keys' );
		$this->statuses = get_option( 'gmw_premium_plugin_status' );
		
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
		
		if ( isset( $_POST['gmw_addon_activated'] ) && !empty( $_POST['gmw_addon_activated'] ) ) 
			self::activate_addon();
		
		if ( isset( $_POST['gmw_addon_deactivated'] ) && !empty( $_POST['gmw_addon_deactivated'] ) )
			self::deactivate_addon();
		
		if ( isset( $_POST['gmw_addon_clear_license_field'] ) && !empty( $_POST['gmw_addon_clear_license_field'] ) )
			self::clear_license_field();
	}
		
	/**
	 * Activate add-on.
	 *
	 * @access private
	 * @return void
	 */
	private function activate_addon() {
		$active_addon = $_POST['gmw_addon_activated'];

		unset( $this->addons[$active_addon] );
		$this->addons[$active_addon] = 'active';
		
		update_option( 'gmw_addons', $this->addons );
	}
	
	/**
	 * deactivate add-on.
	 *
	 * @access private
	 * @return void
	 */
	private function deactivate_addon() {
		$inactive_addon = $_POST['gmw_addon_deactivated'];

		unset( $this->addons[$inactive_addon] );
			
		update_option( 'gmw_addons', $this->addons );
	}
	
	/**
	 * register settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {		
		register_setting ( 'gmw_premium_license', 'gmw_license_keys', array( $this , 'activate_license' ) );
	}
	
	/**
	 * Activate License.
	 *
	 * @access private
	 * @return void
	 */
	public function activate_license( $licenses ) {
		
		// listen for our activate button to be clicked
		if ( !isset( $_POST['gmw_license_key_activate'] ) ) return $licenses;
		
		$add_on = $_POST['gmw_license_key_activate'];
	
		// run a quick security check 
	 	if( !check_admin_referer( $add_on,  $add_on ) ) 	
			return; // get out if we didn't click the Activate button
	
	 	$license_key = ( isset( $licenses[$add_on] ) ) ? $licenses[$add_on] : '';
	 	
	 	$license_key = sanitize_text_field( $license_key );
	 	
		if ( isset( $license_key ) && !empty( $license_key ) ) :
			
			$this_license = trim( $license_key );
			$this_name = ucwords(str_replace('_', ' ', $add_on));
			
			// data to send in our API request
			$api_params = array( 
				'edd_action'=> 'activate_license', 
				'license' 	=> $this_license, 
				'item_name' => urlencode( $this_name ) // the name of our product in EDD
			);
			
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, GMW_REMOTE_SITE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );
			
			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;
	
			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$statuses = get_option( 'gmw_premium_plugin_status' );
			
			$statuses[$add_on] = $license_data->license;
			// $license_data->license will be either "active" or "inactive"
			update_option( 'gmw_premium_plugin_status', $statuses ); 
			
		endif;
				
		return $licenses;		
	}
	
	/**
	 * deactivate License.
	 *
	 * @access private
	 * @return void
	 */
	public function deactivate_license() {
	
		// listen for our activate button to be clicked
		if ( !isset( $_POST['gmw_license_key_deactivate'] ) ) return;
	
		$add_on = $_POST['gmw_license_key_deactivate'];
		
		// run a quick security check
		if( ! check_admin_referer( $add_on, $add_on ) )
			return; // get out if we didn't click the Activate button

		$license_key = ( isset( $_POST['gmw_license_keys'][$add_on] ) ) ? $_POST['gmw_license_keys'][$add_on] : '';
	 	
	 	$license_key = sanitize_text_field( $license_key );

		if ( isset( $license_key ) && !empty( $license_key ) ) :
		
			$this_license = trim( $license_key );
			$this_name 	  = ucwords( str_replace( '_', ' ', $add_on ) );
				
			$api_params = array(
					'edd_action'=> 'deactivate_license',
					'license' 	=> $this_license,
					'item_name' => urlencode( $this_name ) // the name of our product in EDD
			);
					
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, GMW_REMOTE_SITE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );
	
			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;
	
			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				
			// $license_data->license will be either "deactivated" or "failed"
			$statuses = get_option( 'gmw_premium_plugin_status' );
				
			if( $license_data->license == 'deactivated' ) :
				unset( $statuses[$add_on] );
				update_option( 'gmw_premium_plugin_status',$statuses );
			endif;
		
		endif;

	}
	
	/**
	 * Clear license field
	 *
	 * @access private
	 * @return void
	 */
	private function clear_license_field() {
		
		$addon = $_POST['gmw_addon_clear_license_field'];

		unset( $this->licenses[$addon] );
	
		update_option( 'gmw_license_keys', $this->licenses );

	}
	
	/**
	 * Cheack License
	 *
	 * @access private
	 * @return void
	 */
	function check_license() {

		global $wp_version;
	
		$licenses = get_option( 'gmw_license_keys' );
		
		if ( !isset( $licenses ) || empty( $licenses ) ) return;
		
		foreach ( $licenses as $name => $license ) :
			
			$this_license = trim( $license );
			$this_name 	  = ucwords( str_replace( '_', ' ', $name ) );
			
			$api_params = array( 
				'edd_action' => 'check_license', 
				'license' 	 => $this_license, 
				'item_name'  => urlencode( $this_name ) 
			);
	
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, GMW_REMOTE_SITE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );
		
			if ( is_wp_error( $response ) )
				return false;
	
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			
			$statuses = get_option( 'gmw_premium_plugin_status' );
			
			if ( isset( $statuses ) && !empty( $statuses ) ) :
			
				if ( !isset( $license_data ) ) :
					$statuses[$name] = 'inactive';
				else :
					$statuses[$name] = $license_data->license;
				endif;
				// $license_data->license will be either "active" or "inactive"
				update_option( 'gmw_premium_plugin_status', $statuses );
			endif;
			/*
			//print_r($license_data);
			if( $license_data->license == 'valid' ) {
				echo 'valid'; 
			
			// this license is still valid
			} else {
				echo 'invalid'; 
			// this license is no longer valid
			}
			*/
		endforeach;
	}
	
	/**
	 * output function.
	 *
	 * @access public
	 * @return void
	 */
	public function output() {
		
		$addons = array (
				//
		);
		/*
		 * hook your add-on's to GEO my WP's add-ons page
		 * 
		 * append your add-on to the $add-ons array
		 * 
		 * example:
		 * $add-ons = array ( 
		 * 					  'friends' => array( 
		 * 							'name'    => 'friends', 		    //slug
		 * 							'title'   => 'Friends Locator',     //title
		 * 							'desc'    => 'Add-on description',  // description
		 * 							'license' => false 				    //add on requiers licesnse key ?  
		 * 						),
		 *					);
		 */
		$addons = apply_filters( 'gmw_admin_addons_page', $addons );

		?>
		<div class="wrap" style="max-width: 1030px;">
			<a href="http://www.geomywp.com" target="_blank"></a>
			
                        <?php echo GMW_Admin::gmw_credits(); ?>
			
                        <h2 class="gmw-wrap-top-h2"><?php _e('GEO my WP Add-ons','GMW'); ?></h2>
			
			<div class="clear"></div>
			
			<?php 
				if ( isset( $_POST['gmw_license_key_activate'] ) && !empty( $_POST['gmw_license_key_activate'] ) ) {
                                   
					$addon = $_POST['gmw_license_key_activate'];
					if ( $this->statuses[$addon] == 'invalid' ) {
						echo '<div class="updated fade" style="clear:both"><p>' . __( 'Your license key '.$this->licenses[$addon].' is invalid.', 'GMW' ) . '</p></div>';
					}
				}
			?>
				
			<div style="float:left;margin-bottom:10px;">
				<div style="border-left:4px solid red;background: #FDEFEF;" class="gmw-addons-page-top-buttons"><?php _e( 'Add-on Uninstalled / Deactivated', 'GMW' ); ?></div>
				<div style="background: #E8F2F5;border-left:4px solid #2ea2cc;" class="gmw-addons-page-top-buttons"><?php _e( 'Add-on Activated', 'GMW' ); ?></div>
				<div style="border-left:4px solid #37C42A;background: #E9F5E8;" class="gmw-addons-page-top-buttons"><?php _e( 'Add-on and License Activated', 'GMW' ); ?></div>
			</div>
			<br />	
			
			<form method="post" action="options.php">
			
				<?php settings_fields('gmw_premium_license'); ?>
				
				<ul class="widefat fixed">
		         	
		         	<?php $addOnsArray = array(); ksort($addons); ?>
		         	
		         	<?php $count = 1; ?>
		         	
					<?php foreach ( $addons as $addon ) : ?>
						
						<?php $addOnsArray[] = $addon['name']; ?>
						
						<?php $addon_status = ( !isset( $this->addons ) || !isset( $this->addons[$addon['name']] ) ||  $this->addons[$addon['name']] == 'inactive' ) ? 'inactive' : 'active'; ?>
						
						<li class="gmw-single-addon-wrapper <?php echo $addon['name']; ?> first">	
							
							<div class="gmw-addon-top-wrapper">
							
								<h2 class="gmw-addon-title">
									<?php echo $addon['title']; ?>
									<span style="float:right;">
										<?php if ( isset( $addon['version'] ) ) echo $addon['version']; ?>
									</span>
								</h2>
								
							</div>
							
							<div class="gmw-addon-content-wrapper">
							
								<div class="gmw-addon-image-wrapper">
									<?php 
										if ( isset( $addon['image'] ) && !empty( $addon['image'] ) ) {
											echo '<img src="' . $addon['image'].'" />';
										} else {
											echo '<img src="https://geomywp.com/wp-content/uploads/2014/01/no-featured-image.png" />';
										}
									?>
								</div>
								
								<div class="gmw-addon-desc-wrapper">
									<?php echo $addon['desc']; ?>
								</div>
							</div>
							
							<!-- when add-on is deactivated -->
							<?php if ( !isset( $this->addons ) || !isset( $this->addons[$addon['name']] ) ||  $this->addons[$addon['name']] == 'inactive' ) { ?>
									
									<?php wp_nonce_field( $addon['name'], $addon['name'] ); ?>
									
									<div class="gmw-addon-license-wrapper gmw-license-invalid gmw-addon-deactivate">
										
										 <?php 
											 $disabled = '';
											 
											 if ( isset( $addon['require'] ) && !empty( $addon['require'] ) ) {
												 
											 	echo '<div class="gmw-addon-require-wrapper">';
											 	
												 	foreach( $addon['require'] as $key => $require ) {
												 		
												 		if ( !is_plugin_active( $require['plugin_file'] ) ) {
															$disabled = 'disabled="disabled"';
															$link = ( isset( $require['link'] ) && !empty( $require['link'] ) ) ? '<a href="'.$require['link'].'" target="_blank">'.$key.'</a>' : $key;
															
															echo '<span class="gmw-addon-require">require '. $link .'</span>';
														}
														
												 	}
												 	
												echo '</div>';
												 
											 }
										?>
					
										<!-- activate add-on button -->
										<button type="submit" class="button-secondary button-primary gmw-addon-activation-btn" name="gmw_addon_activated" <?php echo $disabled; ?> value="<?php echo $addon['name']; ?>" ><?php _e('Activate Add-on','GMW'); ?></button>
									
									</div>
							
							<!-- when add-on requier license key and key entered and saved in database -->
							<?php } elseif ( isset( $addon['license'] ) && $addon['license'] == true ) { ?>
								
								<!-- if license is valid -->
								<?php if ( isset( $this->statuses[$addon['name']] ) && $this->statuses[$addon['name']] !== false && $this->statuses[$addon['name']] == 'valid' && isset( $this->licenses[$addon['name']] ) && $this->licenses[$addon['name']] != '' ) { ?>
								
									<?php wp_nonce_field( $addon['name'], $addon['name'] ); ?>
								
									<div class="gmw-addon-license-wrapper gmw-license-valid gmw-addon-activate">
						
										<input class="gmw_license_keys" disabled="disabled" type="text" class="regular-text" placeholder="<?php _e('Enter your license key','GMW'); ?>" style="margin-bottom: 5px;width:100%;padding:5px" value="<?php if ( isset( $this->licenses[$addon['name']] ) && !empty( $this->licenses[$addon['name']] ) ) echo $this->licenses[$addon['name']]; ?>" />
										
										<input type="hidden" name="gmw_license_keys[<?php echo $addon['name']; ?>]" value="<?php if ( isset($this->licenses[$addon['name']]) && !empty($this->licenses[$addon['name']]) ) echo $this->licenses[$addon['name']]; ?>" />
										
										<!-- show disctivate license button -->
										<button type="submit" class="button-secondary gmw-addon-activation-btn" style="opacity:0;" name="gmw_license_key_deactivate" value="<?php echo $addon['name']; ?>" ><?php _e('Deactivate License','GMW'); ?></button>
																																								
									</div>
								
								<!-- if status invalid -->
								<?php } else { ?>

									<?php wp_nonce_field( $addon['name'], $addon['name'] ); ?>
								
									<div class="gmw-addon-license-wrapper gmw-license-invalid gmw-addon-activate">
							
										<input class="gmw_license_keys gmw-addon-short-input" name="gmw_license_keys[<?php echo $addon['name']; ?>]" type="text" class="regular-text" placeholder="<?php _e('Enter your license key','GMW'); ?>" style="margin-bottom: 5px;width:100%;padding:5px" value="<?php if ( isset( $this->licenses[$addon['name']] ) && !empty( $this->licenses[$addon['name']] ) ) echo $this->licenses[$addon['name']]; ?>" />
										<button type="submit" class="button-secondary remove-license-key" style="float: right;margin-top:2px" name="gmw_addon_clear_license_field" title="<?php _e( 'clear license field', 'GMW' ); ?>" value="<?php echo $addon['name']; ?>" >x</button>
																						
										<button type="submit" class="button-primary activate-license-btn gmw-addon-activation-btn" style="opacity:0;" name="gmw_license_key_activate" value="<?php echo $addon['name']; ?>"><?php _e('Activate License','GMW'); ?></button>
										
										<button type="submit" class="button-secondary gmw-addon-activation-btn" style="opacity:0;margin-right:5px;" name="gmw_addon_deactivated" value="<?php echo $addon['name']; ?>" ><?php _e('Deactivate Add-on','GMW'); ?></button>
																																	
									</div>
							
								<?php }  ?>
								
							<?php } else { ?>
						
								<div class="gmw-addon-license-wrapper gmw-addon-activate">
							
									<input class="gmw_license_keys" name="gmw_license_keys[<?php echo $addon['name']; ?>]" type="text" class="regular-text" disabled="disabled" placeholder="<?php _e('No license key required','GMW'); ?>" style="margin-bottom: 5px;width:100%;padding:5px" value="<?php if ( isset($this->licenses[$addon['name']]) && !empty($this->licenses[$addon['name']]) ) echo $this->licenses[$addon['name']]; ?>" />
									
									<button type="submit" class="button-secondary gmw-addon-activation-btn" style="opacity:0;" name="gmw_addon_deactivated" value="<?php echo $addon['name']; ?>" ><?php _e('Deactivate Add-on','GMW'); ?></button>									
								</div>
						
							<?php } ?>
							
						</li>
						<?php if ( $count % 3 == 0 ) echo '<div class="clear"></div>'; $count++; ?>
					
					<?php endforeach; ?>
					
					<?php $this->output_feed_addons(); ?>
				</ul>
				
			</form>
			
		</div>
		
		<script>
			jQuery(document).ready(function($) {

				var addonsArray = JSON.parse('<?php echo json_encode($addOnsArray); ?>');
				
				$.each( addonsArray, function (i, ob) {
				       $('.'+ob).each(function() {
					       if ( !$(this).hasClass('first') ) $(this).hide();
				       });
				});
				
			    $('.gmw-single-addon-wrapper').mouseenter(function(){
			        $('.gmw-addon-desc-wrapper, .gmw-addon-activate-btn, .gmw-addon-deactivate-btn', this).stop(true,true).fadeToggle();
			        $('.gmw-addon-image-wrapper', this ).stop(true,true).animate({opacity: 0.1});
			    });
                            $(".gmw-single-addon-wrapper").mouseleave(function(){
			        $('.gmw-addon-desc-wrapper, .gmw-addon-activate-btn, .gmw-addon-deactivate-btn', this).stop(true,true).fadeToggle();
			        $('.gmw-addon-image-wrapper', this ).stop(true,true).animate({opacity: 1});
			    });
                               
			    $(".gmw-addon-activate").mouseenter(function(){
                                $(this).stop(true,true).animate({height: '60px'});
                                $('.gmw-addon-activation-btn', this ).stop(true,true).animate({opacity: 1}); 
                            });
                            $(".gmw-addon-activate").mouseleave(function(){
                                $(this).stop(true,true).animate({height: '30px'});
			    	 $('.gmw-addon-activation-btn', this ).stop(true,true).animate({opacity: 0});
                            });

                            $('.gmw_license_keys').focus(function() {
                                    $(this).removeClass('mandatory');
                            });

                            $('.gmw-addon-require-wrapper').each(function() {
                                    if ( $(this).find('.gmw-addon-require').length ) $(this).show();
                            });

                            $('.remove-license-key').click(function() {
                                    $(this).closest('div').find('.gmw_license_keys').val('');
			    });
			    
                            if ( $('.gmw-addon-require').length > 0 ) {

                            }

                            $('.activate-license-btn').click(function(e) {					
                                    if ( jQuery.trim( $(this).closest('.gmw-addon-license-wrapper').find('.gmw_license_keys').val() ).length <= 0 ) {
                                            $(this).closest('.gmw-addon-license-wrapper').find('.gmw_license_keys').addClass('mandatory');
                                            e.preventDefault();
                                    }
                            });
			});
		</script>
		<?php
	}
	
	private function output_feed_addons() {
		
		if ( false === ( $cache = get_transient( 'gmw_add_ons_feed' ) ) ) {
			
			$feed = wp_remote_get( 'http://geomywp.com/add-ons/?feed=gmw_addons', array( 'sslverify' => false ) );
			
			if ( ! is_wp_error( $feed ) ) {	
				if ( isset( $feed['body'] ) && strlen( $feed['body'] ) > 0 ) {
					$cache = wp_remote_retrieve_body( $feed );
					set_transient( 'gmw_add_ons_feed', $cache, 3600 );
				}	
			} else {
				
				$cache = '<div class="error"><p>' . __( 'There was an error retrieving the extensions list from the server. Please try again later.', 'edd' ) . '</div>';
				
			}
		
		}
		echo $cache;
	}
}