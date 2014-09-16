/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014
 */

(function ($, OC) {
	$(document).ready(function () {
		// Now bind the events when we click on the phone number
		$('#app-navigation').find('a').on('click', function (event) {
			OC.Util.History.pushState('feed=' + $(this).attr('nav-feed'));
                	event.preventDefault();
        	});

		$.getJSON(OC.generateUrl('/apps/ocsms/get/peerlist'), function(jsondata, status) {
			var peerListBuf = "";
			$.each(jsondata['phonelist'], function(id, val) {
				peerListBuf += '<li><a href="#" mailbox-navigation="' + val + '">' + val + '</a></li>';
			});
			$('#app-mailbox-peers ul').html(peerListBuf);
			
			// Now bind the events when we click on the phone number
			$('#app-mailbox-peers').find('a[mailbox-navigation]').on('click', function (event) {
				OC.Util.History.pushState('phonenumber=' + $(this).attr('mailbox-navigation'));
                		event.preventDefault();
        		});

	        });
	});

})(jQuery, OC);
