<?php

namespace Drupal\Tests\ga4_counter\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\ga4_counter\QueryServiceInterface;
use Drupal\ga4_counter\UpdateService;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Class UpdateService.
 *
 * The class is used to update the table ga4_counter with data from Google
 * Analytics 4.
 *
 * @package Drupal\ga4_counter
 */
class FakeUpdateService extends UpdateService {
  /**
   * Mock data for the class 'FakeRunReportResponse'.
   *
   * @var array
   * example: ['page_path' => '/contact-us', 'page_views' => 5000]
   */
  protected array $fakeData;

  /**
   * Return the number for page views for a path.
   *
   * @param mixed $row
   *   The row from the query.
   *
   * @return int
   *   The number of page views.
   */
  public function getNumberOfPageViews(mixed $row): int {
    return $row['page_views'];
  }

  /**
   * This method returns a page path from Google Analytics.
   *
   * @param mixed $row
   *   The row from the query.
   *
   * @return string
   *   The page path.
   */
  public function getPagePath(mixed $row): string {
    return $row['page_path'];
  }

}
