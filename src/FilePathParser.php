<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
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
   * @return array|null An array of values if the $filePath matches $this->filePathFormat,
   * else returns null
   */
  public function parse($filePath)
  {
    return $this->parser->extract($filePath);
  }

}
