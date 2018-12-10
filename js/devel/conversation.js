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
	el: '#app-content',
	data: {
		selectedContact: {},
		isConvLoading: false,
		messages: [],
		lastConvMessageDate: 0,
		totalMessageCount: 0,
		refreshIntervalId: null
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

            let self = this;
            $.getJSON(Sms.generateURL('/front-api/v1/conversation'), {'phoneNumber': self.selectedContact.nav},
				function (jsondata, status) {
                    let phoneNumberLabel = self.selectedContact.nav;

                    if (typeof jsondata['phoneNumbers'] !== 'undefined') {
                        const phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
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

					$('#app-content').scrollTop(1E10);

					// If refreshInterval is already bound, clear previous
					if (self.refreshIntervalId !== null) {
						clearInterval(self.refreshIntervalId);
					}

					self.refreshIntervalId = setInterval(self.refresh, 10000);
				}
			);
		},
		refresh: function () {
			var self = this;
			$.getJSON(Sms.generateURL('/front-api/v1/conversation'),
				{
					'phoneNumber': Conversation.selectedContact.nav,
					"lastDate": Conversation.lastConvMessageDate
				},
				function (jsondata, status) {
					var fmt = self.formatConversation(jsondata);
					var conversationBuf = fmt[1];
					if (conversationBuf === true) {
						$('#app-content').scrollTop(1E10);
						// This will blink the tab because there is new messages
						if (document.hasFocus() === false) {
							Sms.unreadCountCurrentConv += parseInt(fmt[0]);
							document.title = Sms.originalTitle + " (" + Sms.unreadCountCurrentConv + ")";
							SmsNotifications.notify(Sms.unreadCountCurrentConv + " unread message(s) in conversation with "
								+ Conversation.selectedContact.label);
						}

					}

					self.totalMessageCount = jsondata['msgCount'] !== undefined ? parseInt(jsondata['msgCount']) : 0;
				}
			);
		},
		getContactColor: function(contact_id) {
			return ContactRenderer.generateColor(contact_id);
		},
		// Return (int) msgCount, (str) htmlConversation
		formatConversation: function (jsondata) {
			// Improve jQuery performance
      let buf = false;
      // Improve JS performance
      let msgClass = '';
      let msgCount = 0;
			let self = this;
			let twemojiOptions = { base: OC.generateUrl('/apps/ocsms/js/twemoji/')};

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
				if ((id / 100) > (self.lastConvMessageDate / 100)) {
					self.lastConvMessageDate = id;

					// Multiplicate ID to permit date to use it properly
					self.addConversationMessage({
						'id': id,
						'type': msgClass,
						'date': new Date(id * 1),
						'content': twemoji.parse(anchorme(escapeHTML(vals['msg'])), twemojiOptions)
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
			const len = this.messages.length;
			let self = this;
			for (var i = 0; i < len; i++) {
				var curMsg = this.messages[i];
				if (curMsg['id'] === msgId) {
					$.post(Sms.generateURL('/delete/message'),
						{
							"messageId": msgId,
							"phoneNumber": this.selectedContact.label
						}, function (data) {
							self.messages.splice(i, 1);
							if (self.messages.length === 0) {
								self.clear();
							}
						});
					return;
				}
			}
		},
		removeConversation: function () {
			var self = this;
			$.post(Sms.generateURL('/delete/conversation'), {"contact": self.selectedContact.label}, function (data) {
				self.clear();
			});
		},
		clear: function () {
			// Reinit main window
			this.selectedContact.label = "";
			this.selectedContact.opt_numbers = "";
			this.selectedContact.avatar = undefined;
			ContactList.removeContact(this.selectedContact);
			this.messages = [];
			this.selectedContact = {};
			OC.Util.History.pushState('');
			clearInterval(this.refreshIntervalId);
			this.refreshIntervalId = null;
		}
	},
	computed: {
		orderedMessages: function () {
			return _.orderBy(this.messages, ['date'], ['desc'])
		}
	}
});