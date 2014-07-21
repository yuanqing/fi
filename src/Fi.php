<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class Fi implements \Iterator
{
  const ASC = 1;
  const DESC = 2;

  private $mapCallbacks;
  private $sortCallbacks;
  private $fileParser;
  private $fileIterator;

  /**
   * @param string $dataDir The directory containing the data files
   * @param string $filePathFormat The file path format of the data files
   * @throws \InvalidArgumentException
   */
  public function __construct($dataDir, $filePathFormat)
  {
    # check $dataDir
    if (!is_dir($dataDir) || !is_readable($dataDir)) {
      throw new \InvalidArgumentException(sprintf('Invalid data directory: \'%s\'', $dataDir));
    }

    # normalise $args
    $dataDir = rtrim($dataDir, '/');
    $filePathFormat = $dataDir . '/' . ltrim($filePathFormat, '/');

    $this->mapCallbacks = array();
    $this->sortCallbacks = array();

    # dependencies
    $yamlParser = new YAMLParser;
    $filePathParser = new FilePathParser($filePathFormat);

    # get all file paths that match the given file path format
    $iterator = new FileFinder($dataDir, $filePathParser);

    # sort file paths in ascending order
    $this->fileParser = new FileParser($yamlParser, $filePathParser);
    $this->fileIterator = new FileIterator($iterator, $this->fileParser);
    $this->sort(function($document1, $document2) {
      return strnatcmp($document1->getFilePath(), $document2->getFilePath());
    });
  }

  /**
   * Returns all files in {$fileIterator} as an array of Document objects
   *
   * @return array
   */
  public function get()
  {
    return iterator_to_array($this, false);
  }

  /**
   * Adds a callback for filtering the {$fileIterator}
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * false to exclude the Document from the iterator
   * @throws \InvalidArgumentException
   */
  public function filter($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Filter callback must be callable');
    }
    $this->fileIterator->filter($callback);

    return $this;
  }

  /**
   * Adds a callback that is applied to each Document corresponding to each file in {$fileIterator}
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * an object of type Document
   * @throws \InvalidArgumentException
   */
  public function map($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Map callback must be callable');
    }
    $this->mapCallbacks[] = $callback;

    return $this;
  }

  /**
   * Adds a callback for sorting the {$fileIterator}
   */
  public function sort()
  {
    $args = func_get_args();
    if (func_num_args() == 1) {
      return call_user_func_array(array($this, 'sortUsingCallback'), $args);
    }
    return call_user_func_array(array($this, 'sortByFieldName'), $args);
  }

  /**
   * @param callable $callback Takes two arguments of type Document. The callback must return < 0
   * if the first Document argument is to be ordered before the second, else it must return > 0
   * @throws \InvalidArgumentException
   */
  private function sortUsingCallback($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Sort callback must be callable');
    }
    $this->sortCallbacks[] = $callback;

    return $this;
  }

  /**
   * @param string $fieldName The name of the field by which to sort the {$fileIterator}
   * @param int $sortOrder The order with which to sort the {$fileIterator}
   * @throws \InvalidArgumentException
   */
  private function sortByFieldName($fieldName, $sortOrder = Fi::ASC)
  {
    if ($sortOrder !== Fi::ASC && $sortOrder !== Fi::DESC) {
      throw new \InvalidArgumentException('Invalid sort order: %s', $sortOrder);
    }
    $this->sortCallbacks[] = function($filePath1, $filePath2) use ($fieldName, $sortOrder) {
      $val1 = $filePath1->getField($fieldName);
      $val2 = $filePath2->getField($fieldName);
      if (is_numeric($val1) && is_numeric($val2)) {
        return $sortOrder == Fi::ASC ? $val1 > $val2 : $val2 > $val1;
      }
      return $sortOrder == Fi::ASC ? strnatcasecmp($val1, $val2) : strnatcasecmp($val2, $val1);
    };

    return $this;
  }

  /**
   * Sorts the {$fileIterator} using the callbacks in {$sortCallbacks} before rewinding
   * the iterator
   */
  public function rewind()
  {
    foreach ($this->sortCallbacks as $callback) {
      $this->fileIterator = $this->fileIterator->sort($callback);
    }
    $this->sortCallbacks = array(); # empty the {$sortCallbacks} array
    $this->fileIterator->rewind();
  }

  /**
   * Parses the file currently pointed to by the {$fileIterator} into a Document
   *
   * @return Document
   */
  public function current()
  {
    $filePath = $this->fileIterator->current();
    $document = $this->fileParser->parse($filePath);

    # pass the $document through all the {$mapCallbacks}
    foreach ($this->mapCallbacks as $callback) {
      $document = call_user_func($callback, $document);
    }

    return $document;
  }

  public function key()
  {
    return $this->fileIterator->key();
  }

  public function next()
  {
    return $this->fileIterator->next();
  }

  public function valid()
  {
    return $this->fileIterator->valid();
  }

}
