<?php
/**
 * X.php
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link http://github.com/yuanqing/X.php
 */

use yuanqing\Fi\Fi;
use yuanqing\Fi\Document;

class FiTest extends PHPUnit_Framework_TestCase
{
  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidDataDirectory()
  {
    $dataDir = '';
    $format = '{{ foo }}';
    $this->assertFalse(file_exists($dataDir));
    $fi = new Fi($dataDir, $format);
  }

  public function testBaseline()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');
    $this->assertTrue($fi instanceof \Iterator);

    $documents = $fi->get();
    $this->assertTrue(is_array($documents));
    $this->assertTrue(count($documents) === 3);
    $this->assertFieldEquals($documents, 'title', array('Foo', 'Bar', 'Baz'));
    foreach ($documents as $index => $document) {
      $this->assertTrue(is_int($index));
      $this->assertTrue($document instanceof Document);
    }
  }

  public function testNoMatch()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.txt');
    $fi->sort('order', Fi::ASC);
    $this->assertTrue(count($fi->get()) === 0);
  }

  public function testFilter()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Foo', 'Bar', 'Baz'));

    $fi->filter(function($document) {
      return $document->getField('title') !== 'Foo';
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz'));
  }

  public function testMap()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    $fi->map(function($document) {
      return $document->setField('title', 'Qux');
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Qux', 'Qux', 'Qux'));
  }

  public function testSortUsingCallback()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'title' field, ascending
    $fi->sort(function($document1, $document2) {
      return strnatcmp($document1->getField('title'), $document2->getField('title'));
    });
    $documents = $fi->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz', 'Foo'));
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
    $this->assertFieldEquals($documents, 'title', array('Foo', 'Baz', 'Bar'));

    # 'title' field, ascending
    $documents = $fi->sort('title', Fi::ASC)->get();
    $this->assertFieldEquals($documents, 'title', array('Bar', 'Baz', 'Foo'));
  }

  private function assertFieldEquals($documents, $fieldName, $fieldValues)
  {
    $this->assertTrue(count($documents) === count($fieldValues));
    foreach ($documents as $i => $document) {
      $this->assertSame($document->getField($fieldName), $fieldValues[$i]);
    }
  }

}
