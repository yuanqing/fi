<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use yuanqing\Fi\Fi;

# get files in $dataDir that match $filePathFormat
$dataDir = 'data';
$filePathFormat = '{{ date.year: 4d }}/{{ date.month: 2d }}/{{ date.day: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat); #=> Collection object

# iterate over the Collection of Documents
foreach ($collection as $document) {
  $document->getFilePath(); #=> 'data/2014/01/01-foo.md', ...
  $document->getField('title'); #=> 'foo title', ...
  $document->getField('date'); #=> ['year' => 2014, 'month' => 1, 'day' => 1], ...
  $document->getContent(); #=> 'foo content', ...
}

# access a Document in the Collection by index
$document = $collection->getDocument(0); #=> Document object
$document->getFilePath(); #=> 'data/2014/01/01-foo.md'
$document->getField('title'); #=> 'foo title'
$document->getField('date'); #=> ['year' => 2014, 'month' => 1, 'day' => 1]
$document->getContent(); #=> 'foo content'

# set the date to a DateTime object
$collection->map(function($document) {
  $date = DateTime::createFromFormat('Y-m-d', implode('-', $document->getField('date')));
  $document->setField('date', $date);
  return $document;
});

# filter out any Document with date 2014-01-01
$collection->filter(function($document) {
  return $document->getField('date') != DateTime::createFromFormat('Y-m-d', '2014-01-01');
});

# sort by date in descending order
$collection->sort(function($document1, $document2) {
  return $document1->getField('date') < $document2->getField('date');
});

var_dump($collection->toArr());
