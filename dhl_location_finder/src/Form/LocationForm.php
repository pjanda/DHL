<?php

declare(strict_types=1);

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhl_location_finder\Service\DhlApiService;
use Drupal\dhl_location_finder\Service\DataTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DHL location finder form.
 */
final class LocationForm extends FormBase
{

  protected $dhlApiService;

  protected $dataTransformer;

  public function __construct(
    DHLApiService $dhl_api_service,
    DataTransformer $data_transformer
  ) {
    $this->dhlApiService = $dhl_api_service;
    $this->dataTransformer = $data_transformer;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('dhl_location_finder.dhl_api_service'),
      $container->get('dhl_location_finder.data_transformer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'dhl_location_finder_location';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
    ];

    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    // @todo Validate the form here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get form values
    $country = $form_state->getValue('country');
    $city = $form_state->getValue('city');
    $postal_code = $form_state->getValue('postal_code');

    // Use the services:
    $data = $this->dhlApiService->fetchLocations($country, $city, $postal_code);
    $yamlOutput = $this->dataTransformer->toYaml($data);

    // Store the YAML string in the session or cache for retrieval
    $_SESSION['location_yaml_data'] = $yamlOutput;

    // Redirect to custom display page
    $form_state->setRedirect('dhl_location_finder.display_yaml');
  }

}
