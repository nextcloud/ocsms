/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
 */

var SmsNotifications = {
	init: function () {
		if (!("Notification" in window)) {
			return;
		}

		Notification.requestPermission(function (permission) {
			if (!('permission' in Notification)) {
				Notification.permission = permission;
			}
		});
	},
	notify: function (message) {
		if (!SmsSettings.enableNotifications) {
			return;
		}

		if (!("Notification" in window)) {
			return;
		}

		if (Notification.permission === "granted") {
			new Notification("Phone Sync - " + message);
		}
		else if (Notification.permission !== 'denied') {
			Notification.requestPermission(function (permission) {
				if (!('permission' in Notification)) {
					Notification.permission = permission;
				}
				if (permission === "granted") {
					new Notification("Phone Sync - " + message);
				}
			});
		}
	}
};