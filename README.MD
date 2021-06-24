# Commerce Payin-Payout

This module integrates [Payin-Payout](https://payin-payout.net/) with Drupal Commerce 2 providing payment gateway.

## Installation

1. Install module with composer `composer require drupal/commerce_payin_payout`.
2. Enable module as usual.
3. Navigate to Store > Configuration > Payments > Payment Gateways.
4. Add new payment gateway and select Payin-Payout plugin.
5. Select payment mode, add API Token and other necessary information.
7. Save payment gateway.

## Features
Since Payin-Payout requires customer phone number to process payment, you will need to create field to store
phone numbers (/admin/config/people/profile-types/manage/customer) and provide field machine name on plugin
configuration form.

## Documentation

- [Payin-Payout API](https://github.com/payin-payout/payin-api)
