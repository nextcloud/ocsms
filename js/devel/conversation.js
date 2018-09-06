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
	el: '#ocsms-app-content',
	data: {
		selectedContact: {},
		isConvLoading: false,
		messages: [],
		lastConvMessageDate: 0
	},
	created: function () {
	},
	methods: {
		fetchConversation: function (contact) {
		// If contact is not null, we will fetch a conversation for a new contact
		if (contact != null) {
			this.selectedContact = contact;
			this.isConvLoading = true;
		}

		this.messages = [];
		this.lastConvMessageDate = 0;

		var self = this;
		$.getJSON(Sms.generateURL('/front-api/v1/conversation'), {'phoneNumber': $scope.selectedContact.nav},
			function (jsondata, status) {
				var phoneNumberLabel = self.selectedContact.nav;

				if (typeof jsondata['phoneNumbers'] !== 'undefined') {
					var phoneNumberList = arrayUnique(jsondata['phoneNumbers']);
					phoneNumberLabel = phoneNumberList.toString();
				}

				// Reinit messages before showing conversation
				app.formatConversation(jsondata);

				$scope.$apply(function () {
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
				});

				$('#ocsms-app-content').scrollTop(1E10);
			}
		);
	};
	}
});