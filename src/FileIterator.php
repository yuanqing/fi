<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class FileIterator extends \FilterIterator
{
  private $fileParser;
  private $filterCallbacks;

  /**
   * @param string|ArrayIterator $dataDir The file path to the directory containing the
   * data files, or an ArrayIterator over the data files
   * @param FileParser $fileParser
   */
  public function __construct($dataDir, FileParser $fileParser)
  {
    if ($dataDir instanceof \ArrayIterator) {
      parent::__construct($dataDir);
    } else {
      parent::__construct(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dataDir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS)));
    }
    $this->fileParser = $fileParser;
    $this->filterCallbacks = array();
  }

  /**
   * Adds a callback for filtering this iterator
   *
   * @param callable $callback Takes a single argument of type Document. The callback must return * false to exclude the Document from the iterator
   */
  public function filter($callback)
  {
    $this->filterCallbacks[] = $callback;
  }

  /**
   * Filters this iterator on-the-fly (ie. while iterating) using all the callbacks in the
   * {$callbacks} array
   *
   */
  public function accept()
  {
    $filePath = parent::current()->getPathname();
    if ($this->fileParser->parseFilePath($filePath) === null) {
      return false;
    }
    foreach ($this->filterCallbacks as $callback) {
      if (call_user_func($callback, $this->fileParser->parse($filePath)) === false) {
        return false;
      }
    }
    return true;
  }

  /**
   * Sorts this iterator using the given callback, and returns a new instance of the iterator
   *
   * @param callable $callback Takes two arguments of type Document. The callback must return < 0
   * if the first Document argument is to be ordered before the second, else it must return > 0
   */
  public function sort($callback)
  {
    $arr = iterator_to_array($this, true);
    $fileParser = $this->fileParser;
    uasort($arr, function($f1, $f2) use ($callback, $fileParser) {
      return call_user_func(
        $callback,
        $fileParser->parse($f1->getPathname()),
        $fileParser->parse($f2->getPathname())
      );
    });
    return new $this(new \ArrayIterator($arr), $this->fileParser);
  }

}
