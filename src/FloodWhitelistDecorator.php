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
    $whitelisted = $this->checkWhitelistedIp($identifier);
    if (!$whitelisted) {
      $this->original_flood_service->register($name, $window, $identifier);
    }
  }

  /**
   * Check if IP address is whitelisted or not.
   *
   * @param string $identifier
   *   IP address.
   */
  public function checkWhitelistedIp($identifier) {
    if (filter_var($identifier, FILTER_VALIDATE_IP)) {
      $ip_address = $identifier;
    }
    else {
      list($uid, $ip_address) = explode('-', $identifier);
    }
    $whitelisted_ips = $this->config_factory->get('whitelist_flood_ip.whitelistip')->get('ip_addresses');
    $whitelisted_ips = explode("\r\n", $whitelisted_ips);
    if (!in_array($ip_address, $whitelisted_ips)) {
      return FALSE;
    }
    return TRUE;
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
    $whitelisted = $this->checkWhitelistedIp($identifier);
    if (!$whitelisted) {
      $this->original_flood_service->isAllowed($name, $threshold, $window, $identifier);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->original_flood_service->garbageCollection();
  }

}
