# Fi.php [![Packagist Version](http://img.shields.io/packagist/v/yuanqing/fi.svg)](https://packagist.org/packages/yuanqing/fi) [![Build Status](https://img.shields.io/travis/yuanqing/fi.svg)](https://travis-ci.org/yuanqing/fi) [![Coverage Status](https://img.shields.io/coveralls/yuanqing/fi.svg)](https://coveralls.io/r/yuanqing/fi)

Fi (rhymes with *pie*) lets you query a collection of text files, as if the collection were a database.

Fi is designed to be used as part of a [static site generator](http://staticsitegenerators.net/).

## Usage

Suppose that our text files are organised into date-based directories like so:

```
data/
`-- 2014/
    |-- 01/
    |   |-- 01-foo.md
    |   |-- 02-bar.md
    |   |-- 03-baz.md
    |   `-- ...
    |-- 02/
    |   `-- ...
    `-- ...
```

Each text file would have some [YAML frontmatter](http://jekyllrb.com/docs/frontmatter/) and content:

```
---
title: foo
---
bar
```

With Fi, we can quickly grab data from our directory:

```php
$dataDir = './data';
$filePathFormat = '{{ date.year: 4d }}/{{ date.month: 2d }}/{{ date.day: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat); #=> Collection object
```

`$filePathFormat` is specified using a simple quasi-Regex syntax; see [Extract.php](https://github.com/yuanqing/extract).

### Document

Every file that matches the `$filePathFormat` is a **Document**. A Document consists of:

1. A file path
2. Fields (ie. file path metadata + YAML front matter)
3. Content

It is the `$filePathFormat` that specifies the information that is to be extracted from the file path.

### Collection

A **Collection** is an [Iterator](http://php.net/manual/en/class.iterator.php) over a set of Documents:

```php
foreach ($collection as $document) {
  # ... do stuff with $document ...
}
```

Alternatively, we can access a Document in a Collection by its index:

```php
$document = $collection->getDocument(0); #=> Document object
```

### Map, filter, sort


With Fi, we can easily perform any number of **map**, **filter**, or **sort** operations over the Collection:

```php
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
```

### Cascading defaults

Each Document's fields and content can inherit default values. Simply place a `_defaults.md` file in the same directory or in a parent directory of your text files.

Suppose our `data` directory is as follows:
```
data/
|-- _defaults.md
`-- 2014/
    |-- _defaults.md
    |-- 01/
    |   |-- _defaults.md
    |   |-- 01-foo.md
    |   |-- 02-bar.md
    |   |-- 03-baz.md
    |   `-- ...
    |-- 02/
    |   `-- ...
    `-- ...
```
Default files further down the directory hierarchy will take precedence.

## API

### Fi

#### *Collection* Fi::query ( string $dataDir, string $filePathFormat [, string $defaultsFileName = '_defaults.md' ] )

Factory method to make a Collection object. `$defaultsFileName` is the name of the file that Fi will look for when resolving default values for a given Document.

```php
$dataDir = './data';
$filePathFormat = '{{ year: 4d }}/{{ month: 2d }}/{{ date: 2d }}-{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat);
```

-

### Collection

#### *Collection* filter ( callable $callback )

The `$callback` takes a single argument of type Document. Return `false` to * exclude* the Document from the Collection.

```php
# excludes Documents with the title 'foo'
$callback = function($document) {
  return $document->getField('title') !== 'foo';
};
$collection->filter($callback);
```

#### *Collection* map ( callable $callback )

Applies the `$callback` to each Document in the Collection. The `$callback` takes a single argument of type Document, and must return an object of type Document.

```php
# sets the title of all Documents to 'foo'
$callback = function($document) {
  $document->setField('title', 'bar');
  return $document;
};
$collection->map($callback);
```

#### *Collection* sort ( callable $callback )

Sorts the Collection using the `$callback`, which takes two arguments of type Document. Return `1` if the first Document argument is to be ordered before the second, else return `-1`.

```php
# sorts by Document content in ascending order
$callback = function($document1, $document2) {
  $content1 = $document1->getContent();
  $content2 = $document2->getContent();
  return strnatcasecmp($content1, $content2);
};
$collection->sort($callback);
```

#### *Collection* sort ( mixed $fieldName [, int $sortOrder = Fi::ASC ] )

Sorts the Collection by the field with `$fieldName` in the specified `$sortOrder`.

```php
# sorts by title in ascending order
$collection->sort('title');
$collection->sort('title', Fi::ASC);

# sorts by title in descending order
$collection->sort('title', Fi::DESC);
```

#### *array* toArr ( )

Gets all the Documents in the Collection as an array.

```php
$collection->toArr(); #=> [ Document object, Document object, ... ]
```

-

### Document

#### *string* getFilePath ( )

Gets the file path of the file (relative to the `$dataDir`) corresponding to the Document.

```php
$document->getFilePath(); #=> 'data/2014/01/foo.md'
```

#### *array* getFields ( )

Gets all the fields of the Document.

```php
$document->getFields(); #=> ['year' => 2014, 'month' => 1, 'title' => 'foo']
```

#### *bool* hasField ( mixed $fieldName )

Checks if the Document has a field with the specified `$fieldName`.

```php
$document->hasField('title'); #=> true
```

#### *mixed* getField ( mixed $fieldName )

Gets the field corresponding to the specified `$fieldName`.

```php
$document->getField('title'); #=> 'foo'
```

#### *Document* setField ( mixed $fieldName, mixed $fieldValue )

Sets the field with `$fieldName` to the specified `$fieldValue`.

```php
$document->setField('title', 'bar');
```

#### *bool* hasContent ( )

Checks if the Document content is non-empty.

```php
$document->hasContent(); #=> true
```

#### *string* getContent ( )

Gets the Document content.

```php
$document->getContent(); #=> 'foo'
```

#### *Document* setContent ( string $content )

Sets the Document content to the specified `$content`.

```php
$document->setContent('bar');
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

MIT license
