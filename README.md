# Phone Sync (for NextCloud & ownCloud)

Phone Sync provides a webinterface to display your SMS conversations. SMS conversations are pushed by your Android devices using the [Android client](https://github.com/nerzhul/ownCloud-SMS-App), available on [Google Play Store](https://play.google.com/store/apps/details?id=fr.unix_experience.owncloud_sms).

## :arrow_forward: Access

The app is available in both, [Nextcloud appstore](https://apps.nextcloud.com/apps/ocsms) and [ownCloud appstore](https://apps.owncloud.com/content/show.php/ownCloud+SMS?content=167289). So installing is as easy as

1. Navigate in your Nextcloud / ownCloud instance to the "apps"
2. Enable "experimental apps" in the settings
3. Select the category "Multimedia"
4. Click "activate"

## :question: Solve encoding errors on MySQL
If you are on MySQL or MariaDB and have issues with the database, the cause may be that you have to enable 4-byte support. Here is a guide: https://docs.nextcloud.com/server/11/admin_manual/maintenance/mysql_4byte_support.html

## :eyes: Screenshot

![Screenshot](https://raw.githubusercontent.com/nextcloud/ocsms/master/appinfo/screenshots/1.png)

## :link: Requirements
- A [Nextcloud](https://nextcloud.com) or [ownCloud](https://owncloud.com) instance
- An Android phone

## :exclamation: Reporting issues

- **Server:** https://github.com/nextcloud/ocsms/issues
- **Client:** https://github.com/nerzhul/ownCloud-SMS-App/issues

## :notebook: License
Phone Sync web application is currently licensed under [AGPL license](https://github.com/nextcloud/ocsms/blob/master/LICENSE.md).
