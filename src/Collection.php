<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class Collection implements \Iterator
{
  /**
   * @param array $filePaths The file paths of the Documents in this Collection.
   * @param FileParser $fileParser
   */
  public function __construct(array $filePaths, FileParser $fileParser)
  {
    $this->filePaths = $filePaths;
    $this->fileParser = $fileParser;
  }

  /**
   * Gets the Documents corresponding to all the file paths in the {$filePaths} array.
   *
   * @return array An array of Document objects
   */
  public function toArr()
  {
    return iterator_to_array($this, false);
  }

  /**
   * Gets the Document corresponding to the file path at the given $index of the
   * {$filePaths} array.
   *
   * @param int $index
   * @throws \OutOfBoundsException
   * @return Document
   */
  public function getDocument($index)
  {
    if (!isset($this->filePaths[$index])) {
      throw new \OutOfBoundsException('Invalid index');
    }
    return $this->fileParser->makeDocument($this->filePaths[$index]);
  }

  /**
   * Adds $callback to {$fileParser}, to be applied to the Document corresponding to each file
   * path in the {$filePaths} array.
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * an object of type Document.
   * @throws \InvalidArgumentException
   * @return Collection
   */
  public function map($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Map callback must be callable');
    }
    $this->fileParser->addMapCallback($callback);
    return $this;
  }

  /**
   * Filters {$filePaths} using $callback.
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * false to exclude the Document from the Collection.
   * @throws \InvalidArgumentException
   * @return Collection
   */
  public function filter($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Filter callback must be callable');
    }
    $fileParser = $this->fileParser;
    $this->filePaths = array_filter($this->filePaths, function($filePath) use ($callback, $fileParser) {
      return call_user_func($callback, $fileParser->makeDocument($filePath)) !== false;
    });
    return $this;
  }

  /**
   * Sorts {$filePaths} using using $callback, or based on the given field name and sort order.
   *
   * @param callable $callback Takes two arguments of type Document. Return 1 if the first
   * Document argument is to be ordered before the second, else return -1.
   * @throws \InvalidArgumentException
   * @return Collection
   */
  public function sort($callback)
  {
    if (func_num_args() == 2) {
      $fieldName = func_get_arg(0);
      $sortOrder = func_get_arg(1);
      if ($sortOrder !== Fi::ASC && $sortOrder !== Fi::DESC) {
        throw new \InvalidArgumentException(sprintf('Invalid sort order: %s', $sortOrder));
      }
      $callback = function($filePath1, $filePath2) use ($fieldName, $sortOrder) {
        $fieldVal1 = $filePath1->getField($fieldName);
        $fieldVal2 = $filePath2->getField($fieldName);
        if (is_numeric($fieldVal1) && is_numeric($fieldVal2)) {
          return $sortOrder == Fi::ASC ? $fieldVal1 > $fieldVal2 : $fieldVal2 > $fieldVal1;
        }
        return $sortOrder == Fi::ASC ? strnatcasecmp($fieldVal1, $fieldVal2) : strnatcasecmp($fieldVal2, $fieldVal1);
      };
    }
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Sort callback must be callable');
    }
    $fileParser = $this->fileParser;
    usort($this->filePaths, function($filePath1, $filePath2) use ($callback, $fileParser) {
      return call_user_func($callback, $fileParser->makeDocument($filePath1), $fileParser->makeDocument($filePath2));
    });
    return $this;
  }

  /**
   * Returns a Document object corresponding to the file path currently being pointed to
   * in {$filePaths}.
   *
   * @return Document
   */
  public function current()
  {
    return $this->fileParser->makeDocument(current($this->filePaths));
  }

  public function rewind()
  {
    reset($this->filePaths);
  }

  public function key()
  {
    return key($this->filePaths);
  }

  public function next()
  {
    return next($this->filePaths);
  }

  public function valid()
  {
    return key($this->filePaths) !== null;
  }

}
