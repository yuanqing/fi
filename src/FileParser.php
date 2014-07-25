<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class FileParser
{
  private $dataDir;
  private $defaultsFileName;
  private $yamlParser;
  private $filePathParser;
  private $mapCallbacks;

  public function __construct($dataDir, $defaultsFileName, YAMLParser $yamlParser, FilePathParser $filePathParser)
  {
    $this->dataDir = $dataDir;
    $this->defaultsFileName = $defaultsFileName;
    $this->yamlParser = $yamlParser;
    $this->filePathParser = $filePathParser;
    $this->mapCallbacks = array();
  }

  /**
   * Makes a Document from the file at $filePath.
   *
   * @throws \OutOfBoundsException
   * @return Document
   */
  public function makeDocument($filePath)
  {
    $document = $this->parseFile($filePath);
    $defaults = $this->resolveDefaults($filePath);

    # merge $document into $defaults
    $document[0] = array_merge($defaults[0], $document[0]);
    if ($document[1] === '') {
      $document[1] = $defaults[1];
    }

    # construct the object
    $document = new Document($filePath, $document[0], $document[1]);

    # pass the $document through {$mapCallbacks}
    foreach ($this->mapCallbacks as $callback) {
      $result = call_user_func($callback, $document);
      if ($result instanceof Document) {
        $document = $result;
      }
    }
    return $document;
  }

  /**
   * Adds $callback to {$mapCallbacks}.
   *
   * @param callable $callback
   * @return FileParser
   */
  public function addMapCallback($callback)
  {
    $this->mapCallbacks[] = $callback;
    return $this;
  }

  /**
   * Parses the file at $filePath into a Document object.
   *
   * @param string $filePath The location of the file to parse.
   * @return Document
   */
  private function parseFile($filePath, $isDefaultsFile = false)
  {
    if (!is_file($filePath)) {
      return array(array(), '');
    }

    $str = trim(file_get_contents($filePath));
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
      $content = implode(PHP_EOL, array_slice($lines, $i));

    } else {

      $yaml = '';
      $content = $str;

    }

    $filePathFields = $isDefaultsFile ? array() : $this->filePathParser->parse($filePath);
    $yaml = $this->yamlParser->parse($yaml) ?: array();

    return array(array_merge($filePathFields, $yaml), trim($content));
  }

  /**
   * Returns an array containing the default fields and default content of the Document
   * at $filePath.
   *
   * @return array
   */
  private function resolveDefaults($filePath)
  {
    # get the directory names in the path from {$dataDir} to $filePath.
    # if $filePath = '/foo/bar/baz/qux/quux.md' and $this->dataDir = '/foo/bar',
    # then $dirNames = ['baz', 'qux']
    $dirNames = explode('/', dirname(ltrim($filePath, $this->dataDir)));

    # parse the defaults file in {$dataDir}
    $currDirPath = $this->dataDir;
    $defaults = $this->parseFile($currDirPath . '/' . $this->defaultsFileName, true);

    # parse and cascade defaults on the path from {$dataDir} to $filePath
    foreach ($dirNames as $dirName) {
      $currDirPath .= '/' . $dirName;
      # $currDefaults override $defaults
      $currDefaults = $this->parseFile($currDirPath . '/' . $this->defaultsFileName, true);
      $defaults[0] = array_merge($defaults[0], $currDefaults[0]);
      if ($currDefaults[1] !== '') {
        $defaults[1] = $currDefaults[1];
      }
    }
    return $defaults;
  }

}
