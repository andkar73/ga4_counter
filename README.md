GA4 Counter
===========

This Drupal module fetch data through Google Analytics Data API (GA4) and expose the data to views.

The module is a test to learn how the new Data API for Google Analytics 4 is working. 

### Data API credentials


https://console.cloud.google.com/welcome
.......


###  Documentation from Google

https://developers.google.com/analytics/devguides/reporting/data/v1

PHP example:
https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart-client-libraries#php_1

### Installation

Install the dependency with composer 

```sh
$ composer require google/analytics-data
```

Add the following settings to the file settings.php:
```
$settings['ga_data_api_credentials'] = '<path to your data api credentials json file>';
$settings['ga_data_api_property_id'] = '<property id for your Googel Analytics 4>';
```
