/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014
 */

(function ($, OC) {

	$(document).ready(function () {
		$('#push').click(function () {
			var url = OC.generateUrl('/apps/ocsms/push');
			var data = {
				smsCount: 1,
				smsDatas: [
					{"read": 1, "date": 1410524385, "seen": 0, "address": "+33612121212", "body": "testSMS", "id": 10},
					{"read": 0, "date": 1400524385, "seen": 1, "address": "+33614121212", "body": "test SMS 2", "id": 14},
				]
			};

			$.post(url, data).success(function (response) {
				$('#push-result').text(response);
			});

		});
	});

})(jQuery, OC);
