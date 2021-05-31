<?php

namespace Drupal\whitelist_flood_ip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WhitelistIpForm.
 */
class WhitelistIpForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'whitelist_flood_ip.whitelistip',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whitelist_ip_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('whitelist_flood_ip.whitelistip');
    $form['ip_addresses'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IP address.'),
      '#description' => $this->t('IP address that you want to whitelist. Give one IP per line.'),
      '#default_value' => $config->get('ip_addresses'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('whitelist_flood_ip.whitelistip')
      ->set('ip_addresses', $form_state->getValue('ip_addresses'))
      ->save();
  }

}
