<?php

namespace Drupal\Tests\ga4_counter\Kernel;

use Drupal\ga4_counter\FakeRunReportResponse;
use Drupal\ga4_counter\QueryService;
use Drupal\ga4_counter\UpdateService;
use Drupal\KernelTests\KernelTestBase;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\ApiCore\ApiException;


/**
 * Tests the UpdateService class.
 *
 * @group ga4_counter
 */
class UpdateServiceTest extends KernelTestBase
{

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ga4_counter',
    'path',
    'path_alias'
  ];

  /**
   * The UpdateService under test.
   *
   * @var UpdateService
   */
  protected UpdateService $updateService;

  /**
   * @var array
   * Mock data for the class 'FakeRunReportResponse'.
   */
  protected array $mockData = [
    ['page_path' => '/search', 'page_views' => 6000],
    ['page_path' => '/about_us', 'page_views' => 4000],
    ['page_path' => '/products/product1', 'page_views' => 5000],
    ['page_path' => '/products/product2', 'page_views' => 5555],
    ['page_path' => '/information', 'page_views' => 600],
  ];

  /**
   * {@inheritdoc}
   * @throws ApiException
   */
  protected function setUp(): void
  {
    parent::setUp();

    $this->installSchema('ga4_counter', ['ga4_counter', 'ga4_nid_storage', 'ga4_tid_storage']);

    // Create a prophecy for the RunReportResponse class.
    $runReportResponseProphecy = $this->prophesize(RunReportResponse::class);
    $runReportResponseProphecy->getRows()->willReturn($this->mockData);
    $runReportResponseMock = $runReportResponseProphecy->reveal();

    // Create a prophecy for the QueryService class.
    $queryServiceProphecy = $this->prophesize(QueryService::class);
    $queryServiceProphecy->request()->willReturn($runReportResponseMock);
    $queryServiceMock = $queryServiceProphecy->reveal();

    // Instantiate the UpdateService with the mock object.
    $this->updateService = new FakeUpdateService(
      $this->container->get('database'),
      $queryServiceMock,
      $this->container->get('path_alias.manager'),
      $this->container->get('path.matcher')
    );
  }

  /**
   * Tests the update method.
   */
  public function testUpdatePathCount()
  {
    $this->updateService->update_path_count();
    $database = \Drupal::database();

    $queryCountRows = $database->query("SELECT COUNT(*) FROM {ga4_counter}");
    $count = $queryCountRows->fetchField();
    $this->assertEquals(
      count($this->mockData),
      $count,
      "The count of rows in the pagepath table is not equal to 5."
    );

//    $database->select('ga4_counter', 'ga4c')
//      ->fields('ga4c', 'pageviews')
//      ->condition('ga4c.page_path', '/information')
//      ->execute()->fetch();


  }

}
