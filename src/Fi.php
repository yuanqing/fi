<?php
/**
 * Fi.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

namespace yuanqing\Fi;

class Fi
{
  const ASC = 1;
  const DESC = 2;

  public static function query($dataDir, $filePathFormat, $defaultsFileName = '_defaults.md')
  {
    # check $dataDir
    if (!is_dir($dataDir) || !is_readable($dataDir)) {
      throw new \InvalidArgumentException(sprintf('Invalid data directory: \'%s\'', $dataDir));
    }

    $dataDir = rtrim($dataDir, '/');
    $filePathFormat = $dataDir . '/' . ltrim($filePathFormat, '/');
    $defaultsFileName = ltrim($defaultsFileName, '/');

    $yamlParser = new YAMLParser;
    $filePathParser = new FilePathParser($filePathFormat);
    $fileParser = new FileParser($yamlParser, $filePathParser, $defaultsFileName);

    $filePaths = self::getFilePaths($dataDir, $defaultsFileName, $fileParser);
    $fileIterator = new FileIterator($filePaths, $fileParser);

    return new Collection($dataDir, $filePathFormat, $defaultsFileName, $fileParser,
      $fileIterator);
  }

  /**
   * Returns all files in {$fileIterator} as an array of Document objects
   *
   * @return \Iterator
   */
  private static function getFilePaths($dataDir, $defaultsFileName, $fileParser)
  {
    $fileIterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $dataDir,
        \FilesystemIterator::SKIP_DOTS
      )
    );
    $files = iterator_to_array($fileIterator, false);

    # filter out file paths from $filePaths that do not match the format in $filePathParser
    $filePaths = array();
    foreach ($files as $file) {
      $filePath = $file->getPathname();
      if (basename($filePath) !== $defaultsFileName && $fileParser->parseFilePath($filePath) !== null) {
        $filePaths[] = $filePath;
      }
    };

    # sort $filePaths in ascending order
    usort($filePaths, function($filePath1, $filePath2) {
      return strnatcasecmp($filePath1, $filePath2);
    });

    return new \ArrayIterator($filePaths);
  }

}
