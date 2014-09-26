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
var curPhoneNumber = null;
var lastMsgDate = 0;

// Source: http://www.sitepoint.com/url-parameters-jquery/
$.urlParam = function(name){
	var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	if (results == null) {
		return null;
	}
	else {
		return results[1] || 0;
	}
};

var refreshConversation = function() {
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{
			'phoneNumber': curPhoneNumber,
			"lastDate": lastMsgDate
		},
		function(jsondata, status) {
			conversationBuf = formatConversation(jsondata);
			$('.msg-endtag').before(conversationBuf);
			$('#app-content').scrollTop(1E10);
		}
	);
};

function fetchConversation(phoneNumber) {
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{
			'phoneNumber': phoneNumber
		},
		function(jsondata, status) {
			conversationBuf = formatConversation(jsondata);
			conversationBuf += '<div class="msg-endtag"></div>';

			$('#app-content').html(conversationBuf);
			$('#app-content').scrollTop(1E10);

			curPhoneNumber = phoneNumber;
		}
	);
}

function formatConversation(jsondata) {
	// Improve jQuery performance
	var buf = "";
	// Improve JS performance
	var msgClass = '';
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

		// Store the greater msg date for refresher
		if (id > lastMsgDate) {
			lastMsgDate = id;
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

		buf += '<div><div class="' + msgClass + '"><div>' +
			vals["msg"] + '</div><div class="msg-date">' +
			formatedDate + '</div></div><div class="msg-spacer"></div></div>';

	});

	return buf;
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
				// Reset it for refreshConversation
				lastMsgDate = 0;
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
	     setInterval(refreshConversation, 10000);
	});

})(jQuery, OC);
