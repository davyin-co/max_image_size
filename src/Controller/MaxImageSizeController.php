<?php


namespace Drupal\max_image_size\Controller;


use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;

/**
 * Class MaxImageSizeController
 * @package Drupal\max_image_size\Controller
 */
class MaxImageSizeController {

  /**
   * Return resize image list.
   * @return array
   */
  public function resizeList() {
    $header = [
      ['data' => t('name'), 'field' => 'f.filename'],
      ['data' => t('created'), 'field' => 'm.created', 'sort' => TableSort::DESC],
      //['data' => t('changed'), 'field' => 'm.changed'],
      ['data' => t('reSize'), 'field' => 'm.width'],
      ['data' => t('origin'), 'field' => 'm.original_width'],
    ];
    $query = \Drupal::database()->select('max_image_size', 'm')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->innerJoin('file_managed', 'f', 'f.uuid = m.uuid');
    $query->fields('f', ['filename', 'uri']);
    $query->fields('m', ['created', 'width', 'height', 'original_width', 'original_height']);
    $query->limit(20);
    $data = $query->orderByHeader($header)->execute();
    $rows = [];

    foreach ($data as $row) {
      $row = (array) $row;
      $row['created'] = date('Y-m-d H:i:s', $row['created']);
      $row['width'] = $row['width'] . 'x' . $row['height'];
      $row['original_width'] = $row['original_width'] . 'x' . $row['original_height'];

      $row['filename'] = new FormattableMarkup('<a href="@href" target="_blank">@title</a>', ['@href' => Url::fromUri(file_create_url($row['uri']))->toString(), '@title' => $row['filename']]);
      unset($row['uri']);
      unset($row['height']);
      unset($row['original_height']);
      $rows[] = ['data' => $row];
    }
    $build['table_pager'][] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $build['table_pager'][] = [
      '#type' => 'pager',
    ];
    return $build;
  }
}
