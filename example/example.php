<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use yuanqing\Fi\Fi;

$dataDir = 'data';
$format = '{{ date.year: 4d }}/{{ date.month: 2d }}/{{ date.day: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $format);

# iterating over a Collection
foreach ($collection as $document) {
  # ... do stuff with $document ...
}

# access a Document in the Collection by index
$document = $collection->getDocument(0); #=> Document object
$document->getField('title'); #=> 'foo'
$document->getField('date'); #=> ['year' => 2014, 'month' => 1, 'day' => 1 ]
$document->getContent(); #=> 'foo'

# set date to a DateTime object
$collection->map(function($document) {
  $date = DateTime::createFromFormat('Y-m-d', implode('-', $document->getField('date')));
  return $document->setField('date', $date);
});

# sort by date in descending order
$collection->sort(function($document1, $document2) {
  return $document1->getField('date') < $document2->getField('date');
});

# exclude Documents with date 2014-01-01
$collection->filter(function($document) {
  return $document->getField('date') != DateTime::createFromFormat('Y-m-d', '2014-01-01');
});

$collection->toArr();
