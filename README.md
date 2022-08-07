GA4 Counter
===========

This Drupal module is a very specialized module that uses the ‘Data API’ for ‘Google Analytics 4’. The module fetches data about the number of page views for nodes and terms for the last seven days. The data is exposed in Views and can be used when showing content (nodes) or taxonomy terms.

The modules ‘Google Analytics Counter’ (https://www.drupal.org/project/google_analytics_counter ) can replace this module, when it gets support for Google Analytics 4, if you do not need to support taxonomy terms.

## Data API credentials
To use this module you need to create a service account for GA4 on
https://console.cloud.google.com/welcome

Download the json file with Google application credentials. 
Read more about this process in the google documentation https://developers.google.com/analytics/devguides/reporting/data/v1.

## Installation
* You need to have access to the server to use this module.
* The google/analytics-data dependency requires the php module bcmath to be installed on the server.

* Install the dependency with composer
```sh
$ composer require google/analytics-data
```

* Upload the json file with Google application credentials to your server.

* Add the following settings to the file settings.php:
```
$settings['ga_data_api_credentials'] = '<path to your json file with Google application credentials>';
$settings['ga_data_api_property_id'] = '<property id for your Google Analytics 4 account>';
$settings['ga4_exclude_tid'] = <Array with number representing term id:s you want to exclude (ex. [9, 10, 17, 14,]). This setting is optional>;
```
* Enable the module as a regular Drupal module.

## Architecture
Once a day, with the cron job, the module fetches data, from Google analytics, containing a URL and an integer, representing the number of times the URL has been visited in the last seven days. The data is saved in the table ga4_counter.

After the data is saved in the database the module determines if the URL is a node or a taxonomy term. If the URL represents a node the node id (nid) and page views are saved to a table named ga4_nid_storage. If the URL represents a taxonomy term the term id (td) and page views is saved to a table named ga4_tid_storage.

Before data is fetched the three tables are truncated to avoid updates problems.

This approach is tested on a site with approximately 10 000 nodes and taxonomy terms without any problem. If this module causes performance problems we can process the nodes in batches by using the Queue API.

