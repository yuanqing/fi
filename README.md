# Fi.php [![Packagist Version](http://img.shields.io/packagist/v/yuanqing/fi.svg)](https://packagist.org/packages/yuanqing/fi) [![Build Status](https://img.shields.io/travis/yuanqing/fi.svg)](https://travis-ci.org/yuanqing/fi) [![Coverage Status](https://img.shields.io/coveralls/yuanqing/fi.svg)](https://coveralls.io/r/yuanqing/fi)

## API

Fi.php is currently in active development; this API is still subject to change.

-

### Fi

#### Collection Fi::query ( string $dataDir, string $filePathFormat [, string $defaultsFileName ] )

Factory method that makes a Collection object.

```php
$dataDir = './data';
$filePathFormat = '{{ year: 4d }}/{{ month: 2d }}/{{ title: s }}.md';
$collection = Fi::query($dataDir, $filePathFormat);
```

-

### Collection

All other methods apart from `toArr` return the original Collection instance. This means that we can chain method calls like so:

```php
$collection
  ->filter($filterCallback)
  ->map($mapCallback)
  ->sort($sortCallback)
  ->toArr();
```

#### Collection filter ( callable $callback )

The `$callback` takes a single argument of type Document. Return `false` to * exclude* the Document from the Collection.

```php
# excludes Documents with the title 'foo'
$callback = function(Document $document) {
  return $document->getField('title') !== 'foo';
};
$collection->filter($callback);
```

#### Collection map ( callable $callback )

Applies the `$callback` to each Document in the Collection. The `$callback` takes a single argument of type Document, and must return an object of type Document.

```php
# sets the title of all Documents to 'foo'
$callback = function(Document $document) {
  $document->setField('title', 'bar');
  return $document;
};
$collection->map($callback);
```

#### Collection sort ( callable $callback )

Sorts the Collection using the `$callback`, which takes two arguments of type Document. Return `1` if the first Document argument is to be ordered before the second, else return `-1`.

```php
# sorts by Document content in ascending order
$callback = function(Document $document1, Document $document2) {
  $content1 = $document1->getContent();
  $content2 = $document2->getContent();
  return strnatcasecmp($content1, $content2);
};
$collection->sort($callback);
```

#### Collection sort ( mixed $fieldName [, int $sortOrder = Fi::ASC ] )

Sorts the Collection by the field with `$fieldName` in the specified `$sortOrder`.

```php
# sorts by title in ascending order
$collection->sort('title');
$collection->sort('title', Fi::ASC);

# sorts by title in descending order
$collection->sort('title', Fi::DESC);
```

#### array toArr ( )

Gets all the Documents in the Collection as an array.

```php
$collection->toArr();
```

-

### Document

#### string getFilePath ( )

Gets the file path of the file (relative to the `$dataDir`) corresponding to the Document.

```php
$document->getFilePath(); #=> 'data/2014/01/foo.md'
```

#### array getFields ( )

Gets all the fields of the Document.

```php
$document->getFields(); #=> ['year' => 2014, 'month' => 1, 'title' => 'foo']
```

#### bool hasField ( mixed $fieldName )

Checks if the Document has a field with the specified `$fieldName`.

```php
$document->hasField('description'); #=> false
```

#### mixed getField ( mixed $fieldName )

Gets the field corresponding to the specified `$fieldName`.

```php
$document->getField('title'); #=> 'foo'
```

#### Document setField ( mixed $fieldName, mixed $fieldValue )

Sets the field with `$fieldName` to the specified `$fieldValue`.

```php
$document->setField('title', 'bar'); #=> Document
```

#### bool hasContent ( )

Checks if the Document content is non-empty.

```php
$document->hasContent(); #=> true
```

#### string getContent ( )

Get the Document content.

```php
$document->getContent(); #=> 'foo'
```

#### Document setContent ( string $content )

Sets the Document content to the specified `$content`.

```php
$document->setContent('bar'); #=> Document
```

-

## Requirements

Fi.php requires at least **PHP 5.3**, or **HHVM**.

## Install with Composer

1. Install [Composer](http://getcomposer.org/).

2. Install [the Fi.php Composer package](https://packagist.org/packages/yuanqing/fi):

    ```
    $ composer require yuanqing/fi ~0.1
    ```

3. In your PHP, require the Composer autoloader:

    ```php
    require_once __DIR__ . '/vendor/autoload.php';
    ```

## Testing

You need [PHPUnit](http://phpunit.de/) to run the tests:

```
$ git clone https://github.com/yuanqing/fi
$ cd fi
$ phpunit
```

## License

MIT license
