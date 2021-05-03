# Mage2 Module Spod Sync

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Updates](#markdown-header-update)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
SPOD Magento 2 Sync

## Installation

### Type 1: Zip file

 - Unzip the zip file and upload contents to <br> `app/code/Spod/Sync`
 - Enable the module by running  <br>`php bin/magento module:enable Spod_Sync`
 - Apply database updates by running  <br>`php bin/magento setup:upgrade`
 - Flush the cache by running <br> `php bin/magento cache:flush`

Hint: due to technical limitations, the Uninstall routine is only available for composer installations.

### Type 2: Composer

 - Add the composer repository to the configuration by running<br>
   `composer config repositories.spod vcs git@github.com:SP0D/SPOD-Magento-Extension.git`
 - Install the module composer by running <br>`composer require spod/module-sync`
 - enable the module by running <br>`php bin/magento module:enable Spod_Sync`
 - apply database updates by running <br>`php bin/magento setup:upgrade`

If you run into problems, especially in live environments, also try:
 - update the dependency injection <br>`php bin/magento setup:di:compile`
 - update the dependency injection <br>`php bin/magento setup:static-content:deploy`
 - Flush the cache by running <br>`php bin/magento cache:flush`

## Update

 - Update the extension by running <br>`composer update`
 - Apply database changes <br>`php bin/magento setup:upgrade`
 - Update the dependency injection <br>`php bin/magento setup:di:compile`
 - Flush the cache by running <br>`php bin/magento cache:flush`


## Configuration

You have to at least
* configure the Magento 2 general store information (General / General / Store Information)
* store informations used are:
  * Store Name
  * Phone Number
  * Country
  * Region (depends on country selection)
  * ZIP
  * City
  * Street Address

In addition, you have to configure basic parameters in the configuration area of the
Spod_Sync menu entry (SPOD / Configuration):

* Add first- and lastname for shipping from addresses, which is required by the SPOD API
* Decide if there is a shipping method you offer in your store, which has to be mapped to the PREMIUM and/or EXPRESS shipping types of SPOD.

Optional parameters:
* decide wether to use the staging environment
* enable debug logging, if required






