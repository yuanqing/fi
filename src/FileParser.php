<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class FileParser
{
  private $yamlParser;
  private $filePathParser;

  public function __construct(YAMLParser $yamlParser, FilePathParser $filePathParser)
  {
    $this->yamlParser = $yamlParser;
    $this->filePathParser = $filePathParser;
  }

  /**
   * Parses a file into a Document object
   *
   * @param string $filePath The location of the file to parse
   * @return Document
   */
  public function parse($filePath)
  {
    $str = file_get_contents($filePath);
    $lines = explode(PHP_EOL, $str);

    if (rtrim($lines[0]) === '---') {

      unset($lines[0]);

      $i = 1;
      $yaml = array();
      foreach ($lines as $line) {
        if (rtrim($line) === '---') {
          break;
        }
        $yaml[] = $line;
        $i++;
      }

      $yaml = implode(PHP_EOL, $yaml);
      $content = trim(implode(PHP_EOL, array_slice($lines, $i)));

    } else {

      $yaml = $str;
      $content = '';

    }

    $filePathMeta = $this->parseFilePath($filePath);
    $yaml = $this->parseYAML($yaml);

    return new Document($filePath, $filePathMeta, $yaml, $content);
  }

  /**
   * Parses a file path
   *
   * @param string $filePath
   * @return array The fields extracted from the file path
   */
  public function parseFilePath($filePath)
  {
    return $this->filePathParser->parse($filePath);
  }

  /**
   * Parses a raw YAML string
   *
   * @param string $yamlStr
   * @return array The fields in the YAML
   */
  public function parseYAML($yamlStr)
  {
    return $this->yamlParser->parse($yamlStr);
  }

}
