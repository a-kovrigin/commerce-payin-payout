<?php

namespace Drupal\commerce_payin_payout\Utility;

/**
 * Provides helper methods for Payin-Payout payment processing.
 */
class PayinPayoutHelper {

  /**
   * Format currency code.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @return string
   *   The formatted currency code.
   *
   * @see https://github.com/payin-payout/payin-api#%D0%BF%D1%80%D0%B8%D0%BB%D0%BE%D0%B6%D0%B5%D0%BD%D0%B8%D0%B5-2
   */
  public function formatCurrency(string $currency_code) {
    $currencies_map = [
      'RUB' => 'RUR',
      'RUR' => 'RUB',
    ];

    if (array_key_exists($currency_code, $currencies_map)) {
      return $currencies_map[$currency_code];
    }

    return $currency_code;
  }

  /**
   * Format phone number.
   *
   * @param string $phone_number
   *   The phone number.
   *
   * @return string
   *   The formatted phone number.
   */
  public function formatPhoneNumber(string $phone_number) {
    return '+' . preg_replace('/[^0-9.]+/', '', $phone_number);
  }

  /**
   * Generate sign for payment validation.
   *
   * Using md5() for hashing signs is a requirement of the payment gateway.
   *
   * @param array $data
   *   The array of data to implode.
   * @param string $api_token
   *   The API token.
   *
   * @return string
   *   The payment sign.
   *
   * @see https://github.com/payin-payout/payin-api/blob/master/Token.md#%D0%BF%D1%80%D0%B8-%D0%BE%D0%BF%D0%BB%D0%B0%D1%82%D0%B5-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0-%D1%82%D0%BE%D0%BA%D0%B5%D0%BD%D0%BE%D0%BC
   */
  public function generateSign(array $data, string $api_token) {
    $data_string = implode('#', $data);

    return md5($data_string . '#' . md5($api_token));
  }

}
