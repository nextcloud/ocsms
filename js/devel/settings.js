/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
 */

var SmsSettings = {
	// Attributes
	messageLimit: 100,
	enableNotifications: true,
	contactOrderBy: 'lastmsg',
	reverseContactOrder: true,
	country: '',

	// Functions
	init: function () {
		$.getJSON(Sms.generateURL('/front-api/v1/settings'), function (jsondata, status) {
			if (jsondata['status'] === true) {
				this.messageLimit = parseInt(jsondata["message_limit"]);
				this.enableNotifications = parseInt(jsondata["notification_state"]) !== 0;
				this.contactOrderBy = jsondata["contact_order"];
				this.reverseContactOrder = toBool(jsondata["contact_order_reverse"]);
				this.country = jsondata["country"];

				this.updateView();
			}
		});
	},

	// This function should be moved to a renderer or something else
	updateView: function () {
		$('#sel_intl_phone').val(this.country);
		$('input[name=setting_msg_per_page]').val(this.messageLimit);
		$('select[name=setting_notif]').val(this.enableNotifications ? 1 : 0);
		$('select[name=setting_contact_order]').val(this.contactOrderBy);
		$('input[name=setting_contact_order_reverse]').val(this.reverseContactOrder);
	}
};