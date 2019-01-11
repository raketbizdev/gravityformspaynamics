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
class GFPaynamics extends GFPaymentAddOn {
	private static $_instance = null;
	protected $_version = GF_PAYNAMICS_VERSION;
	protected $_min_gravityforms_version = '1.9.14.17';
	protected $_slug = 'gravityformspaynamics';
	protected $_path = 'gravityformspaynamics/paynamics.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Paynamics Add On';
	protected $_short_title = 'Paynamics';
	protected $_enable_rg_autoupgrade = true;
	protected $_requires_credit_card = false;
	protected $_supports_callbacks = false;
	protected $_requires_smallest_unit = true;
	protected $_capabilities_settings_page = 'gravityforms_paynamics';
	protected $_capabilities_form_settings = 'gravityforms_paynamics';
	protected $_capabilities_uninstall = 'gravityforms_paynamics_uninstall';
	protected $_capabilities = array( 'gravityforms_paynamics', 'gravityforms_paynamics_uninstall' );
	protected $_current_meta_key = '';
    protected $redirect_url = '';
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GFPaynamics();
		}

		return self::$_instance;

	}
	/*
	public function styles() {

		$styles = array(
			array(
				'handle'  => $this->_slug . '_paynamics',
				'src'     => $this->get_base_url() . '/css/paynamics.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'paynamics' ) ) ),
			),
		);

		return array_merge( parent::styles(), $styles );

	}*/
	
    public function enqueue_custom_script( $form) {
        
            wp_enqueue_script( 'custom_script', $this->get_base_url() .'/js/paynamics.js' );

    }
    public function pre_init() {
        parent::pre_init();
        // add tasks or filters here that you want to perform during the class constructor - before WordPress has been completely initialized
    }
 
    public function init() {
        // add_filter( 'gform_after_submission', array( $this, 'paynamics_after_submission' ), 10, 3 );
        add_filter( 'gform_confirmation_1', array( $this, 'paynamics_confirmation' ), 10, 3 );
        add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_custom_script'), 10, 2 );
        parent::init();
        
        
        // add tasks or filters here that you want to perform both in the backend and frontend and for ajax requests
    }
 
    public function init_admin() {
        parent::init_admin();
        // add tasks or filters here that you want to perform only in admin
        
    }
 
    public function init_frontend() {
        parent::init_frontend();
        // add tasks or filters here that you want to perform only in the front end
        add_filter( 'gform_disable_post_creation', array( $this, 'delay_post' ), 10, 3 );
		add_filter( 'gform_disable_notification', array( $this, 'delay_notification' ), 10, 4 );
    }
 
    public function init_ajax() {
        parent::init_ajax();
        // add tasks or filters here that you want to perform only during ajax requests
    }

	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	public function plugin_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'Paynamics API', 'gravityformspaynamics' ),
				'description' => 'This is where you put your merchant ID and Merchant Key and URL from Paynamics.',
				'fields' => $this->api_settings_fields(),
			),
		);

	}
	
	public function feed_list_no_item_message() {
		$settings = $this->get_plugin_settings();
		if ( ! rgar( $settings, 'merchant_id' ) ) {
			return sprintf( esc_html__( 'To get started, please configure your %sPaynamics Settings%s!', 'gravityformspaynamics' ), '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) . '">', '</a>' );
		} else {
			return parent::feed_list_no_item_message();
		}
	}

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
				'name'     => 'paynamics_endpoint_url',
				'label'    => esc_html__( 'Paynamics Endpoint  URL', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
				
			),
			array(
				'name'     => 'logo_url',
				'label'    => esc_html__( 'Paynamics logo', 'gravityformspaynamics' ),
				'type'     => 'text',
				'required'   => true,
				'class'    => 'medium',
			),
			array(
                'type'     => 'save',
                'label'    => esc_html__( 'Paynamics Save', 'gravityformspaynamics' ),
                'messages' => array(
                    'error'   => esc_html__( 'Paynamics Settings  could not be updated. Check Error Below.', 'gravityformspaynamics' ),
                    'success' => esc_html__( 'Paynamics Settings have been updated.', 'gravityformspaynamics' ),
                ),
            ),
			
			
		);

	}
	
	
    public function delay_post( $is_disabled, $form, $entry ) {

		$feed            = $this->get_payment_feed( $entry );
		$submission_data = $this->get_submission_data( $feed, $form, $entry );

		if ( ! $feed || empty( $submission_data['payment_amount'] ) ) {
			return $is_disabled;
		}

		return ! rgempty( 'delayPost', $feed['meta'] );
	}

	public function delay_notification( $is_disabled, $notification, $form, $entry ) {
		if ( rgar( $notification, 'event' ) != 'form_submission' ) {
			return $is_disabled;
		}

		$feed            = $this->get_payment_feed( $entry );
		$submission_data = $this->get_submission_data( $feed, $form, $entry );

		if ( ! $feed || empty( $submission_data['payment_amount'] ) ) {
			return $is_disabled;
		}

		$selected_notifications = is_array( rgar( $feed['meta'], 'selectedNotifications' ) ) ? rgar( $feed['meta'], 'selectedNotifications' ) : array();

		return isset( $feed['meta']['delayNotification'] ) && in_array( $notification['id'], $selected_notifications ) ? true : $is_disabled;
	}

    public function feed_settings_fields() {
    		$default_settings = parent::feed_settings_fields();
    		
    		//--add Paynamics Input field
		$fields = array(
			array(
				'name'     => 'notifUrl',
				'label'    => esc_html__( 'Notification Url ', 'gravityformspaynamics' ),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				
			),
			array(
				'name'     => 'resUrl',
				'label'    => esc_html__( 'Response Url ', 'gravityformspaynamics' ),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				
			),
			array(
				'name'     => 'canceUrl',
				'label'    => esc_html__( 'Cancel Url ', 'gravityformspaynamics' ),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				
			),
			array(
				'name'     => 'sec3d',
				'label'    => esc_html__( 'Sec3d ', 'gravityformspaynamics' ),
				'type'     => 'text',
				'class'    => 'medium',
				'required' => false,
				
			),
			
		);

		$default_settings = parent::add_field_after( 'feedName', $fields, $default_settings );
		
		//--add donation to transaction type drop down
		$transaction_type = parent::get_field( 'transactionType', $default_settings );
		$choices          = $transaction_type['choices'];
		$add_donation     = true;
		foreach ( $choices as $choice ) {
			//add donation option if it does not already exist
			if ( $choice['value'] == 'donation' ) {
				$add_donation = false;
			}
		}
		if ( $add_donation ) {
			//add donation transaction type
			$choices[] = array( 'label' => __( 'Donations', 'gravityformspaynamics' ), 'value' => 'donation' );
		}
		$transaction_type['choices'] = $choices;
		$default_settings            = $this->replace_field( 'transactionType', $transaction_type, $default_settings );
		
		//--get billing info section and add customer first/last name
		$billing_info   = parent::get_field( 'billingInformation', $default_settings );
		$billing_fields = $billing_info['field_map'];
		$add_first_name = true;
		$add_last_name  = true;
		$add_mobile_phone  = true;
		foreach ( $billing_fields as $mapping ) {
			//add first/last name if it does not already exist in billing fields
			if ( $mapping['name'] == 'firstName' ) {
				$add_first_name = false;
			} else if ( $mapping['name'] == 'lastName' ) {
				$add_last_name = false;
			}
			else if ( $mapping['name'] == 'mobilePhone' ) {
				$add_mobile_phone = false;
			}
		}

		if ( $add_last_name ) {
			//add last name
			array_unshift( $billing_info['field_map'], array( 'name' => 'lastName', 'label' => esc_html__( 'Last Name', 'gravityformspaynamics' ), 'required' => false ) );
		}
		if ( $add_first_name ) {
			array_unshift( $billing_info['field_map'], array( 'name' => 'firstName', 'label' => esc_html__( 'First Name', 'gravityformspaynamics' ), 'required' => false ) );
		}
		if ( $add_mobile_phone ) {
			array_unshift( $billing_info['field_map'], array( 'name' => 'mobilePhone', 'label' => esc_html__( 'Mobile Phone', 'gravityformspaynamics' ), 'required' => false ) );
		}
		$default_settings = parent::replace_field( 'billingInformation', $billing_info, $default_settings );
		
		//hide default display of setup fee, not used by PayPal Standard
		$default_settings = parent::remove_field( 'setupFee', $default_settings );

		//--add trial period
		$trial_period     = array(
			'name'    => 'trialPeriod',
			'label'   => esc_html__( 'Trial Period', 'gravityformspaynamics' ),
			'type'    => 'trial_period',
			'hidden'  => ! $this->get_setting( 'trial_enabled' ),
			'tooltip' => '<h6>' . esc_html__( 'Trial Period', 'gravityformspaynamics' ) . '</h6>' . esc_html__( 'Select the trial period length.', 'gravityformspaypal' )
		);
		$default_settings = parent::add_field_after( 'trial', $trial_period, $default_settings );
		
		return apply_filters( 'gform_paynamics_feed_settings_fields', $default_settings, $form );
    }
    
    public function field_map_title() {
		return esc_html__( 'Paynamics Field', 'gravityformspaynamics' );
	}
	
	public function get_product_query_string( $submission_data, $entry_id ) {

		if ( empty( $submission_data ) ) {
			return false;
		}

		$query_string   = '';
		$payment_amount = rgar( $submission_data, 'payment_amount' );
		$setup_fee      = rgar( $submission_data, 'setup_fee' );
		$trial_amount   = rgar( $submission_data, 'trial' );
		$line_items     = rgar( $submission_data, 'line_items' );
		$discounts      = rgar( $submission_data, 'discounts' );

		$product_index = 1;
		$shipping      = '';
		$discount_amt  = 0;
		$cmd           = '_cart';
		$extra_qs      = '&upload=1';

		//work on products
		if ( is_array( $line_items ) ) {
			foreach ( $line_items as $item ) {
				$product_name = urlencode( $item['name'] );
				$quantity     = $item['quantity'];
				$unit_price   = $item['unit_price'];
				$options      = rgar( $item, 'options' );
				$product_id   = $item['id'];
				$is_shipping  = rgar( $item, 'is_shipping' );

				if ( $is_shipping ) {
					//populate shipping info
					$shipping .= ! empty( $unit_price ) ? "&shipping_1={$unit_price}" : '';
				} else {
					//add product info to querystring
					$query_string .= "&item_name_{$product_index}={$product_name}&amount_{$product_index}={$unit_price}&quantity_{$product_index}={$quantity}";
				}
				//add options
				if ( ! empty( $options ) ) {
					if ( is_array( $options ) ) {
						$option_index = 1;
						foreach ( $options as $option ) {
							// Trim option label to prevent PayPal displaying an error instead of the cart.
							$option_label = urlencode( substr( $option['field_label'], 0, 64 ) );
							$option_name  = urlencode( $option['option_name'] );
							$query_string .= "&on{$option_index}_{$product_index}={$option_label}&os{$option_index}_{$product_index}={$option_name}";
							$option_index ++;
						}
					}
				}
				$product_index ++;
			}
		}

		//look for discounts
		if ( is_array( $discounts ) ) {
			foreach ( $discounts as $discount ) {
				$discount_full = abs( $discount['unit_price'] ) * $discount['quantity'];
				$discount_amt += $discount_full;
			}
			if ( $discount_amt > 0 ) {
				$query_string .= "&discount_amount_cart={$discount_amt}";
			}
		}

		$query_string .= "{$shipping}&cmd={$cmd}{$extra_qs}";
		
		//save payment amount to lead meta
		gform_update_meta( $entry_id, 'payment_amount', $payment_amount );

		return $payment_amount > 0 ? $query_string : false;

	}
	
	public function save_feed_settings( $feed_id, $form_id, $settings ) {

		//--------------------------------------------------------
		//For backwards compatibility
		$feed = $this->get_feed( $feed_id );

		//Saving new fields into old field names to maintain backwards compatibility for delayed payments
		$settings['type'] = $settings['transactionType'];

		if ( isset( $settings['recurringAmount'] ) ) {
			$settings['recurring_amount_field'] = $settings['recurringAmount'];
		}

		$feed['meta'] = $settings;
		$feed         = apply_filters( 'gform_paypal_save_config', $feed );
		
		//call hook to validate custom settings/meta added using gform_paypal_action_fields or gform_paypal_add_option_group action hooks
		$is_validation_error = apply_filters( 'gform_paypal_config_validation', false, $feed );
		if ( $is_validation_error ) {
			//fail save
			return false;
		}

		$settings = $feed['meta'];
		
		//--------------------------------------------------------

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}
	
	public function get_payment_feed( $entry, $form = false ) {

		$feed = parent::get_payment_feed( $entry, $form );

		if ( empty( $feed ) && ! empty( $entry['id'] ) ) {
			//looking for feed created by legacy versions
			$feed = $this->get_paynamics_feed_by_entry( $entry['id'] );
		}

		$feed = apply_filters( 'gform_paynamics_get_payment_feed', $feed, $entry, $form ? $form : GFAPI::get_form( $entry['form_id'] ) );

		return $feed;
	}
	
	private function get_paynamics_feed_by_entry( $entry_id ) {

		$feed_id = gform_get_meta( $entry_id, 'paynamics_feed_id' );
		$feed    = $this->get_feed( $feed_id );

		return ! empty( $feed ) ? $feed : false;
	}
	
	public function get_donation_query_string( $submission_data, $entry_id ) {
		if ( empty( $submission_data ) ) {
			return false;
		}

		$query_string   = '';
		$payment_amount = rgar( $submission_data, 'payment_amount' );
		$line_items     = rgar( $submission_data, 'line_items' );
		$purpose        = '';
		$cmd            = '_donations';

		//work on products
		if ( is_array( $line_items ) ) {
			foreach ( $line_items as $item ) {
				$product_name    = $item['name'];
				$quantity        = $item['quantity'];
				$quantity_label  = $quantity > 1 ? $quantity . ' ' : '';
				$options         = rgar( $item, 'options' );
				$is_shipping     = rgar( $item, 'is_shipping' );
				$product_options = '';

				if ( ! $is_shipping ) {
					//add options
					if ( ! empty( $options ) ) {
						if ( is_array( $options ) ) {
							$product_options = ' (';
							foreach ( $options as $option ) {
								$product_options .= $option['option_name'] . ', ';
							}
							$product_options = substr( $product_options, 0, strlen( $product_options ) - 2 ) . ')';
						}
					}
					$purpose .= $quantity_label . $product_name . $product_options . ', ';
				}
			}
		}

		if ( ! empty( $purpose ) ) {
			$purpose = substr( $purpose, 0, strlen( $purpose ) - 2 );
		}

		$purpose = urlencode( $purpose );

		//truncating to maximum length allowed by PayPal
		if ( strlen( $purpose ) > 127 ) {
			$purpose = substr( $purpose, 0, 124 ) . '...';
		}

		$query_string = "&amount={$payment_amount}&item_name={$purpose}&cmd={$cmd}";
		
		//save payment amount to lead meta
		gform_update_meta( $entry_id, 'payment_amount', $payment_amount );

		return $payment_amount > 0 ? $query_string : false;

	}
	
	
	public function paynamics_data( $entry, $form ) {
	    
	    $feed            = $this->get_payment_feed( $entry );
	    $query_amount = $this->get_donation_query_string( $submission_data, $entry['id'] );
	    
        $_mid           = $this->get_plugin_setting( 'merchant_id');
        $_requestid     = substr(uniqid(), 0, 13);
        $_ipaddress     = $_SERVER['SERVER_ADDR'];
        $_noturl        = $feed['meta']['notifUrl'];
        $_resurl        = $feed['meta']['resUrl'];
        $_cancelurl     = $feed['meta']['canceUrl'];
        $_fname         = rgar( $entry, '1.3' );
        $_mname         = "";//rgar( $entry, '1.4' );
        $_lname         = rgar( $entry, '1.6' );
        $_addr1         = rgar( $entry, '14.1' );
        $_addr2         = rgar( $entry, '14.2' );
        $_city          = rgar( $entry, '14.3' );
        $_state         = rgar( $entry, '14.4' );
        $_country       = rgar( $entry, '14.6' );
        $_zip           = rgar( $entry, '14.5' );
        $_sec3d         = $feed['meta']['sec3d'];
        $_email         = rgar( $entry, '2' );
        $_phone         = "";
        $_mobile        = rgar( $entry, '3' );
        $_clientip      = $_SERVER['REMOTE_ADDR'];
        $_amount        = number_format((float)rgar( $entry, '8' ), 2, '.', ''); 
        $_currency      = rgar( $entry, '18' ); 
        $cert           = $this->get_plugin_setting( 'merchant_key');
        $_logo          = $this->get_plugin_setting( 'logo_url');
        
        

        
        $forSign = $_mid . $_requestid . $_ipaddress . $_noturl . $_resurl .  $_fname . $_lname . $_mname . $_addr1 . $_addr2 . $_city . $_state . $_country . $_zip . $_email . $_phone . $_clientip . $_amount . $_currency . $_sec3d;
        $cert = $this->get_plugin_setting( 'merchant_key');
        
        
        $_sign = hash("sha512", $forSign.$cert);
        $xmlstr = "";
        
        $strxml = "";
        
        $strxml = $strxml . "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
        $strxml = $strxml . "<Request>";
        $strxml = $strxml . "<orders>";
        $strxml = $strxml . "<items>";
        $strxml = $strxml . "<Items>";
        $strxml = $strxml . "<itemname>Donated By ".$_fname." </itemname><quantity>1</quantity><amount>" . $_amount . "</amount>";
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
        $strxml = $strxml . "<mlogo_url>". $_logo ."</mlogo_url>";
        $strxml = $strxml . "<pmethod></pmethod>";//CC, GC, PP, DP
        $strxml = $strxml . "<signature>" . $_sign . "</signature>";
        $strxml = $strxml . "</Request>";
        $b64string =  base64_encode($strxml);
        
        return $b64string;
        
        
        $this->log_debug( __METHOD__ . '(): body => ' . print_r( $response, true ) );
					
	}
	
	public function paynamics_confirmation( $confirmation, $form, $entry) { 
	    $script = $this->enqueue_custom_script($form);
	    
	    $paynamics_url  = $this->get_plugin_setting( 'paynamics_endpoint_url');
	    $_fname         = rgar( $entry, '1.3' );
	    $_logo          = $this->get_plugin_setting( 'logo_url');
        
        $data = $this->paynamics_data($entry, $form, $feed);
        $this->log_debug( __METHOD__ . '(): fields => ' . print_r( $data, true ) );
        
        try{
            $body = $_POST["paymentresponse"];
            $body = str_replace(" ", "+", $body);
            $Decodebody = base64_decode($body);
            echo "DECODEd : </br></br> ";
            $ServiceResponseWPF = new SimpleXMLElement($Decodebody);
            $application = $ServiceResponseWPF->application;
            $responseStatus = $ServiceResponseWPF->responseStatus;
            
            echo "Response: " . $ServiceResponseWPF->application->signature;
            $cert = ""; //merchantkey
            
            $forSign = $application->merchantid . $application->request_id . $application->response_id . $responseStatus->response_code . $responseStatus->response_message . $responseStatus->
            response_advise . $application->timestamp . $application->rebill_id . $cert;
            
            $_sign = hash("sha512", $forSign);
            
            echo "</br>computed:" . $_sign;
            
            if($_sign == $ServiceResponseWPF->application->signature)
            {
            	echo "</br>VALID SIGNATURE";
            }
            else{echo "</br>INVALID SIGNATURE";}
            
            
            $ourFileName = "testFile2.txt";
            $ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
            fclose($ourFileHandle);
            $myFile = "testFile.txt";
            $fh = fopen($myFile, 'w') or die("can't open file");
            
            $stringData = $Decodebody;
            fwrite($fh, $stringData.$body);
            fclose($fh);
            
            }
        catch(Exception $ex)
            {echo $ex->getMessage();}
            
        
        $confirmation =  '<script>
                alert("Redirecting to Paynamics Gateway Do Not Refresh!");
              document.getElementById("paynamics_send").submit();
        </script>
        '.'<div class="paynamics_form_wrapper" style="width: 400px;margin:0 auto;border: 1px solid #ededed;position: relative;">
                	<div class="paynamics_form_container" style="width: 100%;margin-bottom: 20px;">
                		<p class="paynamics_logo_holder" style="text-align: center;margin-top: 10px;"><img src="'.$_logo.'"></p>
                		<h2 class="paynamics_title" style="font-size: 36px;text-align: center;margin-bottom: 0;">Thank You</h2>
                		
                		<h4 class="paynamics_name_donation" style="font-size: 24px;text-align: center;margin-top: 0;">( '.$_fname.' )</h4>
                		<p class="paynamics_name_donation" style="font-size: 24px;text-align: center;margin-top: 0; margin-bottom:30px;">Click The Button to send your donation to Paynamics!</p>
                		<form name="paynamics_send" id="paynamics_send" method="post" name="paynamics_payment" action="'.$paynamics_url.'">
                	    	<input type="hidden" name="paymentrequest" id="paymentrequest" value="'.$data.'">
                	    	<div class="paynamics_button_wrapper" style="text-align: center;margin: 0 auto;">
                	    		<input type="submit" value="Send To Paynamics" class="paynamics_button" id="paynamics_submit_button" style="background-color: #E46824;border: none;color: white;padding: 15px 32px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;border-radius: 99px;">
                	    	</div>
                	    	
                		</form>
                	</div>
                </div>';
			
		return $confirmation;
		
		
        
	}
	
	

}

