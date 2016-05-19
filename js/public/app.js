/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2016
 */


// Some global vars to improve performances
var g_selectedConversation = null;
var g_lastMsgDate = 0;
var g_unreadCountCurrentConv = 0;
var g_unreadCountAllConv = 0;
var g_unreadCountNotifStep = 12;
var g_lastUnreadCountAllConv = 0;
var g_originalTitle = document.title;

var app = angular.module('OcSms', []);

function inArray(val, arr) {
	return ($.inArray(val, arr) != -1);
}

function arrayUnique(arr) {
	return arr.filter(function (item, i, arr) {
		return i == arr.indexOf(item);
	});
}

// Imported from ownCloud contact app
app.filter('peerColor', function() {
	return function(input) {
		// Check if core has the new color generator
		if(typeof input.toHsl === 'function') {
			var hsl = input.toHsl();
			return 'hsl('+hsl[0]+', '+hsl[1]+'%, '+hsl[2]+'%)';
		} else {
			// If not, we use the old one
			/* global md5 */
			var hash = md5(input).substring(0, 4),
				maxRange = parseInt('ffff', 16),
				hue = parseInt(hash, 16) / maxRange * 256;
			return 'hsl(' + hue + ', 90%, 65%)';
		}
	};
});

app.filter('firstCharacter', function() {
		return function(input) {
			if (input.charAt(0) == '+') {
				return '#';
			}

			return input.charAt(0);
		};
	});


