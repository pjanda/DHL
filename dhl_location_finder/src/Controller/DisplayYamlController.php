<?php

namespace Drupal\dhl_location_finder\Controller;

use Drupal\Core\Controller\ControllerBase;

class DisplayYamlController extends ControllerBase
{

  public function displayYaml()
  {
    // Get the YAML data from the session
    $yamlOutput = $_SESSION['location_yaml_data'];

    // Clear the session data
    unset($_SESSION['location_yaml_data']);

    return [
      '#markup' => '<pre>' . $yamlOutput . '</pre>',
    ];
  }

}

