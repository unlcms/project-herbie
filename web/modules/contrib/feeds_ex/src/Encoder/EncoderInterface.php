<?php

namespace Drupal\feeds_ex\Encoder;

use Drupal\Core\Form\FormStateInterface;

/**
 * Coverts text encodings.
 */
interface EncoderInterface {

  /**
   * Constructs a EncoderInterface object.
   *
   * @param array $encoding_list
   *   The list of encodings to search through.
   */
  public function __construct(array $encoding_list);

  /**
   * Converts a string to UTF-8.
   *
   * @param string $data
   *   The string to convert.
   *
   * @return string
   *   The encoded string, or the original string if encoding failed.
   */
  public function convertEncoding($data);

  /**
   * Returns the configuration form to select encodings.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Validates the encoding configuration form.
   *
   * @param array &$values
   *   The form values.
   */
  public function configFormValidate(array &$values);

}
