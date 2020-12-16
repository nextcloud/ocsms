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


function escapeHTML(string) {
                var str = '' + string
                var matchHtmlRegExp = /["'&<>]/
                var match = matchHtmlRegExp.exec(str)

                if (!match) {
                        return str
                }

                var escape
                var html = ''
                var index = 0
                var lastIndex = 0

                for (index = match.index; index < str.length; index++) {
                        switch (str.charCodeAt(index)) {
                                case 34: // "
                                        escape = '&quot;'
                                        break
                                case 38: // &
                                        escape = '&amp;'
                                        break
                                case 39: // '
                                        escape = '&#39;'
                                        break
                                case 60: // <
                                        escape = '&lt;'
                                        break
                                case 62: // >
                                        escape = '&gt;'
                                        break
                                default:
                                        continue
                        }

                        if (lastIndex !== index) {
                                html += str.substring(lastIndex, index)
                        }

                        lastIndex = index + 1
                        html += escape
                }

                return lastIndex !== index
                        ? html + str.substring(lastIndex, index)
                        : html
}
