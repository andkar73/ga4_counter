<?php


namespace Drupal\ga4_counter;


/**
 * Interface QueryServiceInterface.
 */
interface QueryServiceInterface {

  /**
   * Does a google analytics request.
   * @return array A array containing the query result
   *
   */
  public function request();


}
