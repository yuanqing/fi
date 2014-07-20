<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class Document
{
  private $filePath;
  private $filePathMeta;
  private $frontMatter;
  private $content;

  public function __construct($filePath, $filePathMeta = array(), $frontMatter = array(), $content = '')
  {
    $this->filePath = $filePath;
    $this->filePathMeta = $filePathMeta;
    $this->frontMatter = $frontMatter;
    $this->content = $content;
  }

  public function getFilePath()
  {
    return $this->filePath;
  }

  public function getFilePathMeta($fieldName)
  {
    if ($fieldName === null) {
      return $this->filePathMeta;
    }
    return @$this->filePathMeta[$fieldName];
  }

  public function hasFrontMatter($fieldName = null)
  {
    if ($fieldName === null) {
      return !empty($this->frontMatter);
    }
    return isset($this->frontMatter[$fieldName]);
  }

  public function getFrontMatter($fieldName)
  {
    if ($fieldName === null) {
      return $this->frontMatter;
    }
    return @$this->frontMatter[$fieldName];
  }

  public function hasContent()
  {
    return $this->content !== '';
  }

  public function getContent()
  {
    return $this->content;
  }

}
