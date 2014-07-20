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

  private $fileParser;
  private $fileIterator;
  private $sortCallbacks;

  /**
   * @param string $dataDir The directory containing the data files
   * @param string $filePathFormat The file path format of the data files
   */
  public function __construct($dataDir, $filePathFormat)
  {
    # check $dataDir
    if (!is_dir($dataDir) || !is_readable($dataDir)) {
      throw new \InvalidArgumentException(sprintf('Invalid data directory: %s', $dataDir));
    }

    # normalise $args
    $dataDir = rtrim($dataDir, '/');
    $filePathFormat = $dataDir . '/' . $filePathFormat;

    # dependencies
    $yamlParser = new YAMLParser;
    $filePathParser = new FilePathParser($filePathFormat);

    $this->fileParser = new FileParser($yamlParser, $filePathParser);
    $this->fileIterator = new FileIterator($dataDir, $this->fileParser);
    $this->sortCallbacks = array();
  }

  /**
   * Adds a callback for filtering the {$fileIterator}
   *
   * @param callable $callback
   * @throws InvalidArgumentException
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
   * Adds a callback that is applied to every element in {$fileIterator}
   *
   * @param callable $callback
   * @throws InvalidArgumentException
   */
  public function map($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Map callback must be callable');
    }
    $this->fileIterator->map($callback);

    return $this;
  }

  /**
   * Adds a callback for sorting the {$fileIterator}
   *
   */
  public function sort()
  {
    $args = func_get_args();
    if (count($args) == 1) {
      return call_user_func_array(array($this, 'sortUsingCallback'), $args);
    }
    return call_user_func_array(array($this, 'sortByFieldName'), $args);
  }

  /**
   * @param callable $callback The callback for sorting the {$fileIterator}
   * @throws InvalidArgumentException
   */
  private function sortUsingCallback($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Sort callback must be callable');
    }
    $this->sortCallbacks[] = $fieldName;

    return $this;
  }

  /**
   * @param string $fieldName The name of the field with which to sort the {$fileIterator} by
   * @param int $sortOrder The order with which to sort the {$fileIterator}
   * @throws InvalidArgumentException
   */
  private function sortByFieldName($fieldName, $sortOrder = Fi::ASC)
  {
    if ($sortOrder !== Fi::ASC && $sortOrder !== Fi::DESC) {
      throw new \InvalidArgumentException('Invalid sort order: %s', $sortOrder);
    }
    $this->sortCallbacks[] = function($file1, $file2) use ($fieldName, $sortOrder) {
      $val1 = strtolower($file1->getFrontMatter($fieldName));
      $val2 = strtolower($file2->getFrontMatter($fieldName));
      return $sortOrder == Fi::ASC ? strnatcmp($val1, $val2) : strnatcmp($val2, $val1);
    };

    return $this;
  }

  /**
   * Sorts the {$fileIterator} using the callbacks in {$sortCallbacks} before rewinding
   * the iterator
   *
   */
  public function rewind()
  {
    foreach ($this->sortCallbacks as $sortCallback) {
      $this->fileIterator = $this->fileIterator->sort($sortCallback);
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
    $filePath = $this->fileIterator->current()->getPathname();
    return $this->fileParser->parse($filePath);
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
