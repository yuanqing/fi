<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

use yuanqing\Extract\Extract;

class FilePathParser
{
  private $parser;

  public function __construct($filePathFormat)
  {
    $this->parser = new Extract($filePathFormat);
  }

  /**
   * Extracts values from a file path by matching it against {$filePathFormat}
   *
   * @param string $filePath The file path to extract values from
   * @return null|array An array of values if the $filePath matches $this->filePathFormat,
   *   else returns null
   */
  public function parse($filePath)
  {
    return $this->parser->extract($filePath);
  }

}
