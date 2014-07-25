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
   * @param string $dataDir
   * @param string $filePathFormat
   * @param string $defaultsFileName
   * @param array $filePaths The file paths of all the Documents in this Collection
   * @param FileParser $fileParser
   */
  public function __construct($dataDir, $filePathFormat, $defaultsFileName, array $filePaths, FileParser $fileParser)
  {
    $this->dataDir = $dataDir;
    $this->filePathFormat = $filePathFormat;
    $this->defaultsFileName = $defaultsFileName;
    $this->filePaths = $filePaths;
    $this->fileParser = $fileParser;
    $this->mapCallbacks = array();
  }

  /**
   * Gets the Documents corresponding to all the file paths in the {$filePaths} array
   *
   * @return array An array of Document objects
   */
  public function toArr()
  {
    return iterator_to_array($this, false);
  }

  /**
   * Gets the Document corresponding to the file path at the given $index of the {$filePaths} array
   *
   * @param int $index
   * @return Document
   */
  public function getDocument($index)
  {
    return isset($this->filePaths[$index]) ? $this->resolveDocument($this->filePaths[$index]) : null;
  }

  /**
   * Adds a $callback to be applied to each Document corresponding to each file path
   * in {$filePaths}
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
   * Filters {$filePaths} using the given $callback
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * false to exclude the Document from the Collection
   * @throws \InvalidArgumentException
   */
  public function filter($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Filter callback must be callable');
    }
    $self = $this;
    $this->filePaths = array_filter($this->filePaths, function($filePath) use ($self, $callback) {
      return call_user_func($callback, $self->resolveDocument($filePath)) !== false;
    });

    return $this;
  }

  /**
   * Sorts the {$filePaths} array using the given callback, or based on the given field name
   * and sort order
   */
  public function sort()
  {
    if (func_num_args() == 1) {
      return $this->applySort(func_get_arg(0));
    }
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
    return $this->applySort($callback);
  }

  /**
   * Helper method that applies a sort on the {$filePaths} array using the $callback
   */
  private function applySort($callback)
  {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Sort callback must be callable');
    }
    $fileParser = $this->fileParser;
    usort($this->filePaths, function($filePath1, $filePath2) use ($callback, $fileParser) {
      return call_user_func($callback, $this->resolveDocument($filePath1), $this->resolveDocument($filePath2));
    });
    return $this;
  }

  /**
   * Returns a Document object corresponding to the current file path in {$filePaths}
   */
  public function current()
  {
    return $this->resolveDocument(current($this->filePaths));
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

  /**
   * Returns a Document object corresponding to the $filePath
   */
  private function resolveDocument($filePath)
  {
    $document = $this->fileParser->parse($filePath);
    $defaults = $this->resolveDefaults($filePath);

    # merge $document under $defaults
    $document->mergeUnder($defaults);

    # pass the $document through all the {$mapCallbacks}
    foreach ($this->mapCallbacks as $callback) {
      $result = call_user_func($callback, $document);
      if ($result instanceof Document) {
        $document = $result;
      }
    }

    return $document;
  }

  /**
   * Returns a Document object with the default values for the Document at the given $filePath
   */
  private function resolveDefaults($filePath)
  {
    # get the directory names in the path from {$dataDir} to $filePath. if $filePath =
    # '/foo/bar/baz/qux/quux.md' and $this->dataDir = '/foo/bar', then $dirNames = ['baz', 'qux']
    $dirNames = explode('/', dirname(ltrim($filePath, $this->dataDir)));

    # parse and cascade defaults; defaults files nearer to $filePath takes precedence
    $currDirPath = $this->dataDir;
    $defaults = $this->fileParser->parse($currDirPath . '/' . $this->defaultsFileName);
    foreach ($dirNames as $dirName) {
      $currDirPath .= '/' . $dirName;
      $defaults->mergeOver($this->fileParser->parse($currDirPath . '/' . $this->defaultsFileName));
    }

    return $defaults;
  }

}
