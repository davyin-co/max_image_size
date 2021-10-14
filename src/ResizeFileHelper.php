<?php

namespace Drupal\max_image_size;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Image\Image;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;

class ResizeFileHelper {

  /**
   * @var ImageFactory
   */
  private $imageFactory;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * @var Connection
   */
  private $database;

  /**
   * ResizeFileHelper constructor.
   * @param ImageFactory $image_factory
   * @param ConfigFactoryInterface $config_factory
   * @param LoggerChannelFactoryInterface $logger
   */
  public function __construct(ImageFactory $image_factory, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger, Connection $database) {
    $this->imageFactory = $image_factory;
    $this->config = $config_factory->get('max_image_size.settings');
    $this->logger = $logger->get('max_image_size');
    $this->database = $database;
  }

  /**
   * Verify file is a image.
   * @param FileInterface $file
   * @return bool
   */
  public function isImageFile(FileInterface $file) {
    if ($file && $image = $this->imageFactory->get($file->getFileUri())) {
      return $image->isValid();
    }
    return FALSE;
  }

  /**
   * Resize image.
   * @param FileInterface $file
   * @return Image|false
   */
  public function resizeImage(FileInterface $file) {
    if (empty($file)) {
      return FALSE;
    }
    $fid = $file->id();
    $this->logger->debug("FID:" . $file->id() . ' UUID:' . $file->uuid());
    if (!$this->isImageFile($file)) {
      $this->logger->notice(t('Unable to load image @uri (@fid) for resizing.', ['@fid' => $fid, '@uri' => $file->getFileUri()]));
      return FALSE;
    }
    /** @var Image $image */
    $image = $this->imageFactory->get($file->getFileUri());
    $width = $this->config->get('width');
    $height = $this->config->get('height');
    if (!($width && $width > 0 && $height && $height > 0)) {
      $this->logger->notice(t('Invalid image dimensions specified: @widthx@height', ['@width' => $width, '@height' => $height]));
      return FALSE;
    }
    $origin_w = $image->getWidth();
    $origin_h = $image->getHeight();
    $origin_s = $image->getFileSize();
    if ($origin_w <= $width && $origin_h <= $height) {
      return FALSE;
    }
    //Scales image while maintaining aspect ratio.
    if (!$image->scale($width, $height)) {
      $this->logger->notice(t('Failed to scale image @uri (@fid).', ['@fid' => $fid, '@uri' => $file->getFileUri()]));
      return FALSE;
    }

    if (!$image->save()) {
      $this->logger->notice(t('Unable to save image @uri (@fid)', ['@fid' => $fid, '@uri' => $file->getFileUri()]));
      return FALSE;
    }

    $this->database->insert('max_image_size')
      ->fields([
        "uuid" => $file->uuid(),
        "created" => time(),
        "changed" => time(),
        "width" => $image->getWidth(),
        "height" => $image->getHeight(),
        "size" => $image->getFileSize(),
        "original_width" => $origin_w,
        "original_height" => $origin_h,
        "original_size" => $origin_s,
      ])
      ->execute();
    return $image;
  }

  /**
   * Delete image scale recode.
   * @param FileInterface $file
   */
  public function deleteImage(FileInterface $file) {
    if ($file) {
      $this->database->delete('max_image_size')->condition('uuid', $file->uuid())->execute();
    }
  }
}
