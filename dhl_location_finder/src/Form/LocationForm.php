<?php
declare(strict_types=1);

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides a DHL location finder form.
 */
final class LocationForm extends FormBase
{

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
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  protected $httpClient;

  public function __construct(ClientInterface $http_client)
  {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('http_client')
    );
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

    // Build the API request URL
    $api_url = 'https://api-sandbox.dhl.com/location-finder/v1/find-by-address?countryCode=' . $country . '&addressLocality=' . $city . '&postalCode=' . $postal_code;

    // Define your API key (replace 'ApiKeyPasteHere' with your actual API key)
    $api_key = 'demo-key';

    // Make the API request
    $response = $this->httpClient->get($api_url, [
      'headers' => [
        'DHL-API-Key' => $api_key,
      ],
    ]);

    if ($response->getStatusCode() == 200) {
      $data = json_decode((string)$response->getBody());

      $yamlData = [];

      foreach ($data->locations as $location) {
        // Extract numbers from the streetAddress
        preg_match_all(
          '/\d+/',
          $location->place->address->streetAddress,
          $matches
        );
        $lastNumber = end(
          $matches[0]
        ); // take the last number which usually represents the street number

        // Check if the location has an odd street number
        if ($lastNumber % 2 != 0) {
          continue; // skip this location if the street number is odd
        }

        $openingHours = [
          'monday' => '',
          'tuesday' => '',
          'wednesday' => '',
          'thursday' => '',
          'friday' => '',
          'saturday' => '',
          'sunday' => '',
        ];

        foreach ($location->openingHours as $openingDay) {
          // Extract the day from the URL
          $day = strtolower(basename($openingDay->dayOfWeek));

          // Check if the day exists in the openingHours array
          if (!isset($openingHours[$day])) {
            continue;
          }

          // Combine the day with the opening hours
          $hours = $openingDay->opens . ' - ' . $openingDay->closes;

          if ($openingHours[$day]) {
            $openingHours[$day] .= ', ' . $hours;
          } else {
            $openingHours[$day] = $hours;
          }
        }

        // Check if the location has opening hours on weekends
        if (empty($openingHours['saturday']) && empty($openingHours['sunday'])) {
          continue; // skip this location if it doesn't have weekend opening hours
        }

        $yamlData[] = [
          'locationName' => $location->name,
          'address' => [
            'countryCode' => $location->place->address->countryCode,
            'postalCode' => $location->place->address->postalCode,
            'addressLocality' => $location->place->address->addressLocality,
            'streetAddress' => $location->place->address->streetAddress,
          ],
          'openingHours' => $openingHours,
        ];
      }

      // Convert the filtered data to YAML format
      $yamlOutput = Yaml::dump(
        $yamlData,
        4
      );  // "4" here denotes the depth for inline sequences; it helps in formatting

      // Store the YAML string in the session or cache for retrieval
      $_SESSION['location_yaml_data'] = $yamlOutput;

      // Redirect to custom display page
      $form_state->setRedirect('dhl_location_finder.display_yaml');
    } else {
      // Handle API request error
      \Drupal::messenger()
        ->addMessage('Error fetching data from the API.', 'error');
    }
  }

}
