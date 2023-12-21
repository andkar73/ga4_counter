<?php

namespace Drupal\ga4_counter;

use Drupal\Core\Site\Settings;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportResponse;

/**
 * Query to Google analytics 4.
 *
 * The class do a query to Google analytics 4 and fetches statistics over how
 * many page views they have had over a 7 days period.
 *
 * @package Drupal\ga4_counter
 */
class QueryService implements QueryServiceInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Google\ApiCore\ApiException
   */
  public function request(): RunReportResponse {
    $apiCredentialsPath = settings::get('ga4_data_api_credentials');
    $propertyId = settings::get('ga4_data_api_property_id');

    // Adds a variable to the server environment.
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $apiCredentialsPath);

    // Using a default constructor instructs the client to use the credentials
    // specified in GOOGLE_APPLICATION_CREDENTIALS environment variable.
    $client = new BetaAnalyticsDataClient();

    $startDate = date('Y-m-d', strtotime("-7 day"));

    // Make an API call to the Data API.
    $response = $client->runReport([
      'property' => 'properties/' . $propertyId,
      'dateRanges' => [
        new DateRange([
          'start_date' => $startDate,
          'end_date' => 'today',
        ]),
      ],
      'dimensions' => [new Dimension(
        [
          'name' => 'pagePath',
        ]
        ),
      ],
      'metrics' => [new Metric(
        [
          'name' => 'screenPageViews',
        ]
        ),
      ],
    ]);

    return $response;
  }

}
