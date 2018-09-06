/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

var app = angular.module('OcSms', []);

app.controller('OcSmsController', ['$scope', '$interval', '$timeout', '$compile',
	function ($scope, $interval, $timeout, $compile) {
		$scope.buttons = [
			{text: "Send"}
		];

		$scope.totalMessageCount = 0;
		$scope.lastSearch = '';

		$scope.refreshConversation = function () {
			$.getJSON(Sms.generateURL('/ocsms/front-api/v1/conversation'),
				{
					'phoneNumber': Conversation.selectedContact.nav,
					"lastDate": Conversation.lastConvMessageDate
				},
				function (jsondata, status) {
					var fmt = $scope.formatConversation(jsondata);
					var conversationBuf = fmt[1];
					if (conversationBuf === true) {
						$('#app-conversation').scrollTop(1E10);
						// This will blink the tab because there is new messages
						if (document.hasFocus() === false) {
							Sms.unreadCountCurrentConv += parseInt(fmt[0]);
							document.title = Sms.originalTitle + " (" + Sms.unreadCountCurrentConv + ")";
							SmsNotifications.notify(Sms.unreadCountCurrentConv + " unread message(s) in conversation with " + Conversation.selectedContact.label);
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

							ContactList.modifyContact(contactObj);
							bufferedContacts.push(peerLabel);

							// Re-set conversation because we reload the element
							if (id === Conversation.selectedContact.nav) {
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

		$scope.filterSms = function (query) {
			if (query !== $scope.lastSearch) {
			}
		};

		OC.Plugins.register('OCA.Search', {
			attach: function (search) {
				search.setFilter('sms', $scope.filterSms);
			}
		});

		$interval($scope.refreshConversation, 10000);
		$interval($scope.checkNewMessages, 10000);

		$timeout(function () {
			// Register real title
			Sms.originalTitle = document.title;

			SmsNotifications.init();
			$scope.checkNewMessages();
			$scope.refreshConversation();
		});
	}
]);