app.controller('OcSmsController', ['$scope', '$interval', '$timeout', '$compile',
	function ($scope, $interval, $timeout, $compile) {
		$scope.buttons = [
			{text: "Send"}
		];

		$scope.setting_msgLimit = 100;
		$scope.setting_enableNotifications = 1;

		$scope.contacts = [];
		$scope.messages = [];
		$scope.totalMessageCount = 0;
		$scope.photoVersion = 1;
		$scope.selectedContact = {};

		// Settings
		$scope.sendCountry = function () {
			$.post(OC.generateUrl('/apps/ocsms/set/country'),{'country': $('select[name=intl_phone]').val()});
		};

		$scope.setMessageLimit = function () {
			if ($scope.setting_msgLimit === null || $scope.setting_msgLimit === undefined) {
				return;
			}
			$.post(OC.generateUrl('/apps/ocsms/set/msglimit'),{'limit': $scope.setting_msgLimit});
		};

		$scope.setNotificationSetting = function () {
			if ($scope.setting_enableNotifications < 0 || $scope.setting_enableNotifications > 2) {
				$scope.setting_enableNotifications = 0;
				return;
			}
			$.post(OC.generateUrl('/apps/ocsms/set/notification_state'),{'notification': $scope.setting_enableNotifications});
		};

		// Conversations
		$scope.loadConversation = function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				$scope.fetchConversation(contact);
				changeSelectedConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		};
		$scope.fetchConversation = function (contact) {
			// If contact is not null, we will fetch a conversation for a new contact
			if (contact != null) {
				$scope.selectedContact = contact;
			}
			$scope.messages = [];
			$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'), {'phoneNumber': $scope.selectedContact.nav},
				function(jsondata, status) {
					var phoneNumberLabel = phoneNumber;
					$scope.selectedContact.nav = phoneNumber;

					if (typeof jsondata['phoneNumbers'] != 'undefined') {
						phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
						phoneNumberLabel = phoneNumberList.toString();
					}

					// Reinit messages before showing conversation
					$scope.formatConversation(jsondata);

					if (typeof jsondata['contactName'] == 'undefined' || jsondata['contactName'] == '') {
						$scope.selectedContact.label = phoneNumberLabel;
						$scope.selectedContact.opt_numbers = "";
					}
					else {
						$scope.selectedContact.label = jsondata['contactName'];
						$scope.selectedContact.opt_numbers = phoneNumberLabel;
					}

					$scope.totalMessageCount = jsondata['msgCount'] !== undefined ? jsondata['msgCount'] : 0;

					$('#app-content').scrollTop(1E10);
				}
			);
		};
		$scope.refreshConversation = function() {
			$.getJSON(OC.generateUrl('/apps/ocsms/get/conversation'),
				{
					'phoneNumber': $scope.selectedContact.nav,
					"lastDate": g_lastMsgDate
				},
				function(jsondata, status) {
					var fmt = $scope.formatConversation(jsondata);
					var conversationBuf = fmt[1];
					if (conversationBuf == true) {
						$('#app-content').scrollTop(1E10);
						// This will blink the tab because there is new messages
						if (document.hasFocus() == false) {
							g_unreadCountCurrentConv += fmt[0];
							document.title = g_originalTitle + " (" + g_unreadCountCurrentConv + ")";
							$scope.desktopNotify(g_unreadCountCurrentConv + " unread message(s) in conversation with " + $scope.selectedContact.label);
						}
						
					}
					
					$scope.totalMessageCount = jsondata['msgCount'] !== undefined ? jsondata['msgCount'] : 0;
				}
			);
		};
		$scope.checkNewMessages = function() {
			g_unreadCountAllConv = 0;
			$.getJSON(OC.generateUrl('/apps/ocsms/get/new_messages'),
				{ 'lastDate': g_lastMsgDate },
				function(jsondata, status) {
					var bufferedContacts = [];

					$.each(jsondata['phonelist'], function(id, val) {
						var fn, peerLabel, idxVal;
						idxVal = id.replace(/\//g,' ');
						idxVal2 = idxVal.replace('/ /g','');
						if (typeof jsondata['contacts'][id] == 'undefined') {
							peerLabel = idxVal;
						}
						else {
							fn = jsondata['contacts'][id];
							peerLabel = fn;
						}

						if (!inArray(peerLabel, bufferedContacts)) {
							var contactObj = {
								'label': peerLabel,
								'nav': idxVal2,
								'avatar': jsondata['photos'][peerLabel],
								'unread': val
							};

							$scope.removeContact(contactObj);
							$scope.addContactToFront(contactObj);

							bufferedContacts.push(peerLabel);

							// Re-set conversation because we reload the element
							if (idxVal == $scope.selectedContact.nav) {
								changeSelectedConversation($("a[mailbox-navigation='" + idxVal + "']"));
							}

							g_unreadCountAllConv += parseInt(val);
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
							$scope.desktopNotify(g_unreadCountAllConv + " unread message(s) for all conversations");
							g_unreadCountNotifStep = 12;
							g_lastUnreadCountAllConv = g_unreadCountAllConv;
						}
					}
				}
			);
		};

		$scope.removeConversation = function() {
			$.post(OC.generateUrl('/apps/ocsms/delete/conversation'), {"contact": $scope.selectedContact.label}, function(data) {
				// Reinit main window
				$scope.selectedContact.label = "";
				$scope.selectedContact.opt_numbers = "";
				$scope.removeContact($scope.selectedContact);
				$scope.$apply(function () {
					$scope.messages = [];
				});
				$scope.selectedContact.nav = "";
			});
		};

		/*
		* Contact list management
		*/
		$scope.addContact = function (ct) {
			$scope.$apply(function () {
				$scope.contacts.push(ct);
			});
		};
		$scope.addContactToFront = function (ct) {
			$scope.$apply(function () {
				$scope.contacts.splice(0, 0, ct);
			});
		};
		$scope.removeContact = function (ct) {
			var len = $scope.contacts.length;
			for (var i=0; i < len; i++) {
				var curCt = $scope.contacts[i];
				if (curCt['nav'] == ct['nav']) {
					$scope.$apply(function () {
						$scope.contacts.splice(i, 1);
					});
					return;
				}
			}
		};

		/*
		* Conversation messagelist management
		*/
		$scope.addConversationMessage = function (msg) {
			$scope.$apply(function () {
				$scope.messages.push(msg);
			});
		};

		$scope.removeConversationMessage = function (msgId) {
			var len = $scope.messages.length;
			for (var i=0; i < len; i++) {
				var curMsg = $scope.messages[i];
				if (curMsg['id'] == msgId) {
					$.post(OC.generateUrl('/apps/ocsms/delete/message'),
						{"messageId": msgId, "phoneNumber": $scope.selectedContact.label}, function(data) {
						$scope.$apply(function () {
							$scope.messages.splice(i, 1);
						});
					});
					return;
				}
			}
		};

		$scope.fetchInitialSettings = function () {
			$.getJSON(OC.generateUrl('/apps/ocsms/get/settings'), function(jsondata, status) {
				if (jsondata['status'] == true) {
					$('#sel_intl_phone').val(jsondata["country"]);

					$('input[name=setting_msg_per_page]').val(jsondata["message_limit"]);
					$('select[name=setting_notif]').val(jsondata["notification_state"]);

					$scope.setting_msgLimit = jsondata["message_limit"];
					$scope.setting_enableNotifications = jsondata["notification_state"];
				}
			});
		};

		$scope.fetchInitialPeerList = function (jsondata) {
			// Use a buffer for better jQuery performance
			var peerListBuf = "";
			var photoPrefix = "";
			var bufferedContacts = [];

			$scope.photoVersion = jsondata["photo_version"];
			if ($scope.photoVersion >= 2) {
				photoPrefix = "data:image/png;base64,";
			}

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
					$scope.addContact({'label': peerLabel, 'nav': idxVal2, 'avatar': photoPrefix + jsondata['photos'][peerLabel], 'unread' : 0});
					bufferedContacts.push(peerLabel);
				}
			});

			g_lastMsgDate = jsondata["lastRead"];
		};


		$scope.initDesktopNotifies = function () {
			if (!("Notification" in window)) {
				return;
			}
			
			Notification.requestPermission(function (permission) {
				if(!('permission' in Notification)) {
					Notification.permission = permission;
				}
			});
		};

		// Return (int) msgCount, (str) htmlConversation
		$scope.formatConversation = function (jsondata) {
			// Improve jQuery performance
			var buf = false;
			// Improve JS performance
			var msgClass = '';
			var msgCount = 0;

			$.each(jsondata["conversation"], function(id, vals) {
				if (vals["type"] == 1) {
					msgClass = "recv";
				}
				else if (vals["type"] == 2) {
					msgClass = "sent";
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
				$scope.addConversationMessage({'id': id, 'type': msgClass, 'date': new Date(id * 1), 'content': vals['msg']});
				buf = true;
				msgCount++;

			});
			return [msgCount,buf];
		};

		$scope.desktopNotify = function (msg) {
			if ($scope.setting_enableNotifications == 0) {
				return;
			}

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
		};

		$interval($scope.refreshConversation, 10000);
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
						$scope.fetchConversation(null);
						changeSelectedConversation($("a[mailbox-navigation='" + urlPhoneNumber + "']"));
					}
				}

			});
			$scope.fetchInitialSettings();
			$scope.initDesktopNotifies();
			$scope.checkNewMessages();
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

(function ($, OC) {
	// reset count and title
	window.onfocus = function () {
		g_unreadCountCurrentConv = 0;
		document.title = g_originalTitle;
	};
})(jQuery, OC);
