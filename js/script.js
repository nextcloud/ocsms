/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014
 */

function fetchConversation(phoneNumber) {
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{'phoneNumber': phoneNumber},
		function(jsondata, status) {
			// Improve jQuery performance
			var conversationBuf = "";
			// Improve JS performance
			var msgClass = '';

			$.each(jsondata["conversation"], function(id, vals) {
				if (vals["type"] == 1) {
					msgClass = "msg-recv";
				}
				else if (vals["type"] == 2) {
					msgClass = "msg-sent";
				}
				else {
					msgClass = '';
				}

				conversationBuf += '<div><div class="' + msgClass + '">' + vals["msg"] + '</div><div class="msg-spacer"></div></div>';
			});

			$('#app-content').html(conversationBuf);
		}
	);
}
(function ($, OC) {
	$(document).ready(function () {
		// Now bind the events when we click on the phone number
		$('#app-navigation').find('a').on('click', function (event) {
			OC.Util.History.pushState('feed=' + $(this).attr('nav-feed'));
			event.preventDefault();
        });

		$.getJSON(OC.generateUrl('/apps/ocsms/get/peerlist'), function(jsondata, status) {
			// Use a buffer for better jQuery performance
			var peerListBuf = "";

			$.each(jsondata['phonelist'], function(id, val) {
				peerListBuf += '<li><a href="#" mailbox-navigation="' + val + '">' + val + '</a></li>';
			});

			$('#app-mailbox-peers ul').html(peerListBuf);

			// Now bind the events when we click on the phone number
			$('#app-mailbox-peers').find('a[mailbox-navigation]').on('click', function (event) {
				var phoneNumber = $(this).attr('mailbox-navigation');
				OC.Util.History.pushState('phonenumber=' + phoneNumber);
				fetchConversation(phoneNumber);
				event.preventDefault();
			});

	     });
	});

})(jQuery, OC);
