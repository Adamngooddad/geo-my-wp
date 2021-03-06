<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GMW_Register_Addon' ) ) {
    return;
}

/**
 * Current Location addon
 * 
 */
class GMW_Sweetdate_Geolcation_Addon extends GMW_Register_Addon {
    
    /**
     * Slug 
     * 
     * @var string
     */
    public $slug = "sweetdate_geolocation";

    /**
     * Name
     * 
     * @var string
     */
    public $name = "Sweet Date Geolocation";

     /**
     * Description
     * 
     * @var string
     */
    public $description = "Enhance the Sweet Date theme with geolocation features.";

    /**
     * prefix
     * 
     * @var string
     */
    public $prefix = "sd";

    // version
    public $version = GMW_VERSION;
     
    /**
     * Path
     * 
     * @var [type]
     */
    public $full_path = __FILE__;
    
    /**
     * Is core add-on
     * 
     * @var boolean
     */
    public $is_core = true;
    
    /**
     * required extensions
     * @var array
     */
    public function required() {

        return array( 
            'theme' => array(
                'template' => 'sweetdate',
                'notice'   => sprintf( __( 'Sweet Date Geolocation extension requires the Sweet Date theme version 2.9 order higher. The theme can be purchased separately from <a href="%s" target="_blank">here</a>.' ), 'https://themeforest.net/item/sweet-date-more-than-a-wordpress-dating-theme/4994573?ref=GEOmyWP', 'GMW' ),
                'version' => '2.9'
            ),
            'addons' => array(
                array(
                    'slug'    => 'members_locator',
                    'notice'  => __( 'Sweet Date Geolocation extension requires the Members Locator core extension.', 'GMW' )
                )
            )
        );
    }

    /**
     * Register scripts
     * 
     * @return [type] [description]
     */
    public function enqueue_scripts() {
        if ( ! IS_ADMIN ) {
    	   wp_register_script( 'gmw-sd', GMW_SD_URL . '/assets/js/gmw.sd.min.js', array( 'jquery', 'gmw' ), GMW_VERSION, true );
        } 
    }

    /**
     * Run on BuddyPress init
     * 
     * @return void
     */
    public function pre_init() {
        parent::pre_init();
    	add_action( 'bp_init', array( $this, 'sd_init' ), 20 );
	}

	/**
	 * Load add-on
	 * 
	 * @return [type] [description]
	 */
    public function sd_init() {

    	//admin settings
		if ( is_admin() ) {
			include( 'includes/admin/class-gmw-sweet-date-admin-settings.php' );
			new GMW_Sweet_Date_Admin_Settings;
		}

		//include members query only on members page
		if ( bp_current_component() == 'members' && gmw_get_option( 'sweet_date','enabled', '' ) != '' ) {
			include( 'includes/class-gmw-sweet-date-geolocation.php' );
			new GMW_Sweet_Date_Geolocation;
		}
    }
}
new GMW_Sweetdate_Geolcation_Addon();