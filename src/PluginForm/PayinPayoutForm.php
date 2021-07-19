<?php

namespace Drupal\commerce_payin_payout\PluginForm;

use Drupal\commerce_payin_payout\Utility\PayinPayoutHelper;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides offsite-redirect form for PayinPayout payment gateway.
 */
class PayinPayoutForm extends BasePaymentOffsiteForm {

  /**
   * The live API url to process payment.
   */
  private const API_URL_LIVE = 'https://lk.payin-payout.net/api/shop';

  /**
   * The test API url to process payment.
   */
  private const API_URL_TEST = 'https://dev1.payin-payout.net';

  /**
   * The Payin-Payout helper.
   *
   * @var \Drupal\commerce_payin_payout\Utility\PayinPayoutHelper
   */
  private $payinPayoutHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->payinPayoutHelper = new PayinPayoutHelper();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Set API url for selected payment mode.
    switch ($this->plugin->getMode()) {
      case 'test':
        $api_url = self::API_URL_TEST;
        break;

      case 'live':
        $api_url = self::API_URL_LIVE;
        break;
    }

    // Get plugin configuration parameters.
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $plugin_configuration = $payment_gateway_plugin->getConfiguration();

    $api_token = $plugin_configuration['api_token'];
    $agent_id = $plugin_configuration['agent_id'];
    $agent_name = $plugin_configuration['agent_name'];
    $order_id_prefix = $plugin_configuration['order_id_prefix'];

    // Get order parameters.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity->getOrder();
    $order_id = $order->id();
    $order_email = $order->getEmail();
    $goods = $order_id_prefix . $order_id;
    $amount = $order->getTotalPrice()->getNumber();
    $currency = $this->payinPayoutHelper->formatCurrency($order->getTotalPrice()->getCurrencyCode());

    $phone_field_name = $plugin_configuration['customer_phone_field_name'];
    $phone_number = $order->getBillingProfile()->$phone_field_name->value;
    $phone_number_formatted = $this->payinPayoutHelper->formatPhoneNumber($phone_number);

    // Generate payment sign.
    $agent_time = date('G:i:s d.m.Y');
    $payment_sign = $this->payinPayoutHelper->generateSign(
      [$agent_id, $order_id, $agent_time, $amount, $phone_number_formatted],
      $api_token
    );

    // Prepare payment data.
    $payment_data = [
      'agentId' => $agent_id,
      'agentName' => $agent_name,
      'orderId' => $order_id,
      'amount' => $amount,
      'email' => $order_email,
      'phone' => $phone_number_formatted,
      'agentTime' => $agent_time,
      'goods' => $goods,
      'currency' => $currency,
      'successUrl' => $form['#return_url'],
      'failUrl' => $form['#cancel_url'],
      'sign' => $payment_sign,
    ];

    return $this->buildRedirectForm($form, $form_state, $api_url, $payment_data, self::REDIRECT_POST);
  }

}
