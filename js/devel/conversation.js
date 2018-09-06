/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

var Conversation = new Vue({
	el: '#app-conversation',
	data: {
		selectedContact: {},
		isConvLoading: false,
		messages: [],
		lastConvMessageDate: 0,
		totalMessageCount: 0
	},
	methods: {
		fetch: function (contact) {
			// If contact is not null, we will fetch a conversation for a new contact
			if (contact != null) {
				this.selectedContact = contact;
				this.isConvLoading = true;
			}

			this.messages = [];
			this.lastConvMessageDate = 0;

			var self = this;
			$.getJSON(Sms.generateURL('/front-api/v1/conversation'), {'phoneNumber': self.selectedContact.nav},
				function (jsondata, status) {
					var phoneNumberLabel = self.selectedContact.nav;

					if (typeof jsondata['phoneNumbers'] !== 'undefined') {
						var phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
						phoneNumberLabel = phoneNumberList.toString();
					}

					// Reinit messages before showing conversation
					self.formatConversation(jsondata);

					if (typeof jsondata['contactName'] === 'undefined' || jsondata['contactName'] === '') {
						self.selectedContact.label = phoneNumberLabel;
						self.selectedContact.opt_numbers = "";
					}
					else {
						self.selectedContact.label = jsondata['contactName'];
						self.selectedContact.opt_numbers = phoneNumberLabel;
					}

					self.totalMessageCount = jsondata['msgCount'] !== undefined ? jsondata['msgCount'] : 0;
					self.isConvLoading = false;

					$('#app-conversation').scrollTop(1E10);
				}
			);
		},
		getContactColor: function(contact_id) {
			return ContactRenderer.generateColor(contact_id);
		},
		// Return (int) msgCount, (str) htmlConversation
		formatConversation: function (jsondata) {
			// Improve jQuery performance
			var buf = false;
			// Improve JS performance
			var msgClass = '';
			var msgCount = 0;

			$.each(jsondata["conversation"], function (id, vals) {
				if (vals["type"] === 1) {
					msgClass = "recv";
				}
				else if (vals["type"] === 2) {
					msgClass = "sent";
				}
				else {
					msgClass = 'unknown';
				}

				// Store the greater msg date for refresher
				// Note: we divide by 100 because number compare too large integers
				if ((id / 100) > (this.lastConvMessageDate / 100)) {
					this.lastConvMessageDate = id;

					// Multiplicate ID to permit date to use it properly
					this.addConversationMessage({
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
		},
		/*
		* Conversation messagelist management
		*/
		addConversationMessage: function (msg) {
			this.messages.push(msg);
		},
		removeConversationMessage: function (msgId) {
			var len = $scope.messages.length;
			var self = this;
			for (var i = 0; i < len; i++) {
				var curMsg = this.messages[i];
				if (curMsg['id'] === msgId) {
					$.post(Sms.generateURL('/delete/message'),
						{
							"messageId": msgId,
							"phoneNumber": this.selectedContact.label
						}, function (data) {
							self.messages.splice(i, 1);
						});
					return;
				}
			}
		}
	}
});