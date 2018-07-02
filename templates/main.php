<?php
use \OCA\OcSms\Lib\CountryCodes;

\OCP\Util::addScript('ocsms', 'angular.min');
\OCP\Util::addScript('ocsms', 'app.min');
\OCP\Util::addScript('ocsms', 'vue.min');
\OCP\Util::addStyle('ocsms', 'style');
?>
<script src="https://twemoji.maxcdn.com/2/twemoji.min.js?11.0"></script>

<div class="ng-scope" id="app" ng-app="OcSms" ng-controller="OcSmsController">
	<div id="app-mailbox-peers">
		<div id="app-contacts-loader" class="icon-loading" ng-show="isContactsLoading">
		</div>
		<ul class="contact-list" ng-show="!isContactsLoading">
			<li ng-repeat="contact in contacts | orderBy:vsettings.contactOrderBy:vsettings.reverseContactOrder" peer-label="{{ contact.label }}" ng-click="loadConversation(contact);" href="#">
				<img class="ocsms-plavatar" ng-src="{{ contact.avatar }}" ng-show="contact.avatar !== undefined" />
				<div class="ocsms-plavatar" ng-show="contact.avatar === undefined" ng-style="{'background-color': (contact.uid | peerColor)}">{{ contact.label | firstCharacter }}</div>
				<a class="ocsms-plname" style="{{ contact.unread > 0 ? 'font-weight:bold;' : ''}}" mailbox-label="{{ contact.label }}" mailbox-navigation="{{ contact.nav }}">{{ contact.label }}{{ contact.unread > 0 ? ' (' + contact.unread + ') ' : '' }}</a>
			</li>
		</ul>
	</div>
	<div id="app-settings" class="ng-scope">
		<div id="app-settings-header">
			<button name="app settings" class="settings-button" data-apps-slide-toggle="#app-settings-content">
				<?php p($l->t('Settings'));?>
			</button>
		</div>
		<div id="app-settings-content">
			<div><label for="setting_msg_per_page">Max messages on tab loading</label>
				<input type="number" min="10" max="10000" name="setting_msg_per_page" ng-model="vsettings.messageLimit" ng-change="vsettings.sendMessageLimit()" to-int />
				<span class="label-invalid-input" ng-if="vsettings.messageLimit == null || vsettings.messageLimit == undefined">Invalid message limit</span>
			</div>

			<div><label for="intl_phone">Country code</label>
				<select name="intl_phone" id="sel_intl_phone">
				<?php foreach (CountryCodes::$codes as $code => $cval) { ?>
					<option><?php p($code); ?></option>
				<?php } ?>
				</select>
				<button class="new-button primary icon-checkmark-white" ng-click="vsettings.sendCountry();"></button>
			</div>

			<div>
				<label for="setting_contact_order">Contact ordering</label>
				<select name="setting_contact_order" ng-model="vsettings.contactOrderBy" ng-change="vsettings.sendContactOrder()">
					<option value="lastmsg">Last message</option>
					<option value="label">Label</option>
				</select>
				<label for "setting_contact_order_reverse">Reverse ?</label>
				<input type="checkbox" ng-model="vsettings.reverseContactOrder" ng-change="vsettings.sendContactOrder()" />
			</div>

			<div>
				<label for"setting_notif">Notification settings</label>
				<select name="setting_notif" ng-model="vsetting.enableNotifications" ng-change="vsettings.sendNotificationFlag()">
					<option value="1">Enable</option>
					<option value="0">Disable</option>
				</select>
			</div>
		</div> <!-- app-settings-content -->
	</div>

	<div id="ocsms-app-content">
		<div id="app-content-loader" class="icon-loading" ng-show="isConvLoading">
		</div>
		<div id="app-content-header" ng-show="!isConvLoading && selectedContact.label !== undefined && selectedContact.label !== ''"
			 ng-style="{'background-color': (selectedContact.uid | peerColor)}">
			<div id="ocsms-contact-avatar">
				<img class="ocsms-plavatar-big" ng-src="{{ selectedContact.avatar }}"
					 ng-show="selectedContact.avatar !== undefined" />
			</div>
			<div id="ocsms-contact-details">
				<div id="ocsms-phone-label">{{ selectedContact.label }} </div>
				<div id="ocsms-phone-opt-number">{{ selectedContact.opt_numbers }}</div>
				<div id="ocsms-phone-msg-nb">{{ messages.length }} message(s) shown. {{ totalMessageCount }} message(s) stored in database.</div>
			</div>
			<div id="ocsms-contact-actions">
				<div id="ocsms-conversation-removal" class="icon-delete icon-delete-white svn delete action" ng-click="removeConversation();"></div>
			</div>

		</div>
		<div id="app-content-wrapper" ng-show="!isConvLoading">
			<div ng-show="messages.length == 0" id="ocsms-empty-conversation">Please choose a conversation on the left menu</div>
			<div ng-show="messages.length > 0" class="ocsms-messages-container">
				<div ng-repeat="message in messages | orderBy:'date'">
					<div class="msg-{{ message.type }}">
						<div ng-bind-html="message.content"></div>
						<div style="display: block;" id="ocsms-message-removal" class="icon-delete svn delete action" ng-click="removeConversationMessage(message.id);"></div>
						<div class="msg-date">{{ message.date | date:'medium' }}</div>
					</div>
					<div class="msg-spacer"></div>
				</div>
				<div id="searchresults"></div>
			</div>
		</div>
	</div>
</div>
