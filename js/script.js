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
var curContactName = '';
var lastMsgDate = 0;
var unreadCount = 0;
var originalTitle = document.title;

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
			var fmt = formatConversation(jsondata);
			conversationBuf = fmt[1];
			if (conversationBuf != '') {
				$('.msg-endtag').before(conversationBuf);
				$('#app-content').scrollTop(1E10);
				// This will blink the tab because there is new messages
				if (document.hasFocus() == false) {
					unreadCount += fmt[0];
					document.title = originalTitle + " (" + unreadCount + ")";
					desktopNotify(unreadCount + " unread message(s) in conversation with " + curContactName);
				}
			}

			setMessageCountInfo(jsondata);

			if ($('#app-content-header').is(':hidden')) {
				$('#app-content-header').show();
			}
		}
	);
};

function setMessageCountInfo(jsondata) {
	if (typeof jsondata['msgCount'] != 'undefined') {
		if (jsondata['msgCount'] == 1) {
			$('#ocsms-phone-msg-nb').html(jsondata['msgCount'] + ' message');
		}
		else {
			$('#ocsms-phone-msg-nb').html(jsondata['msgCount'] + ' messages');
		}
	}
	else {
		$('#ocsms-phone-msg-nb').html('');
	}
}

function fetchConversation(phoneNumber) {
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{
			'phoneNumber': phoneNumber
		},
		function(jsondata, status) {
			var phoneNumberLabel = phoneNumber;

			if (typeof jsondata['phoneNumbers'] != 'undefined') {
				len = jsondata["phoneNumbers"].length;
				ctLen = 0;
				phoneNumberLabel = '';

				$.each(jsondata["phoneNumbers"], function(id, val) {
					phoneNumberLabel += val;
					ctLen++;
					if (ctLen != len) {
						phoneNumberLabel += ",";
					}
					phoneNumberLabel += " ";
				});
			}

			conversationBuf = formatConversation(jsondata)[1];
			conversationBuf += '<div class="msg-endtag"></div>';
			if (typeof jsondata['contactName'] == 'undefined' || jsondata['contactName'] == '') {
				$('#ocsms-phone-label').html(phoneNumberLabel);
				curContactName = phoneNumberLabel;
				$('#ocsms-phone-opt-number').html('');
			}
			else {
				$('#ocsms-phone-label').html(jsondata['contactName']);
				curContactName = jsondata['contactName'];
				$('#ocsms-phone-opt-number').html(phoneNumberLabel);
			}

			setMessageCountInfo(jsondata);

			if ($('#app-content-header').is(':hidden')) {
				$('#app-content-header').show();
			}

			$('#app-content-wrapper').html(conversationBuf);
			$('#app-content').scrollTop(1E10);

			curPhoneNumber = phoneNumber;
		}
	);
}

// Return (int) msgCount, (str) htmlConversation
function formatConversation(jsondata) {
	// Improve jQuery performance
	var buf = "";
	// Improve JS performance
	var msgClass = '';

	var msgCount = 0;
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
		msgCount++;

	});

	return [msgCount,buf];
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
		var fn, peerLabel, idxVal;
		idxVal = id.replace(/\//g,' ');
		idxVal2 = idxVal.replace('/ /g','');
		if (typeof jsondata['contacts'][id] == 'undefined') {
			fn = '';
			peerLabel = id;
		}
		else {
			fn = jsondata['contacts'][id];
			peerLabel = fn;
		}
		if ($.inArray(peerLabel, bufferedContacts) == -1) {
			peerListBuf += '<li><a href="#" mailbox-navigation="' + idxVal + '">' + peerLabel + '</a></li>';
			bufferedContacts.push(peerLabel);
		}
	});

	// Only modify peerList if there is peers
	if (peerListBuf != '') {
		$('#app-mailbox-peers ul').html(peerListBuf);
	}
}

function initDesktopNotifies() {
	Notification.requestPermission(function (permission) {
		if(!('permission' in Notification)) {
			Notification.permission = permission;
		}
	});
}

function desktopNotify(msg) {
	if (!("Notification" in window)) {
		return;
	}
	else if (Notification.permission === "granted") {
		new Notification("ownCloud SMS - " + msg);
	}
	else if (Notification.permission !== 'denied') {
		Notification.requestPermission(function (permission) {
			if(!('permission' in Notification)) {
				Notification.permission = permission;
			}
			if (permission === "granted") {
				new Notification("ownCloud SMS - " + msg);
			}
		});
	}
}

(function ($, OC) {
	$(document).ready(function () {
		// Register real title
		originalTitle = document.title;

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
		initDesktopNotifies();
		setInterval(refreshConversation, 10000);
	});

	// reset count and title
	window.onfocus = function () {
		unreadCount = 0;
		document.title = originalTitle;
	};
})(jQuery, OC);
