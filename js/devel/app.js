/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

var Sms = {
	// Attributes
	selectedConversation: null,
	unreadCountCurrentConv: 0,
	unreadCountAllConv: 0,
	unreadCountNotifStep: 12,
	lastUnreadCountAllConv: 0,
	lastContactListMsgDate: 0,
	lastMessageDate: 0,
	originalTitle: document.title,
	photoVersion: 1,

	_winRegexp: /(.*)\/ocsms.*/,

	// Functions
	generateURL: function (endpoint) {
		var match = this._winRegexp.exec(window.location.href);
		if (match.length !== 2) {
			console.log("A very bad error happened when parsing window location");
		}
		return match[1] + '/ocsms' + endpoint;
	},
	selectConversation: function (item) {
		if (item === 'undefined' || item == null) {
			return;
		}

		if (this.selectedConversation != null) {
			this.selectedConversation.parent().removeClass('selected');
		}
		this.selectedConversation = item;
		this.selectedConversation.parent().addClass('selected');
		this.selectedConversation.css("font-weight", "normal");
		this.selectedConversation.html(this.selectedConversation.attr("mailbox-label"));
	}
};

var ContactRenderer = {
	generateColor: function (input) {
		if (typeof input === 'undefined') {
			return '';
		}
		// Check if core has the new color generator
		if (typeof input.toHsl === 'function') {
			var hsl = input.toHsl();
			return 'hsl(' + hsl[0] + ', ' + hsl[1] + '%, ' + hsl[2] + '%)';
		} else {
			// If not, we use the old one
			/* global md5 */
			var hash = md5(input).substring(0, 4),
				maxRange = parseInt('ffff', 16),
				hue = parseInt(hash, 16) / maxRange * 256;
			return 'hsl(' + hue + ', 90%, 65%)';
		}
	},
	generateFirstCharacter: function (input) {
		if (typeof input !== 'string') {
			return '?';
		}

		if (input.charAt(0) === '+') {
			return '#';
		}

		return input.charAt(0);
	}
};

Vue.filter('firstCharacter', ContactRenderer.generateFirstCharacter);
