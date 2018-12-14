<?php
/**
 * Plugin Name: Gravity Forms Paynamics Add On
 * Plugin URI: https://www.gravityforms.com
 * Description: Integrates Gravity Forms with Paynamics, enabling end users to purchase goods and services through Gravity Forms.
 * Version: 1
 * Author: rakethost
 * Author URI: https://www.rakethost.com
 * License: GPL-2.0+
 * Text Domain: gravityformspaynamics
 * Domain Path: /languages
 *
 * ------------------------------------------------------------------------
 * Copyright 2009 - 2018 rocketgenius
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

define( 'GF_PAYNAMICS_VERSION', '1' );

// If Gravity Forms is loaded, bootstrap the Stripe Add-On.
add_action( 'gform_loaded', array( 'GF_Paynamics_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Stripe_Bootstrap
 *
 * Handles the loading of the Stripe Add-On and registers with the Add-On framework.
 *
 * @since 1.0.0
 */
class GF_Paynamics_Bootstrap {

	/**
	 * If the Payment Add-On Framework exists, Paynamics Add-On is loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses GFAddOn::register()
	 *
	 * @return void
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-paynamics.php' );

		GFAddOn::register( 'GFPaynamics' );

	}

}

/**
 * Obtains and returns an instance of the GFPaynamics class
 *
 * @since  1.0.0
 * @access public
 *
 * @uses GFPaynamics::get_instance()
 *
 * @return object GFPaynamics
 */
function gf_paynamics() {
	return GFPaynamics::get_instance();
}
