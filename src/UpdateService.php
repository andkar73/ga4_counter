<?php

namespace Drupal\ga4_counter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Html;

/**
 * Class UpdateService.
 * The class is used to update the table ga4_counter with data from Google Analytics 4.
 * @package Drupal\ga4_counter
 *
 */

class UpdateService implements UpdateServiceInterface {
  use LoggerChannelTrait;

  /**
   * @var \Drupal\ga4_counter\QueryServiceInterface
   */
  protected $queryService;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * UpdateService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection The database connection.
   * @param \Drupal\ga4_counter\QueryServiceInterface $queryService The query service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher The path matcher.
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
   * Fetch data (path and page views) from Google Analytics 4 and update the table
   * ga4_counter.
   *
   * @throws \Exception
   */
  public function update_path_count(): void {
    $queryResponse = $this->queryService->request();
    $this->truncateDatabaseTable('ga4_counter');
    foreach ($queryResponse->getRows() as $row) {
      // Use only the first 2047 characters of the pagepath. This is extremely long
      // but Google does store everything and bots can make URIs that exceed that length.
      $page_path = $this->getPagePath($row);

      $page_path_md5 = md5(Html::escape($page_path));

      $page_path_string = (strlen($page_path) > 2047) ? substr($page_path,0,2047) : $page_path;

      // Update the Google Analytics Counter.
      $this->connection->merge('ga4_counter')
        ->key('pagepath_hash', md5($page_path))
        ->fields([
          'pagepath' => $page_path_string,
          'pageviews' => $this->getNumberOfPageViews($row),
        ])
        ->execute();
    }
  }

  /**
   * Gets get node id (nid) and terms id (tid) from the table ga4_counter
   * and stores it in the table
   *
   * @TDO 'langcode' needs to be a setting.
   */
  public function update_page_views(): void {
    $this->truncateDatabaseTable('ga4_nid_storage');
    $this->truncateDatabaseTable('ga4_tid_storage');
    $query = $this->connection->select('ga4_counter', 'ga4');
    $query->fields('ga4', ['pagepath', 'pageviews']);
    $query->orderBy('pageviews', 'DESC');
    $result = $query->execute();

    foreach ($result as $record) {
      $path_alias  = $record->pagepath;
      $pageviews = $record->pageviews;
      $system_path = $this->aliasManager->getPathByAlias($path_alias, 'sv');
      $path_array = explode('/', $system_path);
      $type = NULL;
      if (isset($path_array[1])) {
        $type = $path_array[1];
      }
      $nid = NULL;
      if (isset($path_array[2])) {
        $nid = is_numeric($path_array[2]) ? $path_array[2] :  NULL;
      }
      $tid = NULL;

      // I check $path_array[4] to remove path with /edit that destroys the statistics.
      if (isset($path_array[3]) && !isset($path_array[4])) {
        $tid = is_numeric($path_array[3]) ? $path_array[3] :  NULL;
      }

      // Get a list of term-id:s (tid) to exclude from settings.php.
      $exclude_tid = empty(settings::get('ga4_exclude_tid')) ? [] : settings::get('ga4_exclude_tid');

      if ($type === 'node' && $nid !== null) {
        $this->update_ga4_tid_storage($nid, $pageviews, 'ga4_nid_storage', 'nid');
      }
      elseif ($type === 'taxonomy' && $tid !== null && !in_array($tid, $exclude_tid)) {
        $this->update_ga4_tid_storage($tid, $pageviews, 'ga4_tid_storage', 'tid');
      }

    }
  }

  /**
   * Update the table ga4_tid_storage with term id:s (tid) or
   * ga4_nid_storage with term id:s (tid)
   *
   * @param int $id
   * @param int $pageViews
   * @param string $table
   * @param string $key_id
   */
  public function update_ga4_tid_storage(int $id, int $pageViews, string $table, string $key_id): void {
    $this->connection->merge($table)
      ->key($key_id, $id)
      ->fields([
        'pageview_total' => $pageViews,
      ])
      ->execute();
  }

  /**
   * Truncate values in the table ga4_counter.
   * @param string $table
   * @return void
   */
  public function truncateDatabaseTable(string $table): void {
    $this->connection->truncate($table)->execute();
  }

  /**
   * @param mixed $row
   * @return int
   */
  public function getNumberOfPageViews(mixed $row): int {
    return (int)$row->getMetricValues()[0]->getValue();
  }

  /**
   * @param mixed $row
   * @return string
   */
  public function getPagePath(mixed $row): string
  {
    return $row->getDimensionValues()[0]->getValue();
  }

}
