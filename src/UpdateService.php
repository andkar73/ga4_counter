<?php

namespace Drupal\ga4_counter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Site\Settings;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Class UpdateService.
 *
 * The class is used to update the table ga4_counter with data from Google
 * Analytics 4.
 *
 * @package Drupal\ga4_counter
 */
class UpdateService implements UpdateServiceInterface {
  use LoggerChannelTrait;

  /**
   * The query service.
   *
   * @var \Drupal\ga4_counter\QueryServiceInterface
   */
  protected $queryService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * UpdateService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\ga4_counter\QueryServiceInterface $queryService
   *   The query service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher.
   */
  public function __construct(Connection $connection,
                              QueryServiceInterface $queryService,
                              AliasManagerInterface $aliasManager,
                              PathMatcherInterface $pathMatcher) {
    $this->queryService = $queryService;
    $this->connection = $connection;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * Update the table ga4_counter.
   *
   * Fetch data (path and page views) from Google Analytics 4 (GA4) and update
   * the table ga4_counter.
   *
   * @throws \Exception
   */
  public function updatePathCount(): void {
    $queryResponse = $this->queryService->request();
    $this->truncateDatabaseTable('ga4_counter');
    foreach ($queryResponse->getRows() as $row) {
      $pagePath = $this->getPagePath($row);
      $pagePathMd5 = md5(Html::escape($pagePath));
      // Use only the first 2047 characters of the page path. This is extremely
      // long but Google does store everything and bots can make URIs that
      // exceed that length.
      $pagePathString = (strlen($pagePath) > 2047) ? substr($pagePath, 0, 2047) : $pagePath;

      // Update the Google Analytics Counter.
      $this->connection->merge('ga4_counter')
        ->key('pagepath_hash', $pagePathMd5)
        ->fields([
          'pagepath' => $pagePathString,
          'pageviews' => $this->getNumberOfPageViews($row),
        ])
        ->execute();
    }
  }

  /**
   * Update the table ga4_nid_storage and ga4_tid_storage.
   *
   * Gets get node id (nid) and terms id (tid) from the table ga4_counter
   * and stores it in the table.
   */
  public function updatePageViews(): void {
    $this->truncateDatabaseTable('ga4_nid_storage');
    $this->truncateDatabaseTable('ga4_tid_storage');
    $query = $this->connection->select('ga4_counter', 'ga4');
    $query->fields('ga4', ['pagepath', 'pageviews']);
    $query->orderBy('pageviews', 'DESC');
    $result = $query->execute();

    foreach ($result as $record) {
      $pathAlias = $record->pagepath;
      $pageViews = $record->pageviews;
      $systemPath = $this->aliasManager->getPathByAlias($pathAlias);
      $pathArray = explode('/', $systemPath);
      $type = NULL;
      if (isset($pathArray[1])) {
        $type = $pathArray[1];
      }
      $nid = NULL;
      if (isset($pathArray[2])) {
        $nid = is_numeric($pathArray[2]) ? $pathArray[2] : NULL;
      }
      $tid = NULL;

      // I check $path_array[4] to remove path with /edit that destroys the
      // statistics.
      if (isset($pathArray[3]) && !isset($pathArray[4])) {
        $tid = is_numeric($pathArray[3]) ? $pathArray[3] : NULL;
      }

      // Get a list of term-id:s (tid) to exclude from settings.php.
      $excludeTid = empty(settings::get('ga4_exclude_tid')) ? [] : settings::get('ga4_exclude_tid');

      if ($type === 'node' && $nid !== NULL) {
        $this->updateGa4TidStorage($nid, $pageViews, 'ga4_nid_storage', 'nid');
      }
      elseif ($type === 'taxonomy' && $tid !== NULL && !in_array($tid, $excludeTid)) {
        $this->updateGa4TidStorage($tid, $pageViews, 'ga4_tid_storage', 'tid');
      }

    }
  }

  /**
   * Update the table ga4_tid_storage with term id:s (tid).
   *
   * @param int $id
   *   The term id.
   * @param int $pageViews
   *   The number of page views.
   * @param string $table
   *   The table name.
   * @param string $keyId
   *   The key id.
   */
  public function updateGa4TidStorage(int $id, int $pageViews, string $table, string $keyId): void {
    $this->connection->merge($table)
      ->key($keyId, $id)
      ->fields([
        'pageview_total' => $pageViews,
      ])
      ->execute();
  }

  /**
   * Truncate values in the table ga4_counter.
   *
   * @param string $table
   *   The table name.
   *
   * @return void
   *   Truncate the table.
   */
  public function truncateDatabaseTable(string $table): void {
    $this->connection->truncate($table)->execute();
  }

  /**
   * Get the number of page views.
   *
   * @param mixed $row
   *   The row.
   *
   * @return int
   *   The number of page views.
   */
  public function getNumberOfPageViews(mixed $row): int {
    return (int) $row->getMetricValues()[0]->getValue();
  }

  /**
   * Get the page path.
   *
   * @param mixed $row
   *   The row.
   *
   * @return string
   *   The page path.
   */
  public function getPagePath(mixed $row): string {
    return $row->getDimensionValues()[0]->getValue();
  }

}
