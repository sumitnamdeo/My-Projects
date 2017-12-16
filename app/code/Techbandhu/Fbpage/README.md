## Magento 2 - Facebook Page Plugin
Magento 2 Facebook page plugin will simply let you add your facebook page to your website with all available options.

###1 - Installation Facebook Page Plugin
##### Manual Installation
Facebook Page Plugin Extenison for Magento2
 * Download the extension
 * Unzip the file
 * Create a folder {Magento root}/app/code/Techbandhu/Fbpage
 * Copy the content from the unzip folder


##### Using Composer

Install with Composer!
```
composer require techbandhu/fbpage:1.0.*
```
Or you can use
```
composer config repositories.techbandhu-fbpage git git@github.com:techbandhu/fbpage.git
composer require techbandhu/fbpage
```

###2 -  Enable Facebook Page Plugin
 * php -f bin/magento module:enable --clear-static-content Techbandhu_Fbpage
 * php -f bin/magento setup:upgrade

###3 - Config Facebook Page Plugin
Log into your Magetno Admin, then goto Stores -> Configuration -> Techbandhu -> Facebook Page Plugin
Enter your facebook page URL and configure as require to show page plugin with your website.
