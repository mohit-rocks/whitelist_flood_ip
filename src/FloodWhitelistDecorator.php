<?php

namespace Drupal\whitelist_flood_ip;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\IpValidator;

/**
 * Class FloodWhitelistDecorator.
 */
class FloodWhitelistDecorator implements FloodInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * Drupal\Core\Flood\FloodInterface definition.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $original_flood_service;

  /**
   * Constructs a new FloodWhitelistDecorator object.
   */
  public function __construct(Connection $database, RequestStack $request_stack, ConfigFactoryInterface $config_factory, FloodInterface $flood) {
    $this->database = $database;
    $this->requestStack = $request_stack;
    $this->config_factory = $config_factory;
    $this->original_flood_service = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $whitelisted = $this->checkWhitelistedIp($name, $identifier);
    if (!$whitelisted) {
      $this->original_flood_service->register($name, $window, $identifier);
    }
  }

  /**
   * Check if IP address is whitelisted or not.
   *
   * @param string $identifier
   *   IP address.
   *
   * @return bool
   *   TRUE if ip address is whitelisted, FALSE otherwise.
   */
  public function checkWhitelistedIp($name, $identifier) {
    if ($name !== 'user.failed_login_ip') {
      return FALSE;
    }
    $ip_address = '';
    if (filter_var($identifier, FILTER_VALIDATE_IP)) {
      $ip_address = $identifier;
    }
    $whitelisted_ips = $this->config_factory->get('whitelist_flood_ip.whitelistip')->get('ip_addresses');
    $whitelisted_ips = explode("\r\n", $whitelisted_ips);
    if (in_array($ip_address, $whitelisted_ips)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    $this->original_flood_service->clear($name, $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    if ($name !== 'user.failed_login_ip') {
      return $this->original_flood_service->isAllowed($name, $threshold, $window, $identifier);
    }
    $whitelisted = $this->checkWhitelistedIp($name, $identifier);
    if ($whitelisted) {
      return TRUE;
    }
    return $this->original_flood_service->isAllowed($name, $threshold, $window, $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->original_flood_service->garbageCollection();
  }

}
