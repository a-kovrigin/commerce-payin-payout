<?php

namespace Drupal\commerce_payin_payout\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payin_payout\Utility\PayinPayoutHelper;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Payin-Payout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payin_payout",
 *   label = @Translation("Payin-Payout"),
 *   display_label = @Translation("Payin-Payout"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payin_payout\PluginForm\PayinPayoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa"
 *   },
 * )
 */
class PayinPayout extends OffsitePaymentGatewayBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * The Payin-Payout helper.
   *
   * @var \Drupal\commerce_payin_payout\Utility\PayinPayoutHelper
   */
  private $payinPayoutHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->logger = $container->get('logger.factory')->get('commerce_payin_payout');
    $instance->payinPayoutHelper = new PayinPayoutHelper();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_token' => '',
      'agent_id' => '',
      'agent_name' => '',
      'order_id_prefix' => '',
      'customer_phone_field_name' => '',
      'api_logging' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#description' => $this->t('The Payin-Payout API token.'),
      '#default_value' => $this->configuration['api_token'],
      '#required' => TRUE,
    ];

    $form['agent_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agent ID'),
      '#description' => $this->t('The store id provided by the Payin-Payout service.'),
      '#default_value' => $this->configuration['agent_id'],
      '#required' => TRUE,
    ];

    $form['agent_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agent name'),
      '#description' => $this->t('The store name displayed to the user on payment form.'),
      '#default_value' => $this->configuration['agent_name'],
      '#required' => TRUE,
    ];

    $form['order_id_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID prefix'),
      '#description' => $this->t('The order id prefix (e.g. "Order #" prefix for order id 42 will result "Order #42").'),
      '#default_value' => $this->configuration['order_id_prefix'],
    ];

    $form['customer_phone_field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer phone field'),
      '#options' => $this->getCustomerFieldOptions(),
      '#description' => $this->t('The customer profile field storing phone number value.'),
      '#default_value' => $this->configuration['customer_phone_field_name'],
      '#required' => TRUE,
    ];

    $form['api_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('API logging'),
      '#description' => $this->t('The API requests data will be logged if checked.'),
      '#default_value' => $this->configuration['api_logging'],
    ];

    return $form;
  }

  /**
   * Get customer profile fields.
   *
   * @return array
   *   The array of customer profile fields.
   */
  private function getCustomerFieldOptions() {
    $customer_fields = [];

    $bundle_fields = $this->entityFieldManager->getFieldDefinitions('profile', 'customer');

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($bundle_fields as $key => $field) {
      if (!$field instanceof FieldConfig) {
        continue;
      }

      $customer_fields[$field->getName()] = $field->label();
    }

    return $customer_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['api_token'] = $values['api_token'];
    $this->configuration['agent_id'] = $values['agent_id'];
    $this->configuration['agent_name'] = $values['agent_name'];
    $this->configuration['order_id_prefix'] = $values['order_id_prefix'];
    $this->configuration['customer_phone_field_name'] = $values['customer_phone_field_name'];
    $this->configuration['api_logging'] = $values['api_logging'];
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $plugin_configuration = $this->getConfiguration();

    // Log the request data if enabled.
    if ($plugin_configuration['api_logging']) {
      $this->logger
        ->debug('Payin-Payout request: <pre>@body</pre>', [
          '@body' => var_export($request->request->all(), TRUE),
        ]);
    }

    // Validate request.
    if (!$this->validateRequest($request)) {
      return NULL;
    }

    // Set payment state.
    // @see https://github.com/payin-payout/payin-api#%D1%84%D0%BE%D1%80%D0%BC%D0%B0-%D0%BF%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B8-%D1%81%D1%82%D0%B0%D1%82%D1%83%D1%81%D0%B0-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0
    $payment_status = $request->request->get('paymentStatus');

    if ($payment_status === '2') {
      $message = $this->t('Payment #@payment_id failed.', [
        '@payment_id' => $request->request->get('outputId'),
      ]);

      $this->logger->warning($message);

      return NULL;
    }

    $payment_state = 'completed';

    // Get order by orderId request param.
    $order_id = $request->request->get('orderId');
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $order_storage->load($order_id);

    if (is_null($order)) {
      return NULL;
    }

    // Validate payment sign.
    $validation_sign = $this->payinPayoutHelper->generateSign(
      [$request->request->get('agentId'),
        $request->request->get('orderId'),
        $request->request->get('paymentId'),
        $request->request->get('amount'),
        $request->request->get('phone'),
        $request->request->get('paymentStatus'),
        $request->request->get('paymentDate'),
      ],
      $plugin_configuration['api_token']
    );

    $request_sign = $request->request->get('sign');

    if ($request_sign !== $validation_sign) {
      return NULL;
    }

    // Create payment entity.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payment_storage->create([
      'type' => 'payment_default',
      'payment_gateway' => $this->pluginId,
      'remote_id' => $request->request->get('outputId'),
      'remote_state' => $request->request->get('paymentStatus'),
      'order_id' => $order_id,
      'amount' => new Price($request->request->get('amount'), $this->payinPayoutHelper->formatCurrency($request->request->get('currency'))),
      'state' => $payment_state,
      'payment_gateway_mode' => $this->getMode(),
    ]);
    $payment->save();

    // Return payment successfully processed XML response.
    // This tells Payin-Payout to stop sending requests about the payment.
    // @see https://github.com/payin-payout/payin-api#%D0%BF%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0-%D0%B8%D0%BD%D1%84%D0%BE%D1%80%D0%BC%D0%B0%D1%86%D0%B8%D0%B8-%D0%BE-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B5
    return new Response('<?xml version="1.0" encoding="UTF-8"?><response><result>0</result></response>', 200, [
      'Content-Type' => 'text/xml',
    ]);
  }

  /**
   * Validate request to check if it has all necessary params.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Payin-Payout service request.
   *
   * @return bool
   *   Whether request is valid.
   */
  private function validateRequest(Request $request) {
    $request_keys = [
      'orderId',
      'agentId',
      'amount',
      'paymentId',
      'paymentStatus',
      'paymentDate',
      'outputId',
      'phone',
      'sign',
      'currency',
    ];

    foreach ($request_keys as $request_key) {
      if (!$request->request->has($request_key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
