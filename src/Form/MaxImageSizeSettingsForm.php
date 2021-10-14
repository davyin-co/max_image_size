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
      '#title' => $this->t('Presave enabled'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t('Check this box to enable resizing of images when they are added to Drupal.'),
    ];

    $form = parent::buildForm($form, $form_state);
    $form['actions']['dealing_all_image_resizing'] = [
      '#type' => 'submit',
      '#value' => $this->t('Dealing all image resizing'),
      '#submit' => ['::dealingSubmitForm'],
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

  /**
   * Dealing all image resizing.
   * @param array $form
   * @param FormStateInterface $form_state
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function dealingSubmitForm(array &$form, FormStateInterface $form_state) {
    $files = \Drupal::entityTypeManager()->getStorage('file')
      ->loadByProperties(['filemime' => 'image/jpeg']);
    $ops = [];
    foreach ($files as $file) {
      $ops[] = [
        '\Drupal\max_image_size\ResizeBatch::resizeProcessCallback',
        [
          $file,
        ]];
    }
    if ($ops) {
      $batch = [
        'title' => t('Dealing all image resizing'),
        'init_message' => t('Start to dealing all image resizing'),
        'error_message' => t('Dealing all image resizing error'),
        'operations' => $ops,
        'finished' => '\Drupal\max_image_size\ResizeBatch::resizeFinishedCallback',
        'batch_redirect' => Url::fromRoute('max_image_size.list')->toString(),
      ];
      batch_set($batch);
    } else {
      \Drupal::messenger()->addStatus(t('No image to resizing'));
    }
    $form_state->setRedirectUrl(Url::fromRoute('max_image_size.list'));
  }

}
