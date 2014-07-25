<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

use Symfony\Component\Yaml\Parser;

class YAMLParser
{
  private $parser;

  public function __construct()
  {
    $this->parser = new Parser;
  }

  /**
   * Parses a raw YAML string.
   *
   * @param string $yamlStr
   * @return array The fields in the YAML.
   */
  public function parse($yamlStr)
  {
    return $this->parser->parse($yamlStr);
  }

}

