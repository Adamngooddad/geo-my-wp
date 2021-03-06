<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * GMW_Sweet_Date_Admin class
 */
class GMW_Sweet_Date_Admin_settings {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {

        $this->settings = get_option( 'gmw_options' );
        
        //create post types settings tab/group 
        add_filter( 'gmw_admin_settings_groups', array( $this, 'admin_settings_group' ), 5 );
        add_filter( 'gmw_admin_settings', array( $this, 'settings_init' ) );
        add_filter( 'admin_footer', array( $this, 'footer_script' ) );

        if ( empty( $this->settings['sweet_date'] ) ) {
            add_filter( 'admin_init', array( $this, 'default_options' ) );
        }   
    }
   
    /**
     * Set default values if not exists
     * 
     */
    public function default_options() {

    	$this->settings['sweet_date'] = array(
    		'enabled'	    		=> 1,
    		'address_autocomplete'  => 1,
			'radius'     			=> '10,25,50,100,200',
			'units'      			=> '3959',
			'orderby'				=> 1,
			'map'        		    => 1,
			'map_width'  			=> '100%',
			'map_height' 			=> '300px',
			'map_type'   			=> 'ROADMAP',
			'distance'   			=> 1,
			'address'    			=> 1,
			'directions_link' 		=> 1
    	);
    	
    	update_option( 'gmw_options', $this->settings );
    }

    /**
     * Create Post Types settings group
     * 
     * @param  [type] $groups [description]
     * @return [type]         [description]
     */
    public function admin_settings_group( $groups ) {

        $groups[] = array(
            'id'    => 'sweet_date',
            'label' => __( 'Sweet Date', 'GMW' ),
            'icon'  => 'location-outline'
        );  

        return $groups;
    }

