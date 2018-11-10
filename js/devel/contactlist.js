/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

var ContactList = new Vue({
	el: '#app-mailbox-peers',
	data: {
		isContactsLoading: true,
		contacts: [],
		lastRetrievedMessageDate: 0,
		totalUnreadMessages: 0,
		lastTotalUnreadCount: 0
	},
	created: function () {
		this.reset();
		this.fetch();
		this.checkNewMessages();
		setInterval(this.checkNewMessages, 10000);
	},
	methods: {
		reset: function () {
			this.contacts = [];
			this.lastRetrievedMessageDate = 0;
			this.totalUnreadMessages = 0;
			this.lastTotalUnreadCount = 0;
		},
		fetch: function () {
			var self = this;
			// Now bind the events when we click on the phone number
			$.getJSON(Sms.generateURL('/front-api/v1/peerlist'), function (jsondata, status) {
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

						self.addContact(contactObj);
						bufferedContacts.push(peerLabel);
					}
				});

				self.isContactsLoading = false;
				Sms.lastContactListMsgDate = jsondata["lastRead"];
				self.lastRetrievedMessageDate = jsondata["lastMessage"];

				var pnParam = $.urlParam('phonenumber');
				if (pnParam != null) {
					var urlPhoneNumber = decodeURIComponent(pnParam);
					if (urlPhoneNumber != null) {
						// If no contact when loading, creating a new contact from urlPhoneNumber
						if (Conversation.selectedContact.nav === undefined) {
							Conversation.selectedContact.label = urlPhoneNumber;
							Conversation.selectedContact.nav = urlPhoneNumber;
							Conversation.selectedContact.avatar = undefined;

							// Now let's loop through the contact list and see if we can find the rest of the details
							for (var i = 0; i < self.contacts.length; i++) {
								if (self.contacts[i].nav === urlPhoneNumber) {
									Conversation.selectedContact = self.contacts[i];
									break;
								}
							}
						}
						Conversation.fetch(Conversation.selectedContact);
						Sms.selectConversation($("a[mailbox-navigation='" + urlPhoneNumber + "']"));
					}
				}
			});
		},
		getContactColor: function(contact_id) {
			return ContactRenderer.generateColor(contact_id);
		},
		// Conversations
		loadConversation: function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				Conversation.fetch(contact);
				Sms.selectConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		},
		/*
		* Contact list management
		*/
		addContact: function (ct) {
			this.contacts.push(ct);
		},
		removeContact: function (ct) {
			var len = this.contacts.length;
			for (var i = 0; i < len; i++) {
				var curCt = this.contacts[i];
				if (curCt['nav'] === ct['nav']) {
					this.contacts.splice(i, 1);
					return;
				}
			}
		},
		modifyContact: function (ct) {
			var len = this.contacts.length;
			for (var i = 0; i < len; i++) {
				if (this.contacts[i]['nav'] === ct['nav']) {
					this.contacts[i].unread = parseInt(ct.unread);
					if (typeof(ct.avatar) !== 'undefined') {
						this.contacts[i].avatar = ct.avatar;
					}
				}
			}
		},
		checkNewMessages: function () {
			this.totalUnreadMessages = 0;
			var self = this;
			$.getJSON(Sms.generateURL('/front-api/v1/new_messages'),
				{'lastDate': this.lastRetrievedMessageDate},
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

							self.totalUnreadMessages += parseInt(val);
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

					if (self.totalUnreadMessages > 0) {
						/*
						* We notify user every two minutes for all messages
						* or if unreadCount changes
						*/
						if (Sms.unreadCountNotifStep === 0 || self.lastTotalUnreadCount !== self.totalUnreadMessages) {
							SmsNotifications.notify(self.totalUnreadMessages + " unread message(s) for all conversations");
							Sms.unreadCountNotifStep = 12;
							self.lastTotalUnreadCount = self.totalUnreadMessages;
						}
					}
				}
			);
		},
	},
	computed: {
		orderedContacts: function () {
			return _.orderBy(this.contacts, [SmsSettings.contactOrderBy], [SmsSettings.reverseContactOrder ? 'desc' : 'asc'])
		}
	}
});