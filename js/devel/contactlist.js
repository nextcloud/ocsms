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

	},
	methods: {
		// Conversations
		loadConversation: function (contact) {
			OC.Util.History.pushState('phonenumber=' + contact.nav);

			// phoneNumber must exist
			if (contact.nav !== null) {
				app.fetchConversation(contact);
				Sms.selectConversation($("a[mailbox-navigation='" + contact.nav + "']"));
			}
		}
	},
	computed: {
		orderedContacts: function () {
			return _.orderBy(this.contacts, SmsSettings.contactOrderBy, SmsSettings.reverseContactOrder)
		}
	}
});