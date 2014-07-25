<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
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

  /**
   * @param string $dataDir The directory containing the data files
   * @param string $filePathFormat The file path format of the data files (does not include the
   * data directory prefix)
   * @param string $defaultsFileName The file name of the defaults file
   */
  public static function query($dataDir, $filePathFormat, $defaultsFileName = '_defaults.md')
  {
    # check $dataDir
    if (!is_dir($dataDir) || !is_readable($dataDir)) {
      throw new \InvalidArgumentException(sprintf('Invalid data directory: \'%s\'', $dataDir));
    }

    # normalise args
    $dataDir = rtrim($dataDir, '/');
    $filePathFormat = $dataDir . '/' . ltrim($filePathFormat, '/');
    $defaultsFileName = trim($defaultsFileName, '/');

    $yamlParser = new YAMLParser;
    $filePathParser = new FilePathParser($filePathFormat);
    $fileParser = new FileParser($yamlParser, $filePathParser, $defaultsFileName);
    $filePaths = self::getFilePaths($dataDir, $defaultsFileName, $filePathParser);
    return new Collection($dataDir, $filePathFormat, $defaultsFileName, $filePaths, $fileParser);
  }

  /**
   * Get all the file paths in $dataDir that match the file path format required by $filePathParser
   *
   * @param string $dataDir
   * @param string $defaultsFileName
   * @param FilePathParser $filePathParser
   */
  private static function getFilePaths($dataDir, $defaultsFileName, FilePathParser $filePathParser)
  {
    # get all file paths in $dataDir
    $fileIterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $dataDir,
        \FilesystemIterator::SKIP_DOTS
      )
    );
    $files = iterator_to_array($fileIterator, false); # discard keys

    # filter out file paths that do not match the format required by $filePathParser
    $filePaths = array();
    $defaultFilePaths = array();
    foreach ($files as $file) {
      $filePath = $file->getPathname();
      if ($file->getBasename() !== $defaultsFileName && $filePathParser->parse($filePath) !== null) {
        $filePaths[] = $filePath;
      }
    };

    # sort $filePaths in ascending order
    usort($filePaths, function($filePath1, $filePath2) {
      return strnatcasecmp($filePath1, $filePath2);
    });

    return $filePaths;
  }

}
