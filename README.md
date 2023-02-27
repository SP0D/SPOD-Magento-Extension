# Magento 2 Spod Sync module

 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)

## Installation

Navigate to Magento 2 root directory. Run the following commands:

`composer require spod/module-sync`

`bin/magento module:enable Spod_Sync`

`bin/magento setup:upgrade`

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






