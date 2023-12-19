<?php

namespace Drupal\Tests\ga4_counter\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\ga4_counter\QueryService;
use Drupal\ga4_counter\UpdateService;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Google\Analytics\Data\V1beta\RunReportResponse;

/**
 * Tests the UpdateService class.
 *
 * @group ga4_counter
 */
class UpdateServiceTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'node',
    'ga4_counter',
    'path_alias',
  ];

  /**
   * The UpdateService under test.
   *
   * @var \Drupal\ga4_counter\UpdateService
   */
  protected UpdateService $updateService;

  /**
   * Mock data for the class 'FakeRunReportResponse'.
   *
   * @var array
   */
  protected array $mockData = [
    'node' => [
      ['title' => 'About us', 'page_path' => '/about_us', 'page_views' => 4000],
      ['title' => 'Product1', 'page_path' => '/products/product1', 'page_views' => 5000],
      ['title' => 'Product2', 'page_path' => '/products/product2', 'page_views' => 5555],
      ['title' => 'Information', 'page_path' => '/information', 'page_views' => 600],
    ],
    'term' => [
      ['title' => 'Category1', 'page_path' => '/category1', 'page_views' => 7000],
      ['title' => 'Category2', 'page_path' => '/category2', 'page_views' => 8000],
    ],
    'other_page' => [
      ['title' => 'Search', 'page_path' => '/search', 'page_views' => 6000],
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Google\ApiCore\ApiException
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('ga4_counter', ['ga4_counter', 'ga4_nid_storage', 'ga4_tid_storage']);
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('taxonomy_term');

    foreach ($this->mockData['node'] as $row) {
      $node = $this->createNode($row['title'], $row['page_path']);
      $this->createPathAlias('/node/' . $node->id(), $row['page_path'], 'sv');
    }

    foreach ($this->mockData['term'] as $row) {
      $term = Term::create([
        'name' => $row['title'],
        'vid' => 'tags',
      ]);
      $term->save();
      $this->createPathAlias('/taxonomy/term/' . $term->id(), $row['page_path'], 'sv');

      $this->addTaxonomyEditToMockSata($term->id());
    }

    $queryServiceMock = $this->createMockForTheQueryServiceClass();
    $this->instantiateTheUpdateServiceWithTheMockObject($queryServiceMock);

  }

  /**
   * Tests the class UpdateService.
   */
  public function testUpdateService(): void {
    $this->updatePageCounterTests();
    $this->updatePageViewsTests();
  }

  /**
   * Adds taxonomy edit to mock data.
   *
   * @param int $termId
   *   The term ID.
   */
  private function addTaxonomyEditToMockSata(int $termId): void {
    $this->mockData['other_page'][] = [
      'title' => 'Edit term' . $termId,
      'page_path' => '/taxonomy/term/' . $termId . '/edit',
      'page_views' => 1,
    ];
  }

  /**
   * Tests the method update_path_count.
   */
  private function updatePageCounterTests(): void {
    $this->updateService->update_path_count();

    $database = \Drupal::database();

    $queryCountRows = $database->query("SELECT COUNT(*) FROM {ga4_counter}");
    $count = $queryCountRows->fetchField();
    $numberOfRows = count(array_reduce($this->mockData, 'array_merge', []));
    $this->assertEquals(
      $numberOfRows,
      $count,
      "The count of rows in the pagepath table is not equal to {$numberOfRows}."
    );

    $pageViews = $database->select('ga4_counter', 'ga4c')
      ->fields('ga4c', ['pageviews'])
      ->condition('ga4c.pagepath', '/information')
      ->execute()->fetchField();
    $this->assertEquals(
      600,
      $pageViews,
      "The page view for '/information' should be 600 but is {$pageViews}."
    );

    foreach ($this->mockData['other_page'] as $row) {
      $pageViews = $database->select('ga4_counter', 'ga4c')
        ->fields('ga4c', ['pageviews'])
        ->condition('ga4c.pagepath', $row['page_path'])
        ->execute()->fetchField();
      $this->assertEquals(
        $row['page_views'],
        $pageViews,
        "The page view for '{$row['page_path']}' should be {$row['page_views']} but is {$pageViews}."
      );
    }

  }

  /**
   * Tests the method update_page_views.
   */
  private function updatePageViewsTests() {
    $this->updateService->update_page_views();

    $database = \Drupal::database();
    $queryCountRows = $database->query("SELECT COUNT(*) FROM {ga4_nid_storage}");
    $count = $queryCountRows->fetchField();
    $numberOfNods = count($this->mockData['node']);
    $this->assertEquals(
      $numberOfNods,
      $count,
      "The count of rows in the nid table is not equal to {$numberOfNods}."
    );

    $queryCountRows = $database->query("SELECT COUNT(*) FROM {ga4_tid_storage}");
    $count = $queryCountRows->fetchField();
    $numberOfNods = count($this->mockData['term']);
    $this->assertEquals(
      $numberOfNods,
      $count,
      "The count of rows in the tid table is not equal to {$numberOfNods}."
    );

    $system_path = \Drupal::service('path_alias.manager')->getPathByAlias($this->mockData['term'][0]['page_path'], 'sv');
    $path_array = explode('/', $system_path);
    $term = $database->select('ga4_tid_storage', 'ga4t')
      ->fields('ga4t', ['pageview_total'])
      ->condition('ga4t.tid', $path_array[3])
      ->execute()->fetchField();
    $this->assertEquals(
      $this->mockData['term'][0]['page_views'],
      $term,
      "The page view for '{$this->mockData['term'][0]['page_path']}' should be {$this->mockData['term'][0]['page_views']} but is {$term}."
    );
  }

  /**
   * Create a mock object for the QueryService class.
   *
   * @return object
   *   The mock object.
   *
   * @throws \Google\ApiCore\ApiException
   */
  public function createMockForTheQueryServiceClass(): object {
    // Create a prophecy for the RunReportResponse class.
    $runReportResponseProphecy = $this->prophesize(RunReportResponse::class);

    // Flatten mockData arrays to be simulate the request data form GA4.
    $flattenedMockArrayOneLevel = array_reduce($this->mockData, 'array_merge', []);

    $runReportResponseProphecy->getRows()->willReturn($flattenedMockArrayOneLevel);
    $runReportResponseMock = $runReportResponseProphecy->reveal();

    // Create a prophecy for the QueryService class.
    $queryServiceProphecy = $this->prophesize(QueryService::class);
    $queryServiceProphecy->request()->willReturn($runReportResponseMock);

    return $queryServiceProphecy->reveal();
  }

  /**
   * Instantiate the variable updateService with the mock object.
   *
   * @param object $queryServiceMock
   *   The mock object QueryService.
   *
   * @return void
   *   NO return value.
   *
   * @throws \Exception
   */
  private function instantiateTheUpdateServiceWithTheMockObject(object $queryServiceMock): void {
    $this->updateService = new FakeUpdateService(
      $this->container->get('database'),
      $queryServiceMock,
      $this->container->get('path_alias.manager'),
      $this->container->get('path.matcher')
    );
  }

  /**
   * Creates a new node with the given title and page path.
   *
   * @param string $title
   *   The title of the node.
   * @param string $page_path
   *   The page path of the node.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  private function createNode(string $title, string $page_path): Node {
    $node = Node::create([
      'type' => 'page',
      'title' => $title,
      'langcode' => 'sv',
      'path' => [
        'alias' => $page_path,
        'langcode' => 'sv',
      ],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Creates a path alias.
   *
   * @param string $path
   *   The path.
   * @param string $alias
   *   The alias.
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created path alias entity.
   */
  protected function createPathAlias(string $path, string $alias, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): EntityInterface {
    /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
    $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
      'path' => $path,
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    return $path_alias;
  }

}
