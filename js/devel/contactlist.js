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
		contacts: []
	},
	created: function () {
		this.contacts = [];
		this.fetch();
	},
	methods: {
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

						ContactList.addContact(contactObj);
						bufferedContacts.push(peerLabel);
					}
				});

				self.isContactsLoading = false;
				Sms.lastContactListMsgDate = jsondata["lastRead"];
				Sms.lastMessageDate = jsondata["lastMessage"];

				var pnParam = $.urlParam('phonenumber');
				if (pnParam != null) {
					var urlPhoneNumber = decodeURIComponent(pnParam);
					if (urlPhoneNumber != null) {
						// If no contact when loading, creating a new contact from urlPhoneNumber
						if (app.selectedContact.nav === undefined) {
							app.selectedContact.label = urlPhoneNumber;
							app.selectedContact.nav = urlPhoneNumber;
							app.selectedContact.avatar = undefined;

							// Now let's loop through the contact list and see if we can find the rest of the details
							for (var i = 0; i < self.contacts.length; i++) {
								if (self.contacts[i].nav === urlPhoneNumber) {
									app.selectedContact = self.contacts[i];
									break;
								}
							}
						}
						app.fetchConversation(app.selectedContact);
						Sms.selectConversation($("a[mailbox-navigation='" + urlPhoneNumber + "']"));
					}
				}
			});
		},
		// Conversations
		loadConversation: function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				app.fetchConversation(contact);
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
		}
	},
	computed: {
		orderedContacts: function () {
			return _.orderBy(this.contacts, [SmsSettings.contactOrderBy], [SmsSettings.reverseContactOrder ? 'desc' : 'asc'])
		}
	}
});