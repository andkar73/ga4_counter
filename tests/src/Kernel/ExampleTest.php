<?php

namespace Drupal\Tests\ga4_counter\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test description.
 *
 * @group ga4_counter
 */
class ExampleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ga4_counter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock required services here.
  }

  /**
   * Test callback.
   */
  public function testSomething() {
    $result = $this->container->get('transliteration')->transliterate('Друпал');
    self::assertSame('Drupal', $result);
  }

}
