<?php
/**
 * Gravity Forms Paynamics Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2009 - 2018, Rocketgenius
 */

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class GFPaynamics
 *
 * Primary class to manage the Paynamics add-on.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
 */
class GFPaynamics extends GFPaymentAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @used-by GFPaynamics::get_instance()
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Paynamics Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GFPaynamics::scripts()
	 *
	 * @var string $_version Contains the version, defined from Paynamics.php
	 */
	protected $_version = GF_PAYNAMICS_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.17';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformspaynamics';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformspaynamics/paynamics.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_url The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Paynamics Add On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'Paynamics';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_enable_rg_autoupgrade true
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines if user will not be able to create feeds for a form until a credit card field has been added.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_requires_credit_card true.
	 */
	protected $_requires_credit_card = false;

	/**
	 * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_supports_callbacks true
	 */
	protected $_supports_callbacks = false;

	/**
	 * Paynamics requires monetary amounts to be formatted as the smallest unit for the currency being used e.g. cents.
	 *
	 * @since  1.10.1
	 * @access protected
	 *
	 * @var bool $_requires_smallest_unit true
	 */
	protected $_requires_smallest_unit = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_paynamics';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_paynamics';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_paynamics_uninstall';

	/**
	 * Defines the capabilities needed for the Paynamics Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_paynamics', 'gravityforms_paynamics_uninstall' );

	/**
	 * Holds the custom meta key currently being processed. Enables the key to be passed to the gform_Paynamics_field_value filter.
	 *
	 * @since  2.1.1
	 * @access protected
	 *
	 * @used-by GFPaynamics::maybe_override_field_value()
	 *
	 * @var string $_current_meta_key The meta key currently being processed.
	 */
	protected $_current_meta_key = '';
    
    // protected $redirect_url = $this->get_plugin_setting( 'frontend_url');
	/**
	 * Get an instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFPaynamics
	 * @uses GFPaynamics::$_instance
	 *
	 * @return object GFPaynamics
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GFPaynamics();
		}

		return self::$_instance;

	}


	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFAddOn::maybe_save_plugin_settings()
	 * @used-by GFAddOn::plugin_settings_page()
	 * @uses    GFPaynamics::api_settings_fields()
	 * @uses    GFPaynamics::get_webhooks_section_description()
	 *
	 * @return array Plugin settings fields to add.
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'Paynamics API', 'gravityformspaynamics' ),
				'fields' => $this->api_settings_fields(),
			),
			array(
				'title'  => esc_html__( 'Paynamics Url', 'gravityformspaynamics' ),
				'fields' => $this->paynamics_url_settings_fields(),
			),
			array(
				'title'  => esc_html__( 'Paynamics Logo transactions', 'gravityformspaynamics' ),
				'fields' => $this->paynamics_logo_settings_fields(),
			),
			
		);

	}

	/**
	 * Define the settings which appear in the Paynamics API section.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaynamics::plugin_settings_fields()
	 *
	 * @return array The API settings fields.
	 * 
	 */
	 
	public function api_settings_fields() {

		return array(
			array(
				'name'     => 'merchant_id',
				'label'    => esc_html__( 'Paynamics Merchant ID', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),
			array(
				'name'     => 'merchant_key',
				'label'    => esc_html__( 'Paynamics Merchant Key', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),
			array(
				'name'     => 'frontend_url',
				'label'    => esc_html__( 'Paynamics Redirect  URL', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
				
			),
			
		);

	}
	
	public function paynamics_url_settings_fields() {

		return array(
			array(
				'name'     => 'notif_url',
				'label'    => esc_html__( 'Paynamics Notification url', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),
			array(
				'name'     => 'res_url',
				'label'    => esc_html__( 'Paynamics Response Url', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),
			array(
				'name'     => 'cancel_url',
				'label'    => esc_html__( 'Paynamics Cancel  URL', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
				
			),
			
		);

	}
	
	public function paynamics_logo_settings_fields() {

		return array(
			array(
				'name'     => 'logo_url',
				'label'    => esc_html__( 'Paynamics Redirect logo', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),

			
		);

	}


	/**
	 * Prevent feeds being listed or created if the API keys aren't valid.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFeedAddOn::feed_edit_page()
	 * @used-by GFFeedAddOn::feed_list_message()
	 * @used-by GFFeedAddOn::feed_list_title()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFPaynamics::get_api_mode()
	 *
	 * @return bool True if feed creation is allowed. False otherwise.
	 
	 
	public function can_create_feed() {

		// Get plugin settings and API mode.
		$settings = $this->get_plugin_settings();
		$api_mode = $this->get_api_mode( $settings );

		// Return valid key state based on API mode.
		if ( 'live' === $api_mode ) {
			return rgar( $settings, 'live_api_merchant_key_is_valid' ) && rgar( $settings, 'live_api_merchant_id_is_valid' ) && rgar( $settings, 'live_frontend_url_is_valid' );
		} else {
			return rgar( $settings, 'test_api_merchant_key_is_valid'  ) && rgar( $settings, 'test_api_merchant_id_is_valid' ) && rgar( $settings, 'test_frontend_url_is_valid' );
		}

	}*/

	/**
	 * Enable feed duplication on feed list page and during form duplication.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return false
	 */
	public function can_duplicate_feed( $id ) {

		return false;

	}

	/**
	 * Define the markup for the field_map setting table header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The header HTML markup.
	 */
	public function field_map_table_header() {
		return '<thead>
					<tr>
						<th></th>
						<th></th>
					</tr>
				</thead>';
	}


	/**
	 * Prevent the 'options' checkboxes setting being included on the feed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::other_settings_fields()
	 *
	 * @return false
	 */
	public function option_choices() {
		return false;
	}



	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add supported notification events.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFeedAddOn::notification_events()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 
	public function supported_notification_events( $form ) {

		// If this form does not have a Paynamics feed, return false.
		if ( ! $this->has_feed( $form['id'] ) ) {
			return false;
		}

		// Return Paynamics notification events.
		return array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'gravityformspaynamics' ),
			'refund_payment'            => esc_html__( 'Payment Refunded', 'gravityformspaynamics' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'gravityformspaynamics' ),
		);

	}*/





	// # FRONTEND ------------------------------------------------------------------------------------------------------

	/**
	 * Initialize the frontend hooks.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaynamics::register_init_scripts()
	 * @uses GFPaynamics::add_Paynamics_inputs()
	 * @uses GFPaynamics::pre_validation()
	 * @uses GFPaynamics::populate_credit_card_last_four()
	 * @uses GFPaymentAddOn::init()
	 *
	 * @return void
	 */
	public function init() {
	    
	   // add_filter( 'gform_field_content', array( $this, 'add_paynamics_inputs' ), 10, 5 );

        // add_action( 'gform_after_submission', array( $this, 'paynamics_after_submission' ), 10, 3 );
        
        add_filter( 'gform_after_submission', array( $this, 'paynamics_after_submission' ), 10, 3 );
		//add_filter( 'gform_confirmation', 'custom_confirmation', 10, 4 );
		
		parent::init();

	}

	
    
    
    public function feed_settings_fields() {

		// Get default payment feed settings fields.
		$default_settings = parent::feed_settings_fields();

		// Prepare customer information fields.
		$customer_info_field = array(
			'name'       => 'customerInformation',
			'label'      => esc_html__( 'Customer Information', 'gravityformspaynamics' ),
			'type'       => 'field_map',
			'dependency' => array(
				'field'  => 'transactionType',
				'values' => array( 'subscription' ),
			),
			'field_map'  => array(
				array(
					'name'       => 'email',
					'label'      => esc_html__( 'Email', 'gravityformspaynamics' ),
					'required'   => true,
					'field_type' => array( 'email', 'hidden' ),
				),
				array(
					'name'     => 'description',
					'label'    => esc_html__( 'Description', 'gravityformspaynamics' ),
					'required' => false,
				),
				array(
					'name'       => 'coupon',
					'label'      => esc_html__( 'Coupon', 'gravityformspaynamics' ),
					'required'   => false,
					'field_type' => array( 'coupon', 'text' ),
					'tooltip'    => '<h6>' . esc_html__( 'Coupon', 'gravityformspaynamics' ) . '</h6>' . esc_html__( 'Select which field contains the coupon code to be applied to the recurring charge(s). The coupon must also exist in your Stripe Dashboard.', 'gravityformsstripe' ),
				),
			),
		);
		
		// Replace default billing information fields with customer information fields.
		$default_settings = $this->replace_field( 'billingInformation', $customer_info_field, $default_settings );

		// Define end of Metadata tooltip based on transaction type.
		if ( 'subscription' === $this->get_setting( 'transactionType' ) ) {
			$info = esc_html__( 'You will see this data when viewing a customer page.', 'gravityformspaynamics' );
		} else {
			$info = esc_html__( 'You will see this data when viewing a payment page.', 'gravityformspaynamics' );
		}
		
		

		// Prepare meta data field.
		$custom_meta = array(
			array(
				'name'                => 'metaData',
				'label'               => esc_html__( 'Metadata', 'gravityformspaynamics' ),
				'type'                => 'dynamic_field_map',
				'limit'				  => 20,
				'exclude_field_types' => 'creditcard',
				'tooltip'             => '<h6>' . esc_html__( 'Metadata', 'gravityformspaynamics' ) . '</h6>' . esc_html__( 'You may send custom meta information to Stripe. A maximum of 20 custom keys may be sent. The key name must be 40 characters or less, and the mapped data will be truncated to 500 characters per requirements by Paynamics. ' . $info , 'gravityformspaynamics' ),
				'validation_callback' => array( $this, 'validate_custom_meta' ),
			),
		);
        
		// Add meta data field.
		$default_settings = $this->add_field_after( 'customerInformation', $custom_meta, $default_settings );
		
        $paynamics_url_fields = array(
            // array(
            //     'name'     => 'sec3d',
            //     'label'    => __( 'sec3d ', 'gravityformspaynamics' ),
            //     'type'     => 'text',
            //     'class'    => 'medium',
            //     'required' => true,
            //     'tooltip'  => '<h6>' . __( 'sec3d', 'gravityformspaynamics' ) . '</h6>' . __( 'Enter the sec3d.', 'gravityformspaynamics' )
            // ),
            array(
                'name'     => 'server_ip',
                'label'    => __( 'Server IP ', 'gravityformspaynamics' ),
                'type'     => 'text',
                'class'    => 'medium',
                'value'    => $_SERVER['SERVER_ADDR'],
                'required' => true,
                'tooltip'  => '<h6>' . __( 'Server IP', 'gravityformspaynamics' ) . '</h6>' . __( 'Enter the Server IP', 'gravityformspaynamics' )
            ),
        );
 
        $default_settings = $this->add_field_after( 'customerInformation', $paynamics_url_fields, $default_settings );



		return $default_settings;

	}
    
    
    
    public function paynamics_after_submission($entry, $form, $feed) {
        

        $_mid           = $this->get_plugin_setting( 'merchant_id');
        $_requestid     = substr(uniqid(), 0, 13);
        $_ipaddress     = $_SERVER['SERVER_ADDR'];
        $_noturl        = $this->get_plugin_setting( 'notif_url');
        $_resurl        = $this->get_plugin_setting( 'res_url');
        $_cancelurl     = $this->get_plugin_setting( 'cancel_url');
        $_fname         = rgar( $entry, '1.3' );
        $_mname         = rgar( $entry, '1.4' );
        $_lname         = rgar( $entry, '1.6' );
        $_addr1         = rgar( $entry, '14.1' );
        $_addr2         = rgar( $entry, '14.2' );
        $_city          = rgar( $entry, '14.3' );
        $_state         = rgar( $entry, '14.4' );
        $_country       = rgar( $entry, '14.6' );
        $_zip           = rgar( $entry, '14.5' );
        $_sec3d         = rgar( $entry, '17' );
        $_email         = rgar( $entry, '2' );
        $_phone         = "";
        $_mobile        = rgar( $entry, '3' );
        $_clientip      = $_SERVER['REMOTE_ADDR'];
        $_amount        = rgar( $entry, '8' );
        $_currency      = "PHP"; 
        $cert           = $this->get_plugin_setting( 'merchant_key');
        $_logo          = $this->get_plugin_setting( 'logo_url');
        $paynamics_url  = $this->get_plugin_setting( 'frontend_url');
        
        
        $forSign = $_mid . $_requestid . $_ipaddress . $_noturl . $_resurl .  $_fname . $_lname . $_mname . $_addr1 . $_addr2 . $_city . $_state . $_country . $_zip . $_email . $_phone . $_clientip . $_amount . $_currency . $_sec3d;
        
        
        $_sign = hash("sha512", $forSign.$cert);
        
        
        $xmlstr = "";
        
        $strxml = "";
        
        $strxml = $strxml . "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
        $strxml = $strxml . "<Request>";
        $strxml = $strxml . "<orders>";
        $strxml = $strxml . "<items>";
        $strxml = $strxml . "<Items>";
        $strxml = $strxml . "<itemname>Donation</itemname><quantity>1</quantity><amount>" . $_amount . "</amount>";
        $strxml = $strxml . "</Items>";
        $strxml = $strxml . "</items>";
        $strxml = $strxml . "</orders>";
        $strxml = $strxml . "<mid>" . $_mid . "</mid>";
        $strxml = $strxml . "<request_id>" . $_requestid . "</request_id>";
        $strxml = $strxml . "<ip_address>" . $_ipaddress . "</ip_address>";
        $strxml = $strxml . "<notification_url>" . $_noturl . "</notification_url>";
        $strxml = $strxml . "<response_url>" . $_resurl . "</response_url>";
        $strxml = $strxml . "<cancel_url>" . $_cancelurl . "</cancel_url>";
        $strxml = $strxml . "<mtac_url>http://www.paynamics.com/index.html</mtac_url>";
        $strxml = $strxml . "<descriptor_note>'My Descriptor .18008008008'</descriptor_note>";
        $strxml = $strxml . "<fname>" . $_fname . "</fname>";
        $strxml = $strxml . "<lname>" . $_lname . "</lname>";
        $strxml = $strxml . "<mname>" . $_mname . "</mname>";
        $strxml = $strxml . "<address1>" . $_addr1 . "</address1>";
        $strxml = $strxml . "<address2>" . $_addr2 . "</address2>";
        $strxml = $strxml . "<city>" . $_city . "</city>";
        $strxml = $strxml . "<state>" . $_state . "</state>";
        $strxml = $strxml . "<country>" . $_country . "</country>";
        $strxml = $strxml . "<zip>" . $_zip . "</zip>";
        $strxml = $strxml . "<secure3d>" . $_sec3d . "</secure3d>";
        $strxml = $strxml . "<trxtype>sale</trxtype>";
        $strxml = $strxml . "<email>" . $_email . "</email>";
        $strxml = $strxml . "<phone>" . $_phone . "</phone>";
        $strxml = $strxml . "<mobile>" . $_mobile . "</mobile>";
        $strxml = $strxml . "<client_ip>" . $_clientip . "</client_ip>";
        $strxml = $strxml . "<amount>" . $_amount . "</amount>";
        $strxml = $strxml . "<currency>" . $_currency . "</currency>";
        $strxml = $strxml . "<mlogo_url>" . $_logo . "</mlogo_url>";
        $strxml = $strxml . "<pmethod></pmethod>";//CC, GC, PP, DP
        $strxml = $strxml . "<signature>" . $_sign . "</signature>";
        $strxml = $strxml . "</Request>";
        $b64string =  base64_encode($strxml);
        
        
        // $this->log_debug( __METHOD__ . '(): feed => ' . print_r( $strxml, true ) );
        
        $request = new WP_Http();
        $response = $request->post( $paynamics_url, array( 'body' => $b64string ) );
        $this->log_debug( __METHOD__ . '(): body => ' . print_r( $response, true ) );
        
    }
    
    
    /*
    public function custom_confirmation($confirmation, $form, $entry) {
        
        $_mid           = $this->get_plugin_setting( 'merchant_id');
        $_requestid     = substr(uniqid(), 0, 13);
        $_ipaddress     = $_SERVER['SERVER_ADDR'];
        $_noturl        = $this->get_plugin_setting( 'notif_url');
        $_resurl        = $this->get_plugin_setting( 'res_url');
        $_cancelurl     = $this->get_plugin_setting( 'cancel_url');
        $_fname         = rgar( $entry, '1.3' );
        $_mname         = rgar( $entry, '1.4' );
        $_lname         = rgar( $entry, '1.6' );
        $_addr1         = rgar( $entry, '14.1' );
        $_addr2         = rgar( $entry, '14.2' );
        $_city          = rgar( $entry, '14.3' );
        $_state         = rgar( $entry, '14.4' );
        $_country       = rgar( $entry, '14.6' );
        $_zip           = rgar( $entry, '14.5' );
        $_sec3d         = rgar( $entry, '17' );
        $_email         = rgar( $entry, '2' );
        $_phone         = "";
        $_mobile        = rgar( $entry, '3' );
        $_clientip      = $_SERVER['REMOTE_ADDR'];
        $_amount        = rgar( $entry, '8' );
        $_currency      = "PHP"; 
        $cert           = $this->get_plugin_setting( 'merchant_key');
        $_logo          = $this->get_plugin_setting( 'logo_url');
        $paynamics_url  = $this->get_plugin_setting( 'frontend_url');
        
        // $paynamics_url  = "https://testpti.payserv.net/webpaymentv2/Default.aspx";
        
        
        
        
        $forSign = $_mid . $_requestid . $_ipaddress . $_noturl . $_resurl .  $_fname . $_lname . $_mname . $_addr1 . $_addr2 . $_city . $_state . $_country . $_zip . $_email . $_phone . $_clientip . $_amount . $_currency . $_sec3d;
        
        
        $_sign = hash("sha512", $forSign.$cert);
        
        
        $xmlstr = "";
        
        $strxml = "";
        
        $strxml = $strxml . "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
        $strxml = $strxml . "<Request>";
        $strxml = $strxml . "<orders>";
        $strxml = $strxml . "<items>";
        $strxml = $strxml . "<Items>";
        $strxml = $strxml . "<itemname>item 1</itemname><quantity>1</quantity><amount>1.00</amount>";
        $strxml = $strxml . "</Items>";
        $strxml = $strxml . "</items>";
        $strxml = $strxml . "</orders>";
        $strxml = $strxml . "<mid>" . $_mid . "</mid>";
        $strxml = $strxml . "<request_id>" . $_requestid . "</request_id>";
        $strxml = $strxml . "<ip_address>" . $_ipaddress . "</ip_address>";
        $strxml = $strxml . "<notification_url>" . $_noturl . "</notification_url>";
        $strxml = $strxml . "<response_url>" . $_resurl . "</response_url>";
        $strxml = $strxml . "<cancel_url>" . $_cancelurl . "</cancel_url>";
        $strxml = $strxml . "<mtac_url>http://www.paynamics.com/index.html</mtac_url>";
        $strxml = $strxml . "<descriptor_note>'My Descriptor .18008008008'</descriptor_note>";
        $strxml = $strxml . "<fname>" . $_fname . "</fname>";
        $strxml = $strxml . "<lname>" . $_lname . "</lname>";
        $strxml = $strxml . "<mname>" . $_mname . "</mname>";
        $strxml = $strxml . "<address1>" . $_addr1 . "</address1>";
        $strxml = $strxml . "<address2>" . $_addr2 . "</address2>";
        $strxml = $strxml . "<city>" . $_city . "</city>";
        $strxml = $strxml . "<state>" . $_state . "</state>";
        $strxml = $strxml . "<country>" . $_country . "</country>";
        $strxml = $strxml . "<zip>" . $_zip . "</zip>";
        $strxml = $strxml . "<secure3d>" . $_sec3d . "</secure3d>";
        $strxml = $strxml . "<trxtype>sale</trxtype>";
        $strxml = $strxml . "<email>" . $_email . "</email>";
        $strxml = $strxml . "<phone>" . $_phone . "</phone>";
        $strxml = $strxml . "<mobile>" . $_mobile . "</mobile>";
        $strxml = $strxml . "<client_ip>" . $_clientip . "</client_ip>";
        $strxml = $strxml . "<amount>" . $_amount . "</amount>";
        $strxml = $strxml . "<currency>" . $_currency . "</currency>";
        $strxml = $strxml . "<mlogo_url>" . $_logo . "</mlogo_url>";
        $strxml = $strxml . "<pmethod></pmethod>";//CC, GC, PP, DP
        $strxml = $strxml . "<signature>" . $_sign . "</signature>";
        $strxml = $strxml . "</Request>";
        $b64string =  base64_encode($strxml);
        
        
        // $request = new WP_Http();
        // $response = $request->post( $paynamics_url, array( 'body' => $b64string ) );
        // $this->log_debug( __METHOD__ . '(): feed => ' . print_r( $paynamics_url, true ) );
        
        
        GFCommon::log_debug( 'gform_confirmation: body => ' . print_r( $b64string, true ) );
 
        $request  = new WP_Http();
        $response = $request->post( $paynamics_url, array( 'body' => $b64string ) );
        GFCommon::log_debug( 'gform_confirmation: response => ' . print_r( $response, true ) );
    
        
        return $confirmation;
        
    }
    */
    
}