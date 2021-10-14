<?php

namespace Drupal\max_image_size;

/**
 * Class ResizeBatch
 * @package Drupal\max_image_size
 */
class ResizeBatch {

  /**
   * Image resize process.
   * @param $file
   * @param $context
   */
  public static function resizeProcessCallback($file, &$context) {
    if (empty($context['results'])) {
      $context['results']['message'] = [];
      $context['results']['counter'] = [
        '@num_items' => 0,
        '@created' => 0,
        '@ignored' => 0,
      ];
    }
    $resize = \Drupal::service('max_image_size.resize_file');
    if ($image = $resize->resizeImage($file)) {
      $context['results']['counter']['@created'] += 1;
    } else {
      $context['results']['counter']['@ignored'] += 1;
    }
    $context['results']['counter']['@num_items'] += 1;
  }

  /**
   * Resize finished.
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function resizeFinishedCallback($success, $results, $operations) {
    if ($success) {
      $singular_message = "Processed 1 item (@created created, @ignored ignored) - done with '@name'";
      $plural_message = "Processed @num_items items (@created created, @ignored ignored)";
      \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($results['counter']['@num_items'],
        $singular_message,
        $plural_message,
        $results['counter']));
    } else {
      \Drupal::messenger()->addError("Dealing all image resizing error");
    }
  }
}
