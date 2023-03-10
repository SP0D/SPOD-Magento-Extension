# Magento 2 Spod Sync module

 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)

## Installation

Register or login on https://login.spod.com/register, click on ‘Connect Integrations’, select Magento and click on Connect.

Open your terminal and navigate to the Magento 2 root directory (if you are using hosting platforms, use SSH to open terminal). Run the following commands to set up the SPOD extension:

`composer require spod/module-sync` (you need to install Composer which is dependency manager for php on your machine)

`bin/magento module:enable Spod_Sync`

`bin/magento setup:upgrade`

In your Magento menu you should now see SPOD. Click on Status and add the API key that you created earlier on https://app.spod.com/

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
* decide wether to use the staging environment (SPOD > Configuration > Enable Staging > Yes) in case you want to do some testing beforehand on https://app.spod-staging.com/ and with testing payment methods taken from https://docs.adyen.com/development-resources/testing/test-card-numbers
* enable debug logging, if required






