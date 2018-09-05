<?php
use \OCA\OcSms\Lib\CountryCodes;

\OCP\Util::addScript('ocsms', 'angular.min');
\OCP\Util::addScript('ocsms', 'vue.min');
// Production
//\OCP\Util::addScript('ocsms', 'app.min');
// Develop
\OCP\Util::addScript('ocsms', 'devel/app');
\OCP\Util::addScript('ocsms', 'devel/helpers');
\OCP\Util::addScript('ocsms', 'devel/legacy');
\OCP\Util::addScript('ocsms', 'devel/notifications');
\OCP\Util::addScript('ocsms', 'devel/settings');
\OCP\Util::addStyle('ocsms', 'style');
?>

<div class="ng-scope" id="app" ng-app="OcSms" ng-controller="OcSmsController" xmlns:v-on="http://www.w3.org/1999/xhtml">
	<div id="app-mailbox-peers">
		<div id="app-contacts-loader" class="icon-loading" ng-show="isContactsLoading">
		</div>
		<ul class="ng-cloak contact-list" ng-show="!isContactsLoading">
			<li ng-repeat="contact in contacts | orderBy:getContactOrderBy:getReverseContactOrder" peer-label="{{ contact.label }}" ng-click="loadConversation(contact);" href="#">
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
			<div><label for="setting_msg_per_page">Max messages to load per conversation</label>
				<input type="number" min="10" max="10000" name="setting_msg_per_page" v-model="messageLimit" v-on:change="sendMessageLimit()" to-int />
				<span class="label-invalid-input" v-if="messageLimit == null || messageLimit == undefined"><?php p($l->t('Invalid message limit'));?></span>
			</div>

			<div><label for="intl_phone"><?php p($l->t('Default country code'));?></label>
				<select name="intl_phone" id="sel_intl_phone" v-model="country">
				<?php foreach (CountryCodes::$codes as $code => $cval) { ?>
					<option><?php p($l->t($code)); ?></option>
				<?php } ?>
				</select>
				<button class="new-button primary icon-checkmark-white" v-on:click="sendCountry();"></button>
			</div>

			<div>
				<label for="setting_contact_order"><?php p($l->t('Contact ordering'));?></label>
				<select name="setting_contact_order" v-model="contactOrderBy" v-on:change="sendContactOrder()">
					<option value="lastmsg"><?php p($l->t('Last message'));?></option>
					<option value="label"><?php p($l->t('Label'));?></option>
				</select>
				<label for="setting_contact_order_reverse"><?php p($l->t('Reverse ?'));?></label>
				<input type="checkbox" v-model="reverseContactOrder" v-on:change="sendContactOrder()" />
			</div>

			<div>
				<label for="setting_notif"><?php p($l->t('Notification settings'));?></label>
				<select name="setting_notif" v-model="enableNotifications" v-on:change="sendNotificationFlag()">
					<option value="1"><?php p($l->t('Enable'));?></option>
					<option value="0"><?php p($l->t('Disable'));?></option>
				</select>
			</div>
		</div> <!-- app-settings-content -->
	</div>

	<div id="ocsms-app-content">
		<div id="app-content-loader" class="ng-cloak icon-loading" ng-show="isConvLoading">
		</div>
		<div id="app-content-header" class="ng-cloak" ng-show="!isConvLoading && selectedContact.label !== undefined && selectedContact.label !== ''"
			 ng-style="{'background-color': (selectedContact.uid | peerColor)}">
			<div id="ocsms-contact-avatar">
				<img class="ocsms-plavatar-big" ng-show="selectedContact.avatar !== undefined" ng-src="{{ selectedContact.avatar }}" />
				<div class="ocsms-plavatar-big" ng-show="selectedContact.avatar === undefined">{{ selectedContact.label | firstCharacter }}</div>
			</div>
			<div id="ocsms-contact-details">
				<div id="ocsms-phone-label">{{ selectedContact.label }} </div>
				<div id="ocsms-phone-opt-number">{{ selectedContact.opt_numbers }}</div>
				<div id="ocsms-phone-msg-nb"><?php p($l->t('%s message(s) shown of %s message(s) stored in database.', array( '{{ messages.length }}', '{{ totalMessageCount }}')));?></div>
			</div>
			<div id="ocsms-contact-actions">
				<div id="ocsms-conversation-removal" class="icon-delete icon-delete-white svn delete action" ng-click="removeConversation();"></div>
			</div>

		</div>
		<div id="app-content-wrapper" ng-show="!isConvLoading">
			<div ng-show="messages.length == 0" id="ocsms-empty-conversation"><?php p($l->t('Please select a conversation from the list to load it.'));?></div>
			<div ng-show="messages.length > 0" class="ng-cloak ocsms-messages-container">
				<div ng-repeat="message in messages | orderBy:'date'">
					<div class="msg-{{ message.type }}">
						<div>{{ message.content }}</div>
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
