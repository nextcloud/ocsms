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
	// if no conversation selected, then don't fetch page
	if (curPhoneNumber == null) {
		if ($('#app-content-header').is(':visible')) {
			$('#app-content-header').hide();
		}
		return;
	}
	
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{
			'phoneNumber': curPhoneNumber,
			"lastDate": lastMsgDate
		},
		function(jsondata, status) {
			conversationBuf = formatConversation(jsondata);
			if (conversationBuf != '') {
				$('.msg-endtag').before(conversationBuf);
				$('#app-content').scrollTop(1E10);
			}
			
			if ($('#app-content-header').is(':hidden')) {
				$('#app-content-header').show();
			}
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
			if (typeof jsondata['contactName'] == 'undefined') {
				$('#ocsms-phone-label').html(phoneNumber);
				$('#ocsms-phone-opt-number').html('');
			}
			else {
				$('#ocsms-phone-label').html(jsondata['contactName']);
				$('#ocsms-phone-opt-number').html(phoneNumber);
			}
			
			if ($('#app-content-header').is(':hidden')) {
				$('#app-content-header').show();
			}

			$('#app-content-wrapper').html(conversationBuf);
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

function fetchInitialPeerList(jsondata) {
	// Use a buffer for better jQuery performance
	var peerListBuf = "";
	
	var bufferedContacts = [];

	$.each(jsondata['phonelist'], function(id, val) {
		
		var fn, peerLabel;
		if (typeof jsondata['contacts'][val] == 'undefined') {
			fn = '';
			peerLabel = val;
		}
		else {
			fn = jsondata['contacts'][val];
			peerLabel = fn;
		}
		if ($.inArray(peerLabel, bufferedContacts) !== true) {
			peerListBuf += '<li><a href="#" mailbox-navigation="' + val + '">' + peerLabel + '</a></li>';
			bufferedContacts.push(peerLabel);
		}
	});
	
	// Only modify peerList if there is peers
	if (peerListBuf != '') {
		$('#app-mailbox-peers ul').html(peerListBuf);
	}
}

(function ($, OC) {
	$(document).ready(function () {
		// Now bind the events when we click on the phone number
		$.getJSON(OC.generateUrl('/apps/ocsms/get/peerlist'), function(jsondata, status) {
			fetchInitialPeerList(jsondata);

			// Now bind the events when we click on the phone number
			$('#app-mailbox-peers').find('a[mailbox-navigation]').on('click', function (event) {
				var phoneNumber = $(this).attr('mailbox-navigation');
				OC.Util.History.pushState('phonenumber=' + phoneNumber);
				// Reset it for refreshConversation
				lastMsgDate = 0;
				
				// phoneNumber must exist
				if (phoneNumber != null) {
					fetchConversation(phoneNumber);
					changeSelectedConversation($(this));
				}
				event.preventDefault();
			});

			var pnParam = $.urlParam('phonenumber');
			if (pnParam != null) {
				var urlPhoneNumber = decodeURIComponent(pnParam);
				if (urlPhoneNumber != null) {
					fetchConversation(urlPhoneNumber);

					var pObject = $("a[mailbox-navigation='" + urlPhoneNumber + "']");
					if (pObject != null) {
						changeSelectedConversation(pObject);
					}
				}
			}
			// Don't show message headers if no conversation selected
			else {
				if ($('#app-content-header').is(':visible')) {
					$('#app-content-header').hide();
				}
			}

	     });
	     setInterval(refreshConversation, 10000);
	});

})(jQuery, OC);
