<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class Document
{
  private $filePath;
  private $fields;
  private $content;

  public function __construct($filePath = null, array $fields = null, $content = null)
  {
    $this->filePath = $filePath;
    $this->fields = $fields ?: array();
    $this->content = $content ?: '';
  }

  public function getFilePath()
  {
    return $this->filePath;
  }

  public function getFields()
  {
    return $this->fields;
  }

  public function hasField($fieldName)
  {
    return isset($this->fields[$fieldName]);
  }

  public function getField($fieldName)
  {
    if (!$this->hasField($fieldName)) {
      throw new \InvalidArgumentException(sprintf('Invalid field name \'%s\'', $fieldName));
    }
    return $this->fields[$fieldName];
  }

  public function setField($fieldName, $fieldVal)
  {
    $this->fields[$fieldName] = $fieldVal;
    return $this;
  }

  public function hasContent()
  {
    return $this->content !== null && $this->content !== '';
  }

  public function getContent()
  {
    return $this->content;
  }

  public function setContent($content)
  {
    $this->content = $content;
    return $this;
  }

}
