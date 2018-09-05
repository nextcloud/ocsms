/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
 */

var app = angular.module('OcSms', []);

// Imported from contact app
app.filter('peerColor', function () {
	return ContactRenderer.generateColor;
});

app.filter('firstCharacter', function () {
	return ContactRenderer.generateFirstCharacter;
});


app.controller('OcSmsController', ['$scope', '$interval', '$timeout', '$compile',
	function ($scope, $interval, $timeout, $compile) {
		$scope.lastConvMessageDate = 0;
		$scope.isConvLoading = false;
		$scope.isContactsLoading = true;
		$scope.buttons = [
			{text: "Send"}
		];

		$scope.contacts = [];
		$scope.messages = [];
		$scope.totalMessageCount = 0;
		$scope.selectedContact = {};
		$scope.lastSearch = '';

		// Conversations
		$scope.loadConversation = function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				$scope.fetchConversation(contact);
				Sms.selectConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		};

		$scope.fetchConversation = function (contact) {
			// If contact is not null, we will fetch a conversation for a new contact
			if (contact != null) {
				$scope.selectedContact = contact;
				$scope.isConvLoading = true;
			}

			$scope.messages = [];
			$scope.lastConvMessageDate = 0;

			$.getJSON(Sms.generateURL('/front-api/v1/conversation'), {'phoneNumber': $scope.selectedContact.nav},
				function (jsondata, status) {
					var phoneNumberLabel = $scope.selectedContact.nav;

					if (typeof jsondata['phoneNumbers'] !== 'undefined') {
						var phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
						phoneNumberLabel = phoneNumberList.toString();
					}

					// Reinit messages before showing conversation
					$scope.formatConversation(jsondata);

					$scope.$apply(function () {
						if (typeof jsondata['contactName'] === 'undefined' || jsondata['contactName'] === '') {
							$scope.selectedContact.label = phoneNumberLabel;
							$scope.selectedContact.opt_numbers = "";
						}
						else {
							$scope.selectedContact.label = jsondata['contactName'];
							$scope.selectedContact.opt_numbers = phoneNumberLabel;
						}

						$scope.totalMessageCount = jsondata['msgCount'] !== undefined ? jsondata['msgCount'] : 0;
						$scope.isConvLoading = false;
					});

					$('#ocsms-app-content').scrollTop(1E10);
				}
			);
		};
		$scope.refreshConversation = function () {
			$.getJSON(Sms.generateURL('/ocsms/front-api/v1/conversation'),
				{
					'phoneNumber': $scope.selectedContact.nav,
					"lastDate": $scope.lastConvMessageDate
				},
				function (jsondata, status) {
					var fmt = $scope.formatConversation(jsondata);
					var conversationBuf = fmt[1];
					if (conversationBuf === true) {
						$('#ocsms-app-content').scrollTop(1E10);
						// This will blink the tab because there is new messages
						if (document.hasFocus() === false) {
							Sms.unreadCountCurrentConv += parseInt(fmt[0]);
							document.title = Sms.originalTitle + " (" + Sms.unreadCountCurrentConv + ")";
							SmsNotifications.notify(Sms.unreadCountCurrentConv + " unread message(s) in conversation with " + $scope.selectedContact.label);
						}

					}

					$scope.totalMessageCount = jsondata['msgCount'] !== undefined ? parseInt(jsondata['msgCount']) : 0;
				}
			);
		};
		$scope.checkNewMessages = function () {
			Sms.unreadCountAllConv = 0;
			$.getJSON(Sms.generateURL('/front-api/v1/new_messages'),
				{'lastDate': Sms.lastMessageDate},
				function (jsondata, status) {
					var bufferedContacts = [];

					$.each(jsondata['phonelist'], function (id, val) {
						var fn, peerLabel;
						if (typeof jsondata['contacts'][id] === 'undefined') {
							peerLabel = id;
						}
						else {
							fn = jsondata['contacts'][id];
							peerLabel = fn;
						}

						if (!inArray(peerLabel, bufferedContacts)) {
							var contactObj = {
								'label': peerLabel,
								'nav': id,
								'unread': parseInt(val)
							};

							if (typeof jsondata['photos'][peerLabel] !== 'undefined') {
								contactObj.avatar = jsondata['photos'][peerLabel];
							}

							if (typeof jsondata['uids'][peerLabel] !== 'undefined') {
								contactObj.uid = jsondata['uids'][peerLabel];
							} else {
								contactObj.uid = peerLabel;
							}

							$scope.modifyContact(contactObj);
							bufferedContacts.push(peerLabel);

							// Re-set conversation because we reload the element
							if (id === $scope.selectedContact.nav) {
								Sms.selectConversation($("a[mailbox-navigation='" + id + "']"));
							}

							Sms.unreadCountAllConv += parseInt(val);
						}
					});

					/*
					* Decrement notification step counter, but stop it a zero
					* Stop at zero permit to notify instanly the user when
					* there is new messages in all conversations
					*/

					if (Sms.unreadCountNotifStep > 0) {
						Sms.unreadCountNotifStep--;
					}

					if (Sms.unreadCountAllConv > 0) {
						/*
						* We notify user every two minutes for all messages
						* or if unreadCount changes
						*/
						if (Sms.unreadCountNotifStep === 0 || Sms.lastUnreadCountAllConv !== Sms.unreadCountAllConv) {
							SmsNotifications.notify(Sms.unreadCountAllConv + " unread message(s) for all conversations");
							Sms.unreadCountNotifStep = 12;
							Sms.lastUnreadCountAllConv = Sms.unreadCountAllConv;
						}
					}
				}
			);
		};

		$scope.removeConversation = function () {
			$.post(Sms.generateURL('/delete/conversation'), {"contact": $scope.selectedContact.label}, function (data) {
				// Reinit main window
				$scope.selectedContact.label = "";
				$scope.selectedContact.opt_numbers = "";
				$scope.selectedContact.avatar = undefined;
				$scope.removeContact($scope.selectedContact);
				$scope.$apply(function () {
					$scope.messages = [];
				});
				$scope.selectedContact.nav = "";
				OC.Util.History.pushState('');
			});
		};

		$scope.filterSms = function (query) {
			if (query !== $scope.lastSearch) {
			}
		};

		OC.Plugins.register('OCA.Search', {
			attach: function (search) {
				search.setFilter('sms', $scope.filterSms);
			}
		});

		/*
		* Contact list management
		*/
		$scope.addContact = function (ct) {
			$scope.$apply(function () {
				$scope.contacts.push(ct);
			});
		};

		$scope.removeContact = function (ct) {
			var len = $scope.contacts.length;
			for (var i = 0; i < len; i++) {
				var curCt = $scope.contacts[i];
				if (curCt['nav'] === ct['nav']) {
					$scope.$apply(function () {
						$scope.contacts.splice(i, 1);
					});
					return;
				}
			}
		};

		$scope.modifyContact = function (ct) {
			var len = $scope.contacts.length;
			for (var i = 0; i < len; i++) {
				if ($scope.contacts[i]['nav'] === ct['nav']) {
					$scope.$apply(function () {
						$scope.contacts[i].unread = parseInt(ct.unread);
						if (typeof(ct.avatar) !== 'undefined') {
							$scope.contacts[i].avatar = ct.avatar;
						}
					});
				}
			}
		};

		$scope.getContactOrderBy = function(ct) {
			return SmsSettings.data.contactOrderBy;
		};

		$scope.getReverseContactOrder = function(ct) {
			return SmsSettings.data.reverseContactOrder;
		}

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
			for (var i = 0; i < len; i++) {
				var curMsg = $scope.messages[i];
				if (curMsg['id'] === msgId) {
					$.post(Sms.generateURL('/delete/message'),
						{"messageId": msgId, "phoneNumber": $scope.selectedContact.label}, function (data) {
							$scope.$apply(function () {
								$scope.messages.splice(i, 1);
							});
						});
					return;
				}
			}
		};

		$scope.fetchInitialPeerList = function (jsondata) {
			// Use a buffer for better jQuery performance
			var bufferedContacts = [];

			Sms.photoVersion = jsondata["photo_version"];

			$.each(jsondata['phonelist'], function (id, val) {
				var peerLabel;
				if (typeof jsondata['contacts'][id] === 'undefined') {
					peerLabel = id;
				}
				else {
					peerLabel = jsondata['contacts'][id];
				}
				if (!inArray(peerLabel, bufferedContacts)) {
					var contactObj = {
						'label': peerLabel,
						'nav': id,
						'unread': 0,
						'lastmsg': parseInt(val)
					};

					if (typeof(jsondata['photos'][peerLabel]) !== 'undefined') {
						contactObj['avatar'] = jsondata['photos'][peerLabel];
					}

					if (typeof jsondata['uids'][peerLabel] !== 'undefined') {
						contactObj.uid = jsondata['uids'][peerLabel];
					} else {
						contactObj.uid = peerLabel;
					}

					$scope.addContact(contactObj);
					bufferedContacts.push(peerLabel);
				}
			});

			$scope.$apply(function () {
				$scope.isContactsLoading = false;
			});

			Sms.lastContactListMsgDate = jsondata["lastRead"];
			Sms.lastMessageDate = jsondata["lastMessage"];
		};

		// Return (int) msgCount, (str) htmlConversation
		$scope.formatConversation = function (jsondata) {
			// Improve jQuery performance
			var buf = false;
			// Improve JS performance
			var msgClass = '';
			var msgCount = 0;

			$.each(jsondata["conversation"], function (id, vals) {
				if (vals["type"] == 1) {
					msgClass = "recv";
				}
				else if (vals["type"] == 2) {
					msgClass = "sent";
				}
				else {
					msgClass = 'unknown';
				}

				// Store the greater msg date for refresher
				// Note: we divide by 100 because number compare too large integers
				if ((id / 100) > ($scope.lastConvMessageDate / 100)) {
					$scope.lastConvMessageDate = id;

					// Multiplicate ID to permit date to use it properly
					$scope.addConversationMessage({
						'id': id,
						'type': msgClass,
						'date': new Date(id * 1),
						'content': vals['msg']
					});
					buf = true;
					msgCount++;
				}

			});
			return [msgCount, buf];
		};

		$interval($scope.refreshConversation, 10000);
		$interval($scope.checkNewMessages, 10000);

		$timeout(function () {
			// Register real title
			Sms.originalTitle = document.title;

			// Now bind the events when we click on the phone number
			$.getJSON(Sms.generateURL('/front-api/v1/peerlist'), function (jsondata, status) {
				$scope.fetchInitialPeerList(jsondata);

				var pnParam = $.urlParam('phonenumber');
				if (pnParam != null) {
					var urlPhoneNumber = decodeURIComponent(pnParam);
					if (urlPhoneNumber != null) {
						// If no contact when loading, creating a new contact from urlPhoneNumber
						if ($scope.selectedContact.nav === undefined) {
							$scope.selectedContact.label = urlPhoneNumber;
							$scope.selectedContact.nav = urlPhoneNumber;
							$scope.selectedContact.avatar = undefined;

							// Now let's loop through the contact list and see if we can find the rest of the details
							for (var i = 0; i < $scope.contacts.length; i++) {
								if ($scope.contacts[i].nav == urlPhoneNumber) {
									$scope.selectedContact = $scope.contacts[i];
									break;
								}
							}
						}
						$scope.fetchConversation($scope.selectedContact);
						Sms.selectConversation($("a[mailbox-navigation='" + urlPhoneNumber + "']"));
					}
				}
			});
			SmsNotifications.init();
			$scope.checkNewMessages();
			$scope.refreshConversation();
		});
	}
]);

$.urlParam = function (name) {
	var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	if (results == null) {
		return null;
	}
	else {
		return results[1] || 0;
	}
};

(function ($, OC) {
	// reset count and title
	window.onfocus = function () {
		Sms.unreadCountCurrentConv = 0;
		document.title = Sms.originalTitle;
	};
})(jQuery, OC);

