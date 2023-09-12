<?php

declare(strict_types=1);

namespace Drupal\dhl_location_finder\Service;

use GuzzleHttp\ClientInterface;

class DhlApiService
{

  protected $httpClient;

  protected $api_key = 'demo-key';

  public function __construct(ClientInterface $http_client)
  {
    $this->httpClient = $http_client;
  }

  public function fetchLocations(
    string $country,
    string $city,
    string $postal_code
  ) {
    // Build the API request URL
    $api_url = 'https://api-sandbox.dhl.com/location-finder/v1/find-by-address?countryCode='
      . $country . '&addressLocality=' . $city . '&postalCode=' . $postal_code;

    // Make the API request
    $response = $this->httpClient->get($api_url, [
      'headers' => ['DHL-API-Key' => $this->api_key],
    ]);

    if ($response->getStatusCode() == 200) {
      return json_decode((string)$response->getBody());
    } else {
      return \Drupal::messenger()->addMessage($response->getStatusCode());
    }
  }

}
