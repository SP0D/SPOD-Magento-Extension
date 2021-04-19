# Mage2 Module Spod Sync

    ``spod/module-sync``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
SPOD Magento 2 Sync

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file and upload contents to `app/code/Spod/Sync`
 - Enable the module by running `php bin/magento module:enable Spod_Sync`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require spod/module-sync`
 - enable the module by running `php bin/magento module:enable Spod_Sync`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

You have to at least
* configure the Magento 2 general store information (General / General / Store Information) 

In addition, you have to configure basic parameters in the configuration area of the
Spod_Sync menu entry (SPOD / Configuration):

* Add first- and lastname for shipping from addresses, which is required by the SPOD API
* Decide if there is a shipping method you offer in your store, which has to be mapped to the PREMIUM and/or EXPRESS shipping types of SPOD.

Optional parameters:
* decide wether to use the staging environment
* enable debug logging, if required






