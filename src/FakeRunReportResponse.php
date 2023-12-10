<?php

namespace Drupal\ga4_counter;

use Google\Analytics\Data\V1beta\RunReportResponse;

/**
 * The method 'request' in the class 'QueryService' returns a RunReportResponse.
 * This class is used to mock this class.
 */
class FakeRunReportResponse extends RunReportResponse {
  /**
   * @var array $reportData
   * The report data that will be returned when the getRows method is called.
   * This data is set in the constructor.
   */
  private array $reportData;

  /**
   * FakeRunReportResponse constructor.
   *
   * @param array $reportData The report data that will be returned when the getRows method is called.
   */
  public function __construct($reportData) {
    parent::__construct();
    $this->reportData = $reportData;
  }

  /**
   * Returns the report data that was set in the constructor.
   *
   * @return array The report data.
   */
  public function getRows(): array {
    return $this->reportData;
  }
}
