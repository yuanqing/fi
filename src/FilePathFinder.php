<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class FilePathFinder extends \ArrayIterator
{
  /**
   * @param \Iterator $iterator The iterator to filter
   * @param callable $filterCallbacks The callbacks used to filter the iterator
   */
  public function __construct($dataDir, FileParser $fileParser)
  {
    # check $dataDir
    if (!is_dir($dataDir) || !is_readable($dataDir)) {
      throw new \InvalidArgumentException(sprintf('Invalid data directory: \'%s\'', $dataDir));
    }

    $filePathIterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $dataDir,
        \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
      )
    );
    $filePaths = iterator_to_array($filePathIterator, false);

    # filter out file paths from $filePaths that do not match the format in $filePathParser
    $filterCallback = function($filePath) use ($fileParser) {
      return $fileParser->parseFilePath($filePath) !== null;
    };
    $filePaths = array_filter($filePaths, $filterCallback);

    # sort $filePaths in ascending order
    $sortCallback = function($filePath1, $filePath2) {
      return strnatcasecmp($filePath1, $filePath2);
    };
    usort($filePaths, $sortCallback);

    parent::__construct($filePaths);
  }

}
