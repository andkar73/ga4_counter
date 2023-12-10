<?php

namespace Drupal\ga4_counter;

use Google\Analytics\Data\V1beta\RunReportResponse;

/**
 * Interface QueryServiceInterface.
 */
interface QueryServiceInterface {

  /**
   * Does a google analytics request.
   * @return RunReportResponse An object containing the query result
   *
   */
  public function request(): RunReportResponse;

}
