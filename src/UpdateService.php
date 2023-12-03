<?php


namespace Drupal\ga4_counter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;

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
  protected $alias_manager;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $path_matcher;

  public function __construct(Connection $connection,
                              QueryServiceInterface $queryService,
                              AliasManagerInterface $alias_manager,
                              PathMatcherInterface $path_matcher) {
    $this->queryService = $queryService;
    $this->connection = $connection;
    $this->alias_manager = $alias_manager;
    $this->path_matcher = $path_matcher;
  }

  /**
   * Fetch data (path and page views) from Google analytics 4 and update the table
   * ga4_counter.
   *
   * @throws \Exception
   */
  public function update_pathe_count(): void {
    $queryResponse = $this->queryService->request();
    $this->connection->truncate('ga4_counter');
    foreach ($queryResponse->getRows() as $row) {
      // Use only the first 2047 characters of the pagepath. This is extremely long
      // but Google does store everything and bots can make URIs that exceed that length.
      $page_path = $row->getDimensionValues()[0]->getValue();
      $page_path_md5 = md5(Html::escape($page_path));

      $page_path_string = (strlen($page_path) > 2047) ? substr($page_path,0,2047) : $page_path;

      // Update the Google Analytics Counter.
      $this->connection->merge('ga4_counter')
        ->key('pagepath_hash', md5($page_path))
        ->fields([
          'pagepath' => $page_path_string,
          'pageviews' => (int) $row->getMetricValues()[0]->getValue(),
        ])
        ->execute();
    }
  }

  /**
   * Gets get node id (nid) and terms id (tid) from the table ga4_counter
   * and stores it in the table
   *
   */
  function update_page_views() {
    $this->connection->truncate('ga4_nid_storage');
    $this->connection->truncate('ga4_tid_storage');
    $query = $this->connection->select('ga4_counter', 'ga4');
    $query->fields('ga4', ['pagepath', 'pageviews']);
    $query->orderBy('pageviews', 'DESC');
    $result = $query->execute();

    foreach ($result as $record) {
      $path_alias  = $record->pagepath;
      $pageviews = $record->pageviews;
      $system_path = $this->alias_manager->getPathByAlias($path_alias, 'sv');
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

      // I check $path_array[4] to remove path with /edit that destorys the statistics.
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
   * @param $id
   * @param $pageviews
   * number of page views
   * @param $table
   * @param $key_id
   */
  function update_ga4_tid_storage($id, $pageviews, $table, $key_id) {
    $this->connection->merge($table)
      ->key($key_id, $id)
      ->fields([
        'pageview_total' => $pageviews,
      ])
      ->execute();
  }
}
