/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2015
 */


// Some global vars to improve performances
var selectedConversation = null;
var curPhoneNumber = null;
var curContactName = '';
var lastMsgDate = 0;
var unreadCountCurrentConv = 0;
var unreadCountAllConv = 0;
var unreadCountNotifStep = 12;
var lastUnreadCountAllConv = 0;
var originalTitle = document.title;

var app = angular.module('OcSms', []);

app.controller('OcSmsController', ['$scope',
	function ($scope) {
		$scope.buttons = [
			{text: "Send"}
		];
		$scope.contacts = [];
		$scope.sendCountry = function () {
			$.post(OC.generateUrl('/apps/ocsms/set/country'),{'country': $('select[name=intl_phone]').val()});
		};
		$scope.loadConversation = function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				fetchConversation(contact.nav);
				changeSelectedConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		};

		$scope.addContact = function (ct) {
			$scope.$apply(function () {
				$scope.contacts.push(ct);
			});
		};
	}
]);

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
					unreadCountCurrentConv += fmt[0];
					document.title = originalTitle + " (" + unreadCountCurrentConv + ")";
					desktopNotify(unreadCountCurrentConv + " unread message(s) in conversation with " + curContactName);
				}
			}

			setMessageCountInfo(jsondata);

			if ($('#app-content-header').is(':hidden')) {
				$('#app-content-header').show();
			}
		}
	);
};

var checkNewMessages = function() {
	unreadCountAllConv = 0;
	$.getJSON(OC.generateUrl('/apps/ocsms/get/new_messages'),
		{ 'lastDate': lastMsgDate },
		function(jsondata, status) {
			var peerListBuf = '';
			var bufferedContacts = [];

			$.each(jsondata['phonelist'], function(id, val) {
				var fn, peerLabel, idxVal;
				idxVal = id.replace(/\//g,' ');
				idxVal2 = idxVal.replace('/ /g','');
				if (typeof jsondata['contacts'][id] == 'undefined') {
					fn = '';
					peerLabel = idxVal;
				}
				else {
					fn = jsondata['contacts'][id];
					peerLabel = fn;
				}

				if ($.inArray(peerLabel, bufferedContacts) == -1) {
					$("li[peer-label='" + peerLabel + "']").remove();
					peerListBuf = '<li peer-label="' + peerLabel + '"><div class="ocsms-plavatar"';
					if (typeof jsondata['photos'][peerLabel] != 'undefined') {
						peerListBuf += 'style="background-image: url(' + jsondata['photos'][peerLabel] + ');"';
					}
					peerListBuf += '></div><a href="#" ng-click="loadConversation(' + idxVal2 + ');" mailbox-navigation="' + idxVal2 + '" style="font-weight: bold;" mailbox-label="' + peerLabel + '">' + peerLabel + ' (' + val + ')</a></li>';
					$('#app-mailbox-peers ul').prepend(peerListBuf);
					bufferedContacts.push(peerLabel);

					// Re-set conversation because we reload the element
					if (idxVal == curPhoneNumber) {
						changeSelectedConversation($("a[mailbox-navigation='" + idxVal + "']"));
					}

					unreadCountAllConv += parseInt(val);	

					// Now bind the events when we click on the phone number
					$("a[mailbox-navigation='" + idxVal + "']").on('click', function (event) {
						var phoneNumber = $(this).attr('mailbox-navigation');
						OC.Util.History.pushState('phonenumber=' + phoneNumber);

						// phoneNumber must exist
						if (phoneNumber != null) {
							fetchConversation(phoneNumber);
							unreadCountAllConv += val;
							changeSelectedConversation($(this));
						}
						event.preventDefault();
					});
				}
			});

			/*
			* Decrement notification step counter, but stop it a zero
			* Stop at zero permit to notify instanly the user when 
			* there is new messages in all conversations
			*/
			
			if (unreadCountNotifStep > 0) {
				unreadCountNotifStep--;
			}
			
			if (unreadCountAllConv > 0) {
				/*
				* We notify user every two minutes for all messages
				* or if unreadCount changes
				*/
				if (unreadCountNotifStep == 0 || lastUnreadCountAllConv != unreadCountAllConv) {
					desktopNotify(unreadCountAllConv + " unread message(s) for all conversations");
					unreadCountNotifStep = 12;
					lastUnreadCountAllConv = unreadCountAllConv;
				}
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
				var len = jsondata["phoneNumbers"].length;
				var ctLen = 0;
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
		// Note: we divide by 100 because number compare too large integers
		if ((id/100) > (lastMsgDate/100)) {
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
	if (item === 'undefined' || item == null) {
		return;
	}

	if (selectedConversation != null) {
		selectedConversation.parent().removeClass('selected');
	}
	selectedConversation = item;
	selectedConversation.parent().addClass('selected');
	selectedConversation.css("font-weight", "normal");
	selectedConversation.html(selectedConversation.attr("mailbox-label"));
}

function fetchInitialPeerList(jsondata) {
	// Use a buffer for better jQuery performance
	var peerListBuf = "";

	var bufferedContacts = [];

	var aScope = angular.element('[ng-controller="OcSmsController"]').scope();

	$.each(jsondata['phonelist'], function(id, val) {
		var fn, peerLabel, idxVal;
		idxVal = id.replace(/\//g,' ');
		idxVal2 = idxVal.replace('/ /g','');
		if (typeof jsondata['contacts'][id] == 'undefined') {
			fn = '';
			peerLabel = idxVal;
		}
		else {
			fn = jsondata['contacts'][id];
			peerLabel = fn;
		}
		if ($.inArray(peerLabel, bufferedContacts) == -1) {
			aScope.addContact({'label': peerLabel, 'nav': idxVal2, 'avatar': jsondata['photos'][peerLabel]});
			bufferedContacts.push(peerLabel);
		}
	});

	lastMsgDate = jsondata["lastRead"];
}

function initDesktopNotifies() {
	if (!("Notification" in window)) {
		return;
	}
	
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

			var pnParam = $.urlParam('phonenumber');
			if (pnParam != null) {
				var urlPhoneNumber = decodeURIComponent(pnParam);
				if (urlPhoneNumber != null) {
					fetchConversation(urlPhoneNumber);
					changeSelectedConversation($("a[mailbox-navigation='" + urlPhoneNumber + "']"));
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
		setInterval(checkNewMessages, 10000);
	});

	// reset count and title
	window.onfocus = function () {
		unreadCountCurrentConv = 0;
		document.title = originalTitle;
	};
})(jQuery, OC);
