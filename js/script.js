/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014
 */
 

// Some global vars to improve performances
var selectedConversation = null;

// Source: http://www.sitepoint.com/url-parameters-jquery/
$.urlParam = function(name){
	var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	if (results == null) {
		return null;
	}
	else {
		return results[1] || 0;
	}
}

function fetchConversation(phoneNumber) {
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{'phoneNumber': phoneNumber},
		function(jsondata, status) {
			// Improve jQuery performance
			var conversationBuf = "";
			// Improve JS performance
			var msgClass = '';
			var unixDate = '';
			var formatedDate = '';
			var formatedHour = '00';
			var formatedMin = '00';
			var months = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'Jun.', 'Jul.', 'Aug.', 'Sep.',
				'Oct.', 'Nov.', 'Dec.'];

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

				// Multiplicate ID to permit date to use it properly
				msgDate = new Date(id*1);

				formatedHour = msgDate.getHours();
				if (formatedHour < 10) {
					formatedHour = '0' + formatedHour;
				}

				formatedMin = msgDate.getMinutes();
				if (formatedMin < 10) {
					formatedMin = '0' + formatedMin;
				}
				formatedDate = msgDate.getDate() + " " + months[msgDate.getMonth()] + " " +
					formatedHour + ":" + formatedMin;

				conversationBuf += '<div><div class="' + msgClass + '"><div>' + 
					vals["msg"] + '</div><div class="msg-date">' + 
					formatedDate + '</div></div><div class="msg-spacer"></div></div>';
			});

			$('#app-content').html(conversationBuf);
			$('#app-content').scrollTop(1E10);
		}
	);
}

function changeSelectedConversation(item) {
	if (selectedConversation != null) {
		selectedConversation.parent().removeClass('selected');
	}
	selectedConversation = item;
	selectedConversation.parent().addClass('selected');
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
				changeSelectedConversation($(this));
				event.preventDefault();
			});

			var urlPhoneNumber = decodeURIComponent($.urlParam('phonenumber'));
			if (urlPhoneNumber != null) {
				fetchConversation(urlPhoneNumber);
				var pObject = $("a[mailbox-navigation='" + urlPhoneNumber + "']");
				if (pObject != null) {
					changeSelectedConversation(pObject);
				}
			}
	     });
	});

})(jQuery, OC);
