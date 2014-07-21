<?php
/**
 * X.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link http://github.com/yuanqing/X.php
 */

use org\bovigo\vfs\vfsStream;
use yuanqing\Fi\Fi;
use yuanqing\Fi\Document;

class FiTest extends PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->documents = array(
      array(
        'filePath' => 'test/data/0 - Foo.md',
        'fields' => array('order' => 0, 'title' => 'Qux'),
        'content' => 'qux'
      ),
      array(
        'filePath' => 'test/data/1 - Bar.md',
        'fields' => array('order' => 1, 'title' => 'Bar'),
        'content' => 'bar'
      ),
      array(
        'filePath' => 'test/data/2 - Baz.md',
        'fields' => array('order' => 2, 'title' => 'Baz'),
        'content' => ''
      )
    );
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidDataDirectory()
  {
    $dataDir = '';
    $this->assertFalse(file_exists($dataDir));
    $fi = new Fi($dataDir, '{{ foo }}');
  }

  public function testBaseline()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $this->assertTrue($fi instanceof \Iterator);

    foreach ($fi as $i => $document) {
      $this->assertTrue($document instanceof Document);
      $expected = $this->documents[$i];
      $this->assertSame($document->getFilePath(), $expected['filePath']);
      $this->assertSame($document->getFields(), $expected['fields']);
      foreach ($document->getFields() as $fieldName => $fieldVal) {
        $this->assertSame($document->hasField($fieldName), isset($expected['fields'][$fieldName]));
        $this->assertSame($document->getField($fieldName), $expected['fields'][$fieldName]);
      }
      $this->assertSame($document->hasContent(), $expected['content'] !== '');
      $this->assertSame($document->getContent(), $expected['content']);
    }

    $documents = $fi->get();
    $this->assertTrue(is_array($documents));
    $this->assertTrue(count($documents) === 3);
  }

  public function testNoMatch()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.txt');
    $fi->sort('order', Fi::ASC);
    $this->assertTrue(count($fi->get()) === 0);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidFilter()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $fi->filter(null)->get();
  }

  public function testFilter()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    $fi->filter(function($document) {
      return $document->getField('title') !== 'Qux';
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz'));
  }

  public function testMap()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    $fi->map(function($document) {
      $document->setField('title', 'Quux');
      $document->setContent('quux');
      return $document;
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Quux', 'Quux', 'Quux'));
    $this->assertContentEquals($documents, array('quux', 'quux', 'quux'));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidMap()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $fi->map(null)->get();
  }

  public function testSortUsingCallback()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'title' field, ascending
    $fi->sort(function($document1, $document2) {
      return strnatcmp($document1->getField('title'), $document2->getField('title'));
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz', 'Qux'));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortUsingCallback()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $fi->sort(null)->get();
  }

  public function testSortByFieldNameNumeric()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'order' field, descending
    $documents = $fi->sort('order', Fi::DESC)->get();
    $this->assertFieldEquals($documents, 'order', array(2, 1, 0));

    # 'order' field, ascending
    $documents = $fi->sort('order', Fi::ASC)->get();
    $this->assertFieldEquals($documents, 'order', array(0, 1, 2));
  }

  public function testSortByFieldNameString()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'title' field, descending
    $documents = $fi->sort('title', Fi::DESC)->get();
    $this->assertFieldEquals($documents, 'title', array('Qux', 'Baz', 'Bar'));

    # 'title' field, ascending
    $documents = $fi->sort('title', Fi::ASC)->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz', 'Qux'));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortByFieldName()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $fi->sort('title', null)->get();
  }

  private function assertContentEquals($documents, $contentArr)
  {
    $this->assertTrue(count($documents) === count($contentArr));
    foreach ($documents as $i => $document) {
      $this->assertSame($document->getContent(), $contentArr[$i]);
    }
  }

  private function assertFieldEquals($documents, $fieldName, $fieldValues)
  {
    $this->assertTrue(count($documents) === count($fieldValues));
    foreach ($documents as $i => $document) {
      $this->assertSame($document->getField($fieldName), $fieldValues[$i]);
    }
  }

}
