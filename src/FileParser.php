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
  private $defaultsFileName;

  public function __construct(YAMLParser $yamlParser, FilePathParser $filePathParser, $defaultsFileName)
  {
    $this->yamlParser = $yamlParser;
    $this->filePathParser = $filePathParser;
    $this->defaultsFileName = $defaultsFileName;
  }

  /**
   * Parses a file into a Document object
   *
   * @param string $filePath The location of the file to parse
   * @return Document
   */
  public function parse($filePath)
  {
    $filePathMeta = $this->parseFilePath($filePath);
    $document = $this->parseFile($filePath);
    $document[0] = array_merge($filePathMeta, $document[0]);

    $defaultsFilePath = dirname($filePath) . '/' . $this->defaultsFileName;
    if (is_file($defaultsFilePath)) {
      $defaults = $this->parseFile($defaultsFilePath);
      $document[0] = array_merge($defaults[0], $document[0]);
      $document[1] = $document[1] ?: $defaults[1];
    }
    return new Document($filePath, $document[0], $document[1]);
  }

  private function parseFile($filePath)
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

    $frontMatter = $this->parseYAML($frontMatter) ?: array();

    return array($frontMatter, $content);
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
