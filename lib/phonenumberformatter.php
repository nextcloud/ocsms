<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @contributor: stagprom <https://github.com/stagprom/>
 * @copyright Loic Blot 2014-2017
 */

namespace OCA\OcSms\Lib;

use \OCA\OcSms\Lib\CountryCodes;

// Load the PhoneNumberUtil class and dependencies.
include( dirname(__FILE__) . '/vendor/autoload.php');

class PhoneNumberFormatter {
	public static function format ($country, $pn) {
		// Trim the phone number.
		$pn = trim($pn);

		// If no country, country not found into mapper, or the phone number is shorter than six characters
		// (aka most likely a "short" SMS service, just return the original number.
		if ($country === false || !array_key_exists($country, CountryCodes::$codes) || strlen($pn) < 6) {
			return $pn;
		}

		// Make sure the passed in phone number looks like it could be a phone number, otherwise just return it.
		if (preg_match('#^[\d\+\(\[\{].*#',$pn)) {							// start with digit, +, (, [ or {
			// Get an instance of the PhoneNumber Util class.
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

			// Try and parse the phone number
			try {
			    $NumberProto = $phoneUtil->parse($pn, CountryCodes::$countries[$country]);

			    // Reformat the returned result as an international phone number.
				$ypn = $phoneUtil->format( $NumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL );

				// Strip out everything but the digits.
				$ypn = preg_replace( '#[^\d]#', '', $ypn );
			} catch (\libphonenumber\NumberParseException $e) {
				// If something failed, just return the original string.
			    $ypn = $pn;
			}
		} else {
			$ypn = $pn;										// some SMS_adresses are strings
		}

		return $ypn;
    }
};