    /**
     * addon settings page function.
     *
     * @access public
     * @return $settings
     */
    public function settings_init( $settings ) {

    	$settings['sweet_date'] = array(
    		'enabled' => array(
				'name'       => 'enabled',
				'type'       => 'checkbox',
				'cb_label'   => 'Enabled',
				'default'    => '',
				'label'      => __( 'Enable Geolocation', 'GMW' ),
				'desc'       => __( 'Enable/disable the geolocation features in the Members Directory page of the Sweet Date theme.', 'GMW' ),
				'attributes' => array(),
				'priority'	 => 10
			),
			'address_autocomplete' => array(
				'name'       => 'address_autocomplete',
				'type'       => 'checkbox',
				'cb_label'   => 'Enabled',
				'default'    => '',
				'label'      => __( 'Address Autocomplete', 'GMW' ),
				'desc'       => __( 'Enable address autocomplete feature in the address field.', 'GMW' ),
				'attributes' => array(),
				'priority'	 => 20
			),
			'radius'     => array(
				'name'        => 'radius',
				'type'        => 'text',
				'default'     => '10,25,50,100,200',
				'label'       => __( 'Radius', 'GMW' ),
				'placeholder' => __( 'Enter radius values', 'GMW' ),
				'desc'        => __( 'Enter a single numeric value to be used as the default, or multiple values, comma separated, that will be displayed as a dropdown select box in the search form.', 'GMW' ),
				'attributes' => array(),
				'priority'	 => 30
			),
			'units'      => array(
				'name'       => 'units',
				'type'       => 'select',
				'default'    => '3959',
				'label'      => __( 'Distance Units', 'GMW' ),
				'desc'       => __( 'Select miles or kilometers.', 'GMW' ),
				'options'    => array(
					'3959' => __( 'Miles', 'GMW' ),
					'6371' => __( 'Kilometers', 'GMW' ),
				),
				'attributes' => array(),
				'priority'	 => 40
			),
			'orderby'  	  => array(
				'name'       => 'orderby',
				'type'       => 'checkbox',
				'cb_label'   => 'Enabled',
				'default'    => '',
				'label'      => __( 'Orderby Filter', 'GMW' ),
				'desc'       => __( 'Enable Orderby dropdown menu in the search form.', 'GMW' ),
				'attributes' => array(),
				'priority'	 => 50
			),
			'map'    => array(
				'name'       => 'map',
				'type'       => 'checkbox',
				'default'    => '',
				'cb_label'   => 'Enabled',
				'label'      => __( 'Google Map', 'GMW' ),
				'desc'       => __( 'Enable Google map above list of members.', 'GMW' ),
				'attributes' => array(),
				'priority'	 => 60
			),
			'map_width'  => array(
				'name'        => 'map_width',
				'type'        => 'text',
				'default'     => '100%',
				'label'       => __( 'Map Width', 'GMW' ),
				'placeholder' => __( 'Enter map width', 'GMW' ),
				'desc'        => __( 'Map width in pixels or percentage ( ex. 100% or 200px ).', 'GMW' ),
				'attributes'  => array(),
				'priority'	  => 70
				
			),
			'map_height' => array(
				'name'        => 'map_height',
				'type'        => 'text',
				'default'     => '300px',
				'label'       => __( 'Map Height', 'GMW' ),
				'placeholder' => __( 'Enter map height', 'GMW' ),
				'desc'        => __( 'Map height in pixels or percentage ( ex. 100% or 200px ).', 'GMW' ),
				'attributes'  => array(),
				'priority'	  => 80
			),
			'map_type'   => array(
				'name'        => 'map_type',
				'type'        => 'select',
				'default'     => 'ROADMAP',
				'label'       => __( 'Map Type', 'GMW' ),
				'desc'        => __( 'Select the map type.', 'GMW' ),
				'options'     => array(
					'ROADMAP'   => __( 'ROADMAP', 'GMW' ),
					'SATELLITE' => __( 'SATELLITE', 'GMW' ),
					'HYBRID'    => __( 'HYBRID', 'GMW' ),
					'TERRAIN'   => __( 'TERRAIN', 'GMW' ),
				),
				'attributes'  => array(),
				'priority'	  => 90
			),
			'distance'   => array(
				'name'        => 'distance',
				'type'        => 'checkbox',
				'default'     => '',
				'label'       => __( 'Distance', 'GMW' ),
				'cb_label'    => __( 'Enabled', 'GMW' ),
				'desc'        => __( 'Display the distance to each member in the list of results.', 'GMW' ),
				'attributes'  => array(),
				'priority'	  => 100
			),
			'address'    => array(
				'name'        => 'address',
				'default'     => '',
				'label'       => __( 'Address', 'GMW' ),
				'cb_label'    => __( 'Enabled', 'GMW' ),
				'desc'        => __( 'Display the address of each member in the list of results.', 'GMW' ),
				'type'        => 'checkbox',
				'attributes'  => array(),
				'priority'	  => 110
			),
			'directions_link' => array(
				'name'        => 'directions_link',
				'type'        => 'checkbox',
				'default'     => '',
				'label'       => __( 'Directions Link', 'GMW' ),
				'cb_label'    => __( 'Enabled', 'GMW' ),
				'desc'        => __( 'Display directions link, which will open a new window with Google map showing the driving directions, in each member in the list of results.', 'GMW' ),
				'attributes'  => array(),
				'priority'	  => 120
			),
    	);

    	return $settings;
    }

    /**
     * JavaScripts 
     * @return [type] [description]
     */
    function footer_script() {
    	?>
		<script>
		jQuery( document ).ready( function( $ ) {

			function hideRows() {
				$( "#sweet_date-enabled-tr" ).show().siblings().toggle();
			}

			if ( ! $( '#setting-sweet_date-enabled' ).is( ':checked' ) ) {
				hideRows();
			}

			$( '#setting-sweet_date-enabled' ).on( 'change', function() {
				hideRows();
			});
		} );
		</script>
		<?php
    }
}