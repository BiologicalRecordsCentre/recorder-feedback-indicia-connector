<?php

namespace Drupal\recorder_feedback_indicia_connector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A utility class for accessing the external Recorder Feedback API.
 */
class RecorderFeedbackUtils implements ContainerInjectionInterface {

  /**
   * Config factory services.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Dependency inject services.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
    );
  }

  /**
   * Constructor for dependency injection.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactory $loggerFactory, MessengerInterface $messenger) {
    $this->configFactory = $configFactory;
    $this->logger = $loggerFactory->get('recorder_feedback_indicia_connector');
    $this->messenger = $messenger;
  }

  /**
   * Returns true if the configuration has been filled in.
   *
   * @return bool
   *   True if configuration completed.
   */
  public function validateConfig() {
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $systemPrefix = $config->get('system_prefix');
    $serviceUrl = $config->get('api_url');
    $apiKey = $config->get('api_key');
    return !empty($systemPrefix) && !empty($serviceUrl) && !empty($apiKey);
  }

  /**
   * Retrieve available lists to subscribe to.
   *
   * Includes subscription status if the user is valid and signed up.
   *
   * @param \Drupal\user\UserInterface|null $account
   *   Drupal user account.
   *
   * @return array
   *   Array of list objects.
   */
  public function getLists($account) {
    if ($account && $account->field_rec_feedback_registered->value) {
      $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
      $systemPrefix = $config->get('system_prefix');
      $indiciaUserId = $account->field_indicia_user_id->value;
      // An endpoint to get lists including user subscribed info..
      $resource = "users/$systemPrefix:$indiciaUserId/subscriptions";
    }
    else {
      // A general GET lists endpoint.
      $resource = "lists";
    }
    $r = $this->curlRequest($resource);
    return $r ? $r->lists : [];
  }

  /**
   * Register a new user on the external service.
   *
   * Called when the user first ticks a subscription checkbox to choose at
   * least one list.
   *
   * @param \Drupal\user\UserInterface $account
   *   Drupal user account.
   *
   * @return bool
   *   True if successful.
   */
  public function registerUser(UserInterface $account) {
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $systemPrefix = $config->get('system_prefix');
    $payload = [
      'external_key' => "$systemPrefix:" . $account->field_indicia_user_id->value,
      'name' => $account->field_first_name->value . ' ' . $account->field_last_name->value,
      'email' => $account->getEmail(),
    ];
    $r = $this->curlRequest('users', $payload);
    return $this->checkResponse($r, 'User added successfully');
  }

  /**
   * Update details of an already registered user.
   *
   * @param \Drupal\user\UserInterface $account
   *   Drupal user account.
   * @param mixed $firstName
   *   Updated user first name.
   * @param mixed $lastName
   *   Updated user last name.
   * @param mixed $email
   *   Updated user email.
   *
   * @return bool
   *   True if successful.
   */
  public function updateUser(UserInterface $account, $firstName, $lastName, $email) {
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $systemPrefix = $config->get('system_prefix');
    $indiciaUserId = $account->field_indicia_user_id->value;
    $payload = [
      'external_key' => "$systemPrefix:$indiciaUserId",
      'name' => "$firstName $lastName",
      'email' => $email,
    ];
    $r = $this->curlRequest("users/$indiciaUserId", $payload, 'PUT');
    return $this->checkResponse($r, 'User updated successfully');
  }

  /**
   * Subscribes a user to a list.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account.
   * @param int $listId
   *   ID of the list.
   *
   * @return bool
   *   True if successful.
   */
  public function subscribe(UserInterface $account, $listId) {
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $systemPrefix = $config->get('system_prefix');
    $indiciaUserId = $account->field_indicia_user_id->value;
    $payload = [
      'list_id' => $listId,
    ];
    $r = $this->curlRequest("users/$systemPrefix:$indiciaUserId/subscriptions", $payload);
    return $this->checkResponse($r, 'Subscription added successfully');
  }

  /**
   * Unsubscribes a user from a list they previuosly subscribed to.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account.
   * @param int $listId
   *   ID of the list.
   */
  public function unsubscribe(UserInterface $account, $listId) {
    $indiciaUserId = $account->field_indicia_user_id->value;
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $systemPrefix = $config->get('system_prefix');
    $r = $this->curlRequest("users/$systemPrefix:$indiciaUserId/subscriptions/$listId", NULL, 'DELETE');
    return $this->checkResponse($r, 'Subscription removed successfully');
  }

  /**
   * Checks the external service response against an expected message.
   *
   * @param mixed $r
   *   Service response.
   * @param mixed $expectedMessage
   *   Expected success message.
   *
   * @return bool
   *   True if the response returned the expected success message.
   */
  private function checkResponse($r, $expectedMessage) {
    if (empty($r->message) || $r->message !== $expectedMessage) {
      $this->logger->error('Response to Recorder Feedback request not as expected: ' . $r->message . ' :: ' . $expectedMessage);
      $this->messenger->addError(var_export($r, TRUE));
      $this->messenger->addError('Request to external Recorder Feedback service failed. More information is available in the Drupal logs.');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * A generic function for sending requests to the external service.
   *
   * @param string $resource
   *   Resource to access in the external service API, e.g. 'users'.
   * @param array $payload
   *   Payload to send if POST or PUT.
   * @param string $method
   *   If a method other than GET or POST required, specify it here, e.g.
   *   'DELETE' or 'PUT'.
   *
   * @return mixed
   *   Decoded response object or NULL if an error occurs.
   */
  private function curlRequest($resource, $payload = NULL, $method = NULL) {
    $config = $this->configFactory->get('recorder_feedback_indicia_connector.settings');
    $serviceUrl = $config->get('api_url');
    $apiKey = $config->get('api_key');
    $url = "$serviceUrl/api/$resource";
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      "Authorization: Bearer $apiKey",
      'Content-type: application/json',
    ]);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    if ($payload) {
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    if ($method) {
      curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method);
    }
    $r = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    // Check for an error, or check if the http response was not OK.
    if ($curlErrno || ($httpCode != 200 && $httpCode != 201)) {
      $this->logger->error('cUrl request to service failed: ' . $url);
      $this->logger->alert("Request Payload: " . var_export($payload, TRUE));
      $this->logger->alert("Request method: $method");
      $this->logger->error('Response: ' . $r);
      if ($curlErrno) {
        $this->logger->error("cUrl error: $curlErrno - " . curl_error($session));
      }
      if ($httpCode != 200) {
        $this->logger->error("Status: $httpCode");
      }
      $this->logger->error('Stack: ' . var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), TRUE));
      return NULL;
    }
    else {
      $this->logger->info("Successful request: $url");
      $this->logger->info("Request Payload: " . var_export($payload, TRUE));
      $this->logger->info("Request method: $method");
    }
    curl_close($session);
    return json_decode($r);
  }

}
