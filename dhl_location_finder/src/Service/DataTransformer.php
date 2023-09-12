<?php

declare(strict_types=1);

namespace Drupal\dhl_location_finder\Service;

use Symfony\Component\Yaml\Yaml;

class DataTransformer
{

  public function toYaml($data): string
  {
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

    return $yamlOutput;
  }

}

