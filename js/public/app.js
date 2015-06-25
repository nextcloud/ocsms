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
var g_selectedConversation = null;
var g_curPhoneNumber = null;
var g_curContactName = '';
var g_lastMsgDate = 0;
var g_unreadCountCurrentConv = 0;
var g_unreadCountAllConv = 0;
var g_unreadCountNotifStep = 12;
var g_lastUnreadCountAllConv = 0;
var g_originalTitle = document.title;

var g_ulContactList = $('.contact-list');
var app = angular.module('OcSms', []);

function inArray(val, arr) {
	return ($.inArray(val, arr) != -1);
}

function arrayUnique(arr) {
	var unq = arr.filter(function(item, i, arr) {
		return i == arr.indexOf(item);
	})
	return unq;
}

app.controller('OcSmsController', ['$scope', '$interval', '$timeout', '$compile',
	function ($scope, $interval, $timeout, $compile) {
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
				$scope.fetchConversation(contact.nav);
				changeSelectedConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		};
		$scope.fetchConversation = function (phoneNumber) {
			$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
				{
					'phoneNumber': phoneNumber
				},
				function(jsondata, status) {
					var phoneNumberLabel = phoneNumber;

					if (typeof jsondata['phoneNumbers'] != 'undefined') {
						phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
						phoneNumberLabel = phoneNumberList.toString();
					}

					var conversationBuf = formatConversation(jsondata)[1];
					conversationBuf += '<div class="msg-endtag"></div>';
					if (typeof jsondata['contactName'] == 'undefined' || jsondata['contactName'] == '') {
						$('#ocsms-phone-label').html(phoneNumberLabel);
						g_curContactName = phoneNumberLabel;
						$('#ocsms-phone-opt-number').html('');
					}
					else {
						$('#ocsms-phone-label').html(jsondata['contactName']);
						g_curContactName = jsondata['contactName'];
						$('#ocsms-phone-opt-number').html(phoneNumberLabel);
					}

					setMessageCountInfo(jsondata);

					if ($('#app-content-header').is(':hidden')) {
						$('#app-content-header').show();
					}

					if ($('#ocsms-conversation-removal').is(':hidden')) {
						$('#ocsms-conversation-removal').show();
					}

					$('#app-content-wrapper').html(conversationBuf);
					$('#app-content').scrollTop(1E10);

					g_curPhoneNumber = phoneNumber;
				}
			);
		}
		$scope.checkNewMessages = function() {
			g_unreadCountAllConv = 0;
			$.getJSON(OC.generateUrl('/apps/ocsms/get/new_messages'),
				{ 'lastDate': g_lastMsgDate },
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

						if (!inArray(peerLabel, bufferedContacts)) {
							$("li[peer-label='" + peerLabel + "']").remove();
							peerListBuf = '<li peer-label="' + peerLabel + '"><div class="ocsms-plavatar"';
							if (typeof jsondata['photos'][peerLabel] != 'undefined') {
								peerListBuf += 'style="background-image: url(' + jsondata['photos'][peerLabel] + ');"';
							}
							peerListBuf += '></div><a href="#" ng-click="loadConversation(' + idxVal2 + ');" mailbox-navigation="' +
								idxVal2 + '" style="font-weight: bold;" mailbox-label="' + peerLabel + '">' + peerLabel + ' (' + val + ')</a></li>';

							g_ulContactList.prepend(peerListBuf);
							bufferedContacts.push(peerLabel);

							// Re-set conversation because we reload the element
							if (idxVal == g_curPhoneNumber) {
								changeSelectedConversation($("a[mailbox-navigation='" + idxVal + "']"));
							}

							g_unreadCountAllConv += parseInt(val);	

							// Now bind the events when we click on the phone number
							$("a[mailbox-navigation='" + idxVal + "']").on('click', function (event) {
								var phoneNumber = $(this).attr('mailbox-navigation');
								OC.Util.History.pushState('phonenumber=' + phoneNumber);

								// phoneNumber must exist
								if (phoneNumber != null) {
									$scope.fetchConversation(phoneNumber);
									g_unreadCountAllConv += val;
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
					
					if (g_unreadCountNotifStep > 0) {
						g_unreadCountNotifStep--;
					}
					
					if (g_unreadCountAllConv > 0) {
						/*
						* We notify user every two minutes for all messages
						* or if unreadCount changes
						*/
						if (g_unreadCountNotifStep == 0 || g_lastUnreadCountAllConv != g_unreadCountAllConv) {
							desktopNotify(g_unreadCountAllConv + " unread message(s) for all conversations");
							g_unreadCountNotifStep = 12;
							g_lastUnreadCountAllConv = g_unreadCountAllConv;
						}
					}
				}
			);
		};

		$scope.removeConversation = function() {
			$.post(OC.generateUrl('/apps/ocsms/delete/conversation'), {"contact": g_curContactName}, function(data) {
				// Reinit main window
				$('#ocsms-phone-label').html('');
				$('#ocsms-phone-opt-number').html('');
				$('#ocsms-phone-msg-nb').html('');
				$('#app-content-wrapper').html('<div id="ocsms-empty-conversation">Please choose a conversation on the left menu</div>');
				$('#ocsms-conversation-removal').hide();
				$('#app-content-header').hide();
				$("li[peer-label='" + g_curContactName + "']").remove();
				g_curPhoneNumber = null;
			});
		};
		$scope.removeMessage = function(messageId) {
			alert('test');
		};
		$scope.addContact = function (ct) {
			$scope.$apply(function () {
				$scope.contacts.push(ct);
			});
		};

		$scope.fetchInitialSettings = function () {
			$.getJSON(OC.generateUrl('/apps/ocsms/get/country'), function(jsondata, status) {
				if (jsondata['status'] == true) {
					$('#sel_intl_phone').val(jsondata["country"]);
				}
			});
		}
		$scope.fetchInitialPeerList = function (jsondata) {
			// Use a buffer for better jQuery performance
			var peerListBuf = "";
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
				if (!inArray(peerLabel, bufferedContacts)) {
					$scope.addContact({'label': peerLabel, 'nav': idxVal2, 'avatar': jsondata['photos'][peerLabel]});
					bufferedContacts.push(peerLabel);
				}
			});

			g_lastMsgDate = jsondata["lastRead"];
		}


		$scope.initDesktopNotifies = function () {
			if (!("Notification" in window)) {
				return;
			}
			
			Notification.requestPermission(function (permission) {
				if(!('permission' in Notification)) {
					Notification.permission = permission;
				}
			});
		}

		$interval(refreshConversation, 10000);
		$interval($scope.checkNewMessages, 10000);

		$timeout(function () {
			// Register real title
			g_originalTitle = document.title;

			// Now bind the events when we click on the phone number
			$.getJSON(OC.generateUrl('/apps/ocsms/get/peerlist'), function(jsondata, status) {
				$scope.fetchInitialPeerList(jsondata);

				var pnParam = $.urlParam('phonenumber');
				if (pnParam != null) {
					var urlPhoneNumber = decodeURIComponent(pnParam);
					if (urlPhoneNumber != null) {
						$scope.fetchConversation(urlPhoneNumber);
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
			$scope.fetchInitialSettings();
			$scope.initDesktopNotifies();
		});
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
	if (g_curPhoneNumber == null) {
		if ($('#app-content-header').is(':visible')) {
			$('#app-content-header').hide();
		}
		return;
	}
	$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
		{
			'phoneNumber': g_curPhoneNumber,
			"lastDate": g_lastMsgDate
		},
		function(jsondata, status) {
			var fmt = formatConversation(jsondata);
			conversationBuf = fmt[1];
			if (conversationBuf != '') {
				$('.msg-endtag').before(conversationBuf);
				$('#app-content').scrollTop(1E10);
				// This will blink the tab because there is new messages
				if (document.hasFocus() == false) {
					g_unreadCountCurrentConv += fmt[0];
					document.title = g_originalTitle + " (" + g_unreadCountCurrentConv + ")";
					desktopNotify(g_unreadCountCurrentConv + " unread message(s) in conversation with " + g_curContactName);
				}
				
			}
			
			setMessageCountInfo(jsondata);
			if ($('#ocsms-conversation-removal').is(':hidden')) {
				$('#ocsms-conversation-removal').show();
			}

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
		if ((id/100) > (g_lastMsgDate/100)) {
			g_lastMsgDate = id;
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
			vals["msg"] + '</div>' + 
			'<div style="display: block;" id="ocsms-message-removal" class="icon-delete svn delete action" ng-click="removeMessage(' + id + ');"></div>' +
			'<div class="msg-date">' + formatedDate + '</div>' +
			'</div><div class="msg-spacer"></div></div>';
		msgCount++;

	});
	return [msgCount,buf];
}

function changeSelectedConversation(item) {
	if (item === 'undefined' || item == null) {
		return;
	}

	if (g_selectedConversation != null) {
		g_selectedConversation.parent().removeClass('selected');
	}
	g_selectedConversation = item;
	g_selectedConversation.parent().addClass('selected');
	g_selectedConversation.css("font-weight", "normal");
	g_selectedConversation.html(g_selectedConversation.attr("mailbox-label"));
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
	// reset count and title
	window.onfocus = function () {
		g_unreadCountCurrentConv = 0;
		document.title = g_originalTitle;
	};
})(jQuery, OC);
