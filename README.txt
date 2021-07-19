README file for Commerce Payin-Payout

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* How It Works
* Troubleshooting
* Maintainers

INTRODUCTION
------------
This module integrates Payin-Payout (https://payin-payout.net/) with Drupal Commerce 2 providing payment gateway.
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_payin_payout
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/commerce_payin_payout


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core
  - Commerce Payment (and its dependencies)


INSTALLATION
------------
* This module can to be installed via Composer or manually.
composer require "drupal/commerce_payin_payout"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies

CONFIGURATION
-------------
* Create a new Payin-Payout payment gateway.
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Select payment mode, add API token and other necessary information.


HOW IT WORKS
------------

* General considerations:
  - The store owner must have a Payin-Payout store account.
    Sign up here:
    https://payin-payout.net/


TROUBLESHOOTING
---------------
* No troubleshooting pending for now.


MAINTAINERS
-----------
Current maintainers:
* Alexander Kovrigin (a.kovrigin) - https://www.drupal.org/u/akovrigin
