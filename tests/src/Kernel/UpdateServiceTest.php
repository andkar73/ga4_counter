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


    $runReportResponseProphecy = $this->prophesize(RunReportResponse::class);
    $runReportResponseProphecy->getRows()->willReturn($this->mockData);
    $runReportResponseMock = $runReportResponseProphecy->reveal();

    // Create a prophecy for the QueryService class.
    $queryServiceProphecy = $this->prophesize(QueryService::class);

    //Define a prediction for the request method.
    //$mockResponse = new FakeRunReportResponse($this->mockData);
    //$mockResponse = new FakeRunReportResponse($this->mockData);
    $queryServiceProphecy->request()->willReturn($runReportResponseMock);
    // Reveal the prophecy to get the actual mock object.
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
  public function testUpdate()
  {

    // Call the update method and check the result.
    $this->updateService->update_page_views();

  }

}
