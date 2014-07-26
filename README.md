# Fi.php [![Packagist Version](http://img.shields.io/packagist/v/yuanqing/fi.svg)](https://packagist.org/packages/yuanqing/fi) [![Build Status](https://img.shields.io/travis/yuanqing/fi.svg)](https://travis-ci.org/yuanqing/fi) [![Coverage Status](https://img.shields.io/coveralls/yuanqing/fi.svg)](https://coveralls.io/r/yuanqing/fi)

Fi lets you query a collection of text files, as if the folder of files were a database (well, almost).

Fi (rhymes with *pie*) is designed to be used as part of a [static site generator](http://staticsitegenerators.net/).

## Super quick start

There is [a documented, runnable example](https://github.com/yuanqing/fi/blob/master/example) you can play with:

```
$ git clone https://github.com/yuanqing/fi
$ cd fi
$ composer install
$ php example/example.php
```

There are also [tests](https://github.com/yuanqing/fi/blob/master/test/FiTest.php).

## Quick start

Suppose we have organised our text files into neat date-based folders like so:

```
data/
|-- _defaults.md
`-- 2014/
    |-- 01/
    |   |-- _defaults.md
    |   |-- 01-foo.md
    |   |-- 02-bar.md
    |   `-- ...
    |-- 02/
    |   `-- ...
    `-- ...
```

Each text file contains [YAML frontmatter](http://jekyllrb.com/docs/frontmatter/) and content. The file `01-foo.md` might be something like:

```
---
title: foo title
---
foo content
```

We would query our `data` directory like so:

```php
$dataDir = 'data';
$filePathFormat = '{{ date.year: 4d }}/{{ date.month: 2d }}/{{ date.day: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat); #=> Collection object
```

Every file that matches the given `$filePathFormat` is a **Document**. A **Collection**, then, is simply an [Iterator](http://php.net/manual/en/class.iterator.php) over a set of Documents:

```php
foreach ($collection as $document) {
  $document->getFilePath(); #=> 'data/2014/01/01-foo.md', ...
  $document->getField('title'); #=> 'foo title', ...
  $document->getField('date'); #=> ['year' => 2014, 'month' => 1, 'day' => 1], ...
  $document->getContent(); #=> 'foo content', ...
}
```

We can also access a Document directly by index:

```php
$document = $collection->getDocument(0); #=> Document object
$document->getFilePath(); #=> 'data/2014/01/01-foo.md'
$document->getField('title'); #=> 'foo title'
$document->getField('date'); #=> ['year' => 2014, 'month' => 1, 'day' => 1]
$document->getContent(); #=> 'foo content'
```

### Map, filter, sort

Fi also supports **map**, **filter**, and **sort** operations over our Collection of Documents:

```php
# set the date to a DateTime object
$collection->map(function($document) {
  $date = DateTime::createFromFormat('Y-m-d', implode('-', $document->getField('date')));
  return $document->setField('date', $date);
});

# filter out Documents with date 2014-01-01
$collection->filter(function($document) {
  return $document->getField('date') != DateTime::createFromFormat('Y-m-d', '2014-01-01');
});

# sort by date in descending order
$collection->sort(function($document1, $document2) {
  return $document1->getField('date') < $document2->getField('date');
});
```

### Default values

A text file will inherit default values (for fields or content) from any `_defaults.md` file found in the same directory, or in a parent directory. Defaults are said to **cascade**; `_defaults.md` files found further down the file hierarchy will *overwrite* those higher up the hierarchy.

## API

### Fi

#### Fi::query ( string $dataDir, string $filePathFormat [, string $defaultsFileName = '_defaults.md' ] )

Makes a Collection object.

```php
$dataDir = './data';
$filePathFormat = '{{ year: 4d }}/{{ month: 2d }}/{{ date: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat);
```

- `$dataDir` is the directory where Fi will look for text files that match the `$filePathFormat`.
- `$filePathFormat` is specified using a Regex-like syntax; see [Extract.php](https://github.com/yuanqing/extract).
- `$defaultsFileName` is the name of the text file that Fi will look for when resolving defaults.

-

### Collection

#### map ( callable $callback )

Applies the `$callback` to each Document in the Collection. Returns the Collection object.

```php
# sets the title of all Documents to 'foo'
$collection->map(function($document) {
  $document->setField('title', 'foo');
  return $document;
}); #=> Collection
```

- `$callback` takes a single argument of type Document. It must return an object of type Document.

#### filter ( callable $callback )

Filter out Documents in the Collection using the `$callback`. Returns the Collection object.

```php
# filters out Documents with the title 'foo'
$collection->filter(function($document) {
  return $document->getField('title') !== 'foo';
}); #=> Collection
```

- `$callback` takes a single argument of type Document. Return false to *exclude* that Document from the Collection.

#### sort ( callable $callback )

Sorts the Collection using the `$callback`. Returns the Collection object.

```php
# sorts by title in ascending order
$collection->sort(function($document1, $document2) {
  return strnatcasecmp($document1->getField('title'), $document2->getField('title'));
}); #=> Collection
```

- `$callback` takes two arguments of type Document. Return `1` if the first Document argument is to be ordered before the second, else return `-1`.

#### sort ( mixed $fieldName [, int $sortOrder = Fi::ASC ] )

Sorts the Collection by the `$fieldName` in the specified `$sortOrder`. Returns the Collection object.

```php
# sorts by title in ascending order
$collection->sort('title', Fi::ASC); #=> Collection

# sorts by title in descending order
$collection->sort('title', Fi::DESC); #=> Collection
```

- `$sortOrder` must be either `Fi::ASC` or `Fi::DESC`.

#### toArr ( )

Gets all the Documents in the Collection as an array.

```php
$collection->toArr(); #=> [Document, Document, ...]
```

-

### Document

#### getFilePath ( )

Gets the file path of the text file (relative to the `$dataDir`) that corresponds to the Document.

```php
$document->getFilePath(); #=> 'data/2014/01/01-foo.md'
```

#### getFields ( )

Gets all the fields of the Document.

```php
$document->getFields(); #=> ['title' => 'foo', 'date' => ['year' => 2014, 'month' => 1, 'day' => 1]]
```

#### hasField ( mixed $fieldName )

Checks if the Document has a field with the specified `$fieldName`.

```php
$document->hasField('title'); #=> true
```

#### getField ( mixed $fieldName )

Gets the value of the specified `$fieldName`.

```php
$document->getField('title'); #=> 'foo'
```

#### setField ( mixed $fieldName, mixed $fieldValue )

Sets the field with `$fieldName` to the specified `$fieldValue`. Returns the Document object.

```php
$document->setField('title', 'bar'); #=> Document
```

#### hasContent ( )

Checks if the Document content is non-empty.

```php
$document->hasContent(); #=> true
```

#### getContent ( )

Gets the Document content.

```php
$document->getContent(); #=> 'bar'
```

#### setContent ( string $content )

Sets the Document content to the specified `$content`. Returns the Document object.

```php
$document->setContent('baz'); #=> Document
```

-

## Requirements

Fi requires at least **PHP 5.3** or **HHVM**, and [Composer](http://getcomposer.org/).

## Installation

1. Install [Composer](http://getcomposer.org/).

2. Install [the Composer package](https://packagist.org/packages/yuanqing/fi):

    ```
    $ composer require yuanqing/fi ~0.1
    ```

3. In your PHP file, require the Composer autoloader:

    ```php
    require_once __DIR__ . '/vendor/autoload.php';
    ```

## Testing

You need [PHPUnit](http://phpunit.de/) to run the tests:

```
$ git clone https://github.com/yuanqing/fi
$ cd fi
$ composer install
$ phpunit
```

## License

[MIT license](https://github.com/yuanqing/fi/blob/master/LICENSE)
