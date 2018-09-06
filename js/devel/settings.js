/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

var SmsSettings = new Vue({
	el: '#app-settings',
	data: {
		// Attributes
		messageLimit: 100,
		enableNotifications: true,
		contactOrderBy: 'lastmsg',
		reverseContactOrder: true,
		country: ''
	},

	// Functions
	created: function () {
		var self = this;
		$.getJSON(Sms.generateURL('/front-api/v1/settings'), function (jsondata, status) {
			if (jsondata['status'] === true) {
				self.messageLimit = parseInt(jsondata["message_limit"]);
				self.enableNotifications = parseInt(jsondata["notification_state"]) !== 0 ? 1 : 0;
				self.contactOrderBy = jsondata["contact_order"];
				self.reverseContactOrder = toBool(jsondata["contact_order_reverse"]);
				self.country = jsondata["country"];
			}
		});
	},

	methods: {
		sendMessageLimit: function () {
			if (this.messageLimit === null) {
				return;
			}

			var self = this;
			$.post(Sms.generateURL('/set/msglimit'),
				{
					'limit': self.messageLimit
				}
			);
		},
		sendNotificationFlag: function () {
			var self = this;
			$.post(Sms.generateURL('/set/notification_state'),
				{
					'notification': parseInt(self.enableNotifications)
				}
			);
		},
		sendContactOrder: function () {
			var self = this;
			$.post(Sms.generateURL('/set/contact_order'),
				{
					'attribute': self.contactOrderBy,
					'reverse': self.reverseContactOrder
				}
			);
		},
		sendCountry: function () {
			var self = this;
			$.post(Sms.generateURL('/set/country'),
				{
					'country': self.country
				}
			);
		}
	}
});