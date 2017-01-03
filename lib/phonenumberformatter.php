<?php
/**
 * NextCloud - Phone Sync
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

class PhoneNumberFormatter {
	public static $intlPhoneNumber_rxp = array(					// match international numbers with 1,2,3 digits
		'#^(00|\+)(1\d\d\d)#',				// NANP
		'#^(00|\+)(2[1|2|3|4|5|6|8|9]\d)#',		// +2(1|2|3|4|5|6|8|9)x
		'#^(00|\+)(2[0|7])#',				// +2x
		'#^(00|\+)(3[5|7|8]\d)#',			// +3(5|7|8)x
		'#^(00|\+)(3[0|1|2|3|4|6|9])#',			// +3x
		'#^(00|\+)(4[2]\d)#',				// +4(2)x
		'#^(00|\+)(4[0|1|3|4|5|6|7|8|9])#',		// +4x
		'#^(00|\+)(5[0|9]\d)#',				// +5(0|9)x
		'#^(00|\+)(5[1|2|3|4|5|6|7|8])#',		// +5x
		'#^(00|\+)(6[7|8|9]\d)#',			// +6(7|8|9)x
		'#^(00|\+)(6[0|1|2|3|4|5|6])#',			// +6x
		'#^(00|\+)(7)#',				// +7
		'#^(00|\+)(8[5|7|8|9]\d)#',			// +8(5|7|8|9)x
		'#^(00|\+)(8[1|2|3|4|6])#',			// +8x
		'#^(00|\+)(9[6|7|9]\d)#',			// +9(6|7|9)x
		'#^(00|\+)(9[0|1|2|3|4|5|8])#'			// +9x
	);

	public static $localPrePhoneNumber_rxp = array(
		'#(^0)([^0])#'						// in germany : 0-xx[x[x]]-123456
	);								//

	public static function format ($country, $pn) {
		// If no country or country not found into mapper, return original number
		if ($country === false || !array_key_exists($country, CountryCodes::$codes)) {
			return trim($pn);	
		}

		$ignrxp = array(					// match non digits and +
			'#[^\d\+\(\)\[\]\{\}]#',			// everything but digit, +, (), [] or {}
			'#(.+)([\(\[\{]\d*[\)\]\}])#',			// braces inside the number: +49 (0) 123 456789
			'#[^\d\+]#'					// everything but digits and +
		);

		$ignrpl = array(					// replacements
			'',
			'$1',
			''
		);
		
		$lpnrpl = CountryCodes::$codes[$country].'$2';		// replace with +{countryCode} -xx[x[x]]-123456

		$tpn = trim($pn);

		if (preg_match('#^[\d\+\(\[\{].*#',$tpn)) {							// start with digit, +, (, [ or {
			$fpn = preg_replace($ignrxp, $ignrpl, $tpn);						// replace everything but digits/+ with ''
			$xpn = preg_replace(PhoneNumberFormatter::$localPrePhoneNumber_rxp, $lpnrpl, $fpn);	// replace local prenumbers
			$ypn = preg_replace(PhoneNumberFormatter::$intlPhoneNumber_rxp, '+$2', $xpn);		// format to international coding +x[x[x]].....
		} else {
			$ypn = $tpn;										// some SMS_adresses are strings
		}

		return $ypn;
    }
};
