1.5.0
* You can now delete conversation or single messages (only in owncloud, not on your phone via the app)
* Fix a scrolling issue (thanks @animalillo)
* Fix duplicate numbers in conversation in some cases
* Update AngularJS to 1.3
* Rewrite all JS code to use AngularJS

1.4.5
* Fix a MySQL issue with some key length
* Fix a mischecked variable in sync process which could block the sync process

1.4.4
* Add more european country codes
* Code refactoring to respect owncloud app style
* Minor performance improvements

1.4.3
* Add south Africa country code

1.4.2
* Fix appframework check issue
* Fix angular.js library

1.4.0
* Use contact avatars into the conversation list
* Add a user setting to set your country. It permit to deduplicate local/international prefixes and prevent splitted conversations. (Tell us if your country doesn't appear, we will map it, we refer to Google Play Store stats for generate the basic mapping)
* Add angular.js support into template
* Re-organize JS sources

1.3.3
* Fix JS code for HTML5 notifications on browsers which doesn't support it (like IE)

1.3.2
* Fix an integer overflow on 32bits systems which block the sync process

1.3.1
* Fix a CSRF issue when phone push datas
