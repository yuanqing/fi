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

    $docs = $fi->get();
    $this->assertTrue(is_array($docs));
    $this->assertTrue(count($docs) === 3);
    foreach ($docs as $index => $doc) {
      $this->assertTrue(is_int($index));
      $this->assertTrue($doc instanceof Document);
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

    $fi->filter(function($doc) {
      return $doc->getField('title') !== 'Foo';
    });
    $docs = $fi->get();
    $this->assertFieldEquals($docs, 'title', array('Bar', 'Baz'));
  }

  public function testMap()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    $fi->map(function($doc) {
      return $doc->setField('title', 'Qux');
    });
    $docs = $fi->get();
    $this->assertFieldEquals($docs, 'title', array('Qux', 'Qux', 'Qux'));
  }

  public function testSortUsingCallback()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'title' field, ascending
    $fi->sort(function($doc1, $doc2) {
      return strnatcmp($doc1->getField('title'), $doc2->getField('title'));
    });
    $docs = $fi->get();
    $this->assertFieldEquals($docs, 'title', array('Bar', 'Baz', 'Foo'));
  }

  public function testSortByFieldNameNumeric()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'order' field, descending
    $docs = $fi->sort('order', Fi::DESC)->get();
    $this->assertFieldEquals($docs, 'order', array(2, 1, 0));

    # 'order' field, ascending
    $docs = $fi->sort('order', Fi::ASC)->get();
    $this->assertFieldEquals($docs, 'order', array(0, 1, 2));
  }

  public function testSortByFieldNameString()
  {
    $fi = new Fi('test/data', '{{ order: d }} - {{ title: s }}.md');

    # 'title' field, descending
    $docs = $fi->sort('title', Fi::DESC)->get();
    $this->assertFieldEquals($docs, 'title', array('Foo', 'Baz', 'Bar'));

    # 'title' field, ascending
    $docs = $fi->sort('title', Fi::ASC)->get();
    $this->assertFieldEquals($docs, 'title', array('Bar', 'Baz', 'Foo'));
  }

  private function assertFieldEquals($docs, $fieldName, $fieldValues)
  {
    $this->assertTrue(count($docs) === count($fieldValues));
    foreach ($docs as $i => $doc) {
      $this->assertSame($doc->getField($fieldName), $fieldValues[$i]);
    }
  }

}
