<?php

namespace Drupal\Tests\ga4_counter\Kernel;

use Drupal\ga4_counter\QueryServiceInterface;
use Drupal\ga4_counter\UpdateService;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Class UpdateService.
 * The class is used to update the table ga4_counter with data from Google Analytics 4.
 * @package Drupal\ga4_counter
 *
 */
class FakeUpdateService extends UpdateService {
  /**
   * @var array $fakeData The fake data used for testing. The array should contain two values
   * example: ['page_path' => '/contact-us', 'page_views' => 5000]
   */
  protected array $fakeData;

  /**
   * UpdateService constructor.
   *
   * @param Connection $connection The database connection.
   * @param QueryServiceInterface $queryService The query service.
   * @param AliasManagerInterface $aliasManager The alias manager.
   * @param PathMatcherInterface $pathMatcher The path matcher.
   */
  public function __construct(Connection $connection,
                              QueryServiceInterface $queryService,
                              AliasManagerInterface $aliasManager,
                              PathMatcherInterface $pathMatcher) {
    parent::__construct($connection, $queryService, $aliasManager, $pathMatcher);
  }

  /**
   * @param mixed $row
   * @return int
   */
  public function getNumberOfPageViews(mixed $row): int {
    return $row['page_views'];
  }

  /**
   * @param mixed $row
   * @return string
   *
   * This method returns a page path from Google Analytics.
   */
  public function getPagePath(mixed $row): string{
    return $row['page_path'];
  }
}
