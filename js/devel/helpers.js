/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

function inArray(val, arr) {
	return ($.inArray(val, arr) !== -1);
}

function arrayUnique(arr) {
	return arr.filter(function (item, i, arr) {
		return i === arr.indexOf(item);
	});
}

function toBool(str) {
	if (str === "true") {
		return true;
	}
	else if (str === "false") {
		return false;
	}
	return null;
}