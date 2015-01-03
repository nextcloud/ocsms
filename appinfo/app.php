<?php
/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2015
 */

namespace OCA\OcSms\AppInfo;


\OCP\App::addNavigationEntry(array(
    // the string under which your app will be referenced in owncloud
    'id' => 'ocsms',

    // sorting weight for the navigation. The higher the number, the higher
    // will it be listed in the navigation
    'order' => 10,

    // the route that will be shown on startup
    'href' => \OCP\Util::linkToRoute('ocsms.sms.index'),

    // the icon that will be shown in the navigation
    // this file needs to exist in img/
    'icon' => \OCP\Util::imagePath('ocsms', 'app.svg'),

    // the title of your application. This will be used in the
    // navigation or on the settings page of your app
    'name' => \OCP\Util::getL10N('ocsms')->t('SMS')
));
