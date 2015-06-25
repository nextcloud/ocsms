<?php
use \OCA\OcSms\Lib\CountryCodes;

\OCP\Util::addScript('ocsms', 'angular/angular.min');
\OCP\Util::addScript('ocsms', 'public/app');
\OCP\Util::addStyle('ocsms', 'style');
?>

<div class="ng-scope" id="app" ng-app="OcSms" ng-controller="OcSmsController">
	<div id="app-mailbox-peers">
		<ul class="contact-list">
			<li ng-repeat="contact in contacts" peer-label="{{ contact.label }}">
				<div class="ocsms-plavatar" style="background-image: url('{{ contact.avatar }}');"></div>
				<a style="{{ contact.unread > 0 ? 'font-weight:bold;' : ''}}" mailbox-label="{{ contact.label }}" mailbox-navigation="{{ contact.nav }}" ng-click="loadConversation(contact);" href="#">{{ contact.label }}{{ contact.unread > 0 ? ' (' + contact.unread + ') ' : '' }}</a>
			</li>
		</ul>
	</div>
	<div id="app-settings" class="ng-scope">
		<div id="app-settings-header">
			<button name="app settings" class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
		</div>
		<div id="app-settings-content">
			<select name="intl_phone" id="sel_intl_phone">
			<?php foreach (CountryCodes::$codes as $code => $cval) { ?>
			<option><?php p($code); ?></option>
			<?php } ?>
			</select>
			<button class="new-button primary icon-checkmark-white" ng-click="sendCountry();"></button>
		</div> <!-- app-settings-content -->
	</div>

	<div id="app-content">
		<div id="app-content-header" style="display: none;">	
			<div id="ocsms-phone-label"></div><div id="ocsms-conversation-removal" class="icon-delete svn delete action" ng-click="removeConversation();"></div>
			<div id="ocsms-phone-opt-number"></div>
			<div id="ocsms-phone-msg-nb"></div>
			
		</div>
		<div id="app-content-wrapper">
			<div ng-show="messages.length == 0" id="ocsms-empty-conversation">Please choose a conversation on the left menu</div>
			<div ng-repeat="message in messages">
				<div class="msg-{{ message.type }}">
					<div>{{ message.content }}</div>
					<div style="display: block;" id="ocsms-message-removal" class="icon-delete svn delete action" ng-click="removeConversationMessage({{ message.id }});"></div>
					<div class="msg-date">{{ message.date }}</div>
				</div>
				<div class="msg-spacer"></div>
			</div>
		</div>
	</div>
</div>
