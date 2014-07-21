<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class FileFinder extends \FilterIterator
{
  private $filePathParser;

  /**
   * @param string $dataDir The file path to the directory containing the data files
   * @param FilePathParser $filePathParser Used to filter the files
   */
  public function __construct($dataDir, FilePathParser $filePathParser)
  {
    parent::__construct(
      new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
          $dataDir,
          \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        )
      )
    );
    $this->filePathParser = $filePathParser;
  }

  /**
   * Filters this iterator
   */
  public function accept()
  {
    if ($this->filePathParser->parse(parent::current()) === null) {
      return false;
    }
    return true;
  }

}
