<?php

namespace Drupal\max_image_size\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MaxImageSizeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'max_image_size_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['max_image_size.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('max_image_size.settings');
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('width'),
      '#description' => $this->t('The maximum allowed width for Drupal managed images.'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 99999,
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('height'),
      '#description' => $this->t('The maximum allowed height for Drupal managed images.'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 99999,
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t('Check this box to resizing of images when they are added to Drupal.'),
    ];

    $form['actions']['dealing_all_image_resizing'] = [
      '#type' => 'link',
      '#title' => $this->t('Dealing all image resizing'),
      '#url' => Url::fromRoute('max_image_size.resizing_confirm'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('max_image_size.settings');
    $config->set('width', $form_state->getValue('width'))
      ->set('height', $form_state->getValue('height'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
