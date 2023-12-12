<?php

namespace Drupal\ga4_counter;

use Google\Analytics\Data\V1beta\RunReportResponse;

/**
 * Interface for QueryService.
 */
interface QueryServiceInterface {

  /**
   * Does a google analytics request.
   *
   * @return \Google\Analytics\Data\V1beta\RunReportResponse
   *   An object containing the query result
   */
  public function request(): RunReportResponse;

}
