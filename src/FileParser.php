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
  private $frontMatterParser;
  private $filePathParser;

  public function __construct(YAMLParser $frontMatterParser, FilePathParser $filePathParser)
  {
    $this->yamlParser = $frontMatterParser;
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
    $str = trim(file_get_contents($filePath));
    $lines = explode(PHP_EOL, $str);

    if (rtrim($lines[0]) === '---') {

      unset($lines[0]);

      $i = 1;
      $frontMatter = array();
      foreach ($lines as $line) {
        if (rtrim($line) === '---') {
          break;
        }
        $frontMatter[] = $line;
        $i++;
      }

      $frontMatter = implode(PHP_EOL, $frontMatter);
      $content = trim(implode(PHP_EOL, array_slice($lines, $i)));

    } else {

      $frontMatter = '';
      $content = trim($str);

    }

    $filePathMeta = $this->parseFilePath($filePath);
    $frontMatter = $this->parseYAML($frontMatter) ?: array();

    return new Document($filePath, array_merge($filePathMeta, $frontMatter), $content);
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
