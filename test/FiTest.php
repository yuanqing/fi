<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link http://github.com/yuanqing/X.php
 */

use yuanqing\Fi\Fi;
use yuanqing\Fi\Document;

class FiTest extends PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->dataDir = 'test/fixtures';
    $this->format = '{{ order: d }} - {{ title: s }}.md';
    $this->documents = array(
      array(
        'filePath' => $this->dataDir . '/0 - foo.md',
        'fields' => array('order' => 3, 'title' => 'foo title', 'tag' => 'default tag'),
        'content' => 'foo content'
      ),
      array(
        'filePath' => $this->dataDir . '/1 - bar.md',
        'fields' => array('order' => 1, 'title' => 'bar', 'tag' => 'bar tag'),
        'content' => 'default content'
      ),
      array(
        'filePath' => $this->dataDir . '/2 - baz.md',
        'fields' => array('order' => 2, 'title' => 'baz', 'tag' => 'default tag'),
        'content' => 'baz content'
      )
    );
    $this->fi = Fi::query($this->dataDir, $this->format);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidDataDirectory()
  {
    $dataDir = '';
    $this->assertFalse(file_exists($dataDir));
    $fi = Fi::query($dataDir, '{{ order: d }} - {{ title: s }}.txt');
  }

  public function testIteration()
  {
    $this->assertTrue($this->fi instanceof \Iterator);
    $this->assertTrue(iterator_count($this->fi) === 3);
    $j = 0;
    foreach ($this->fi as $i => $document) {
      $this->assertSame($i, $j++);
      $this->assertDocumentEquals($document, $this->documents[$i]);
    }
  }

  public function testToArray()
  {
    $arr = $this->fi->toArr();
    $this->assertTrue(is_array($arr));
    $this->assertTrue(count($arr) === 3);
    $j = 0;
    foreach ($arr as $i => $document) {
      $this->assertSame($i, $j++);
      $this->assertDocumentEquals($document, $this->documents[$i]);
    }
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidGetField()
  {
    $arr = $this->fi->toArr();
    $arr[0]->getField('foo');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidFilter()
  {
    $this->fi->filter(null)->toArr();
  }

  public function testFilter()
  {
    $this->fi->filter(function($document) {
      return $document->getField('title') !== 'foo title';
    });
    $arr = $this->fi->toArr();
    $this->assertTrue(count($arr) === 2);
    $this->assertDocumentEquals($arr[0], $this->documents[1]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidMap()
  {
    $this->fi->map(null)->toArr();
  }

  public function testMap()
  {
    $this->fi->map(function($document) {
      if ($document->getField('title') === 'foo title') {
        $document->setField('title', 'changed title');
        $document->setField('order', null); # unsets the 'order' field
        $document->setContent('changed content');
      }
      return $document;
    });
    $arr = $this->fi->toArr();
    $this->assertTrue(count($arr) === 3);
    $this->assertDocumentEquals($arr[0],
      array(
        'filePath' => $this->dataDir . '/0 - foo.md',
        'fields' => array('title' => 'changed title', 'tag' => 'default tag'),
        'content' => 'changed content'
      )
    );
    $this->assertDocumentEquals($arr[1], $this->documents[1]);
    $this->assertDocumentEquals($arr[2], $this->documents[2]);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortUsingCallback()
  {
    $this->fi->sort(null)->toArr();
  }

  public function testSortUsingCallback()
  {
    # 'title', ascending
    $this->fi->sort(function($document1, $document2) {
      return strnatcmp($document1->getField('title'), $document2->getField('title'));
    });
    $arr = $this->fi->toArr();
    $this->assertTrue(count($arr) === 3);
    $this->assertDocumentEquals($arr[0], $this->documents[1]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
    $this->assertDocumentEquals($arr[2], $this->documents[0]);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortByFieldName()
  {
    $this->fi->sort('order', null)->toArr();
  }

  public function testSortByFieldNameNumeric()
  {
    # 'order', ascending
    $arr = $this->fi->sort('order', Fi::ASC)->toArr();
    $this->assertDocumentEquals($arr[0], $this->documents[1]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
    $this->assertDocumentEquals($arr[2], $this->documents[0]);

    # 'order', descending
    $arr = $this->fi->sort('order', Fi::DESC)->toArr();
    $this->assertDocumentEquals($arr[0], $this->documents[0]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
    $this->assertDocumentEquals($arr[2], $this->documents[1]);
  }

  public function testSortByFieldNameString()
  {
    # 'title', ascending
    $arr = $this->fi->sort('title', Fi::ASC)->toArr();
    $this->assertDocumentEquals($arr[0], $this->documents[1]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
    $this->assertDocumentEquals($arr[2], $this->documents[0]);

    # 'title', descending
    $arr = $this->fi->sort('title', Fi::DESC)->toArr();
    $this->assertDocumentEquals($arr[0], $this->documents[0]);
    $this->assertDocumentEquals($arr[1], $this->documents[2]);
    $this->assertDocumentEquals($arr[2], $this->documents[1]);
  }

  private function assertDocumentEquals($document, $expected)
  {
    $this->assertTrue($document instanceof Document);
    $this->assertSame($expected['filePath'], $document->getFilePath());
    $actualFields = $document->getFields();
    $this->assertEmpty(array_merge(array_diff($expected['fields'], $actualFields), array_diff($actualFields, $expected['fields'])));
    foreach ($document->getFields() as $fieldName => $fieldVal) {
      $this->assertSame(isset($expected['fields'][$fieldName]), $document->hasField($fieldName));
      $this->assertSame($expected['fields'][$fieldName], $document->getField($fieldName));
    }
    $this->assertSame($expected['content'] !== '', $document->hasContent());
    $this->assertSame($expected['content'], $document->getContent());
  }

}
