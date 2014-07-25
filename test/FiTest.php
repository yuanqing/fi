<?php
/**
 * Fi.php - Query a collection of text files like a document database in PHP.
 *
 * @author Lim Yuan Qing <hello@yuanqing.sg>
 * @license MIT
 * @link https://github.com/yuanqing/fi
 */

use yuanqing\Fi\Fi;
use yuanqing\Fi\Document;

class FiTest extends PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->dataDir = 'test/fixtures';
    $this->format = 'foo/bar/{{ order: d }} - {{ title: s }}.md';
    $this->c = Fi::query($this->dataDir, $this->format);
    $this->documents = array(
      array(
        'filePath' => $this->dataDir . '/foo/bar/0 - foo.md',
        'fields' => array('order' => 3, 'title' => 'foo title', 'tag' => 'default tag'),
        'content' => 'foo content'
      ),
      array(
        'filePath' => $this->dataDir . '/foo/bar/1 - bar.md',
        'fields' => array('order' => 1, 'title' => 'bar', 'tag' => 'bar tag'),
        'content' => 'default content 2'
      ),
      array(
        'filePath' => $this->dataDir . '/foo/bar/2 - baz.md',
        'fields' => array('order' => 2, 'title' => 'baz', 'tag' => 'default tag'),
        'content' => 'baz content'
      )
    );
    $this->orderAscending = array(1, 2, 3);
    $this->titleAscending = array('bar', 'baz', 'foo title');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidDataDirectory()
  {
    $dataDir = '';
    $this->assertFalse(file_exists($dataDir));
    $c = Fi::query($dataDir, $this->format);
  }

  public function testIteration()
  {
    $this->assertTrue($this->c instanceof \Iterator);
    $this->assertSame(count($this->documents), iterator_count($this->c));
    $j = 0;
    foreach ($this->c as $i => $document) {
      $this->assertSame($j++, $i);
      $this->assertDocumentEquals($this->documents[$i], $document);
    }
  }

  public function testToArr()
  {
    $arr = $this->c->toArr();
    $this->assertTrue(is_array($arr));
    $this->assertSame(count($this->documents), count($arr));
    $j = 0;
    foreach ($arr as $i => $document) {
      $this->assertSame($j++, $i);
      $this->assertDocumentEquals($this->documents[$i], $document);
    }
  }

  public function testGetDocument()
  {
    $count = iterator_count($this->c);
    for ($i=0; $i<$count; $i++) {
      $this->assertDocumentEquals($this->documents[$i], $this->c->getDocument($i));
    }
  }

  /**
   * @expectedException OutOfBoundsException
   */
  public function testInvalidGetDocument()
  {
    $this->c->getDocument(-1);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidGetDocumentField()
  {
    $this->c->getDocument(0)->getField('foo');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidMap()
  {
    $this->c->map(null);
  }

  public function testMap()
  {
    $this->c->map(function($document) {
      $document->setField('title', 'qux');
      $document->setContent('quux');
      return $document;
    });
    $this->assertSame(count($this->documents), iterator_count($this->c));
    foreach ($this->c as $document) {
      $this->assertSame('qux', $document->getField('title'));
      $this->assertSame('quux', $document->getContent());
    }
  }

  public function testFilter()
  {
    $this->assertSame(count($this->documents), iterator_count($this->c));
    $count = 0;
    $this->c->filter(function($document) use (&$count) {
      if ($document->getField('title') === $this->documents[0]['fields']['title']) {
        return false;
      }
      $count++;
      return true;
    });
    $this->assertSame($count, iterator_count($this->c));
    foreach ($this->c as $document) {
      $this->assertFalse($document->getField('title') === $this->documents[0]['fields']['title']);
    }
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidFilter()
  {
    $this->c->filter(null);
  }

  public function testSortWithCallback()
  {
    # 'title', ascending
    $this->c->sort(function($document1, $document2) {
      return strnatcasecmp($document1->getField('title'), $document2->getField('title'));
    });
    $this->assertSame(count($this->documents), iterator_count($this->c));
    foreach ($this->titleAscending as $i => $title) {
      $this->assertSame($title, $this->c->getDocument($i)->getField('title'));
    }
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortWithCallback()
  {
    $this->c->sort(null);
  }

  public function testSortByFieldName()
  {
    # sort by 'title', ascending
    $this->c->sort('title', Fi::ASC);
    foreach ($this->titleAscending as $i => $title) {
      $this->assertSame($title, $this->c->getDocument($i)->getField('title'));
    }

    # sort by 'title', descending
    $this->c->sort('title', Fi::DESC);
    foreach (array_reverse($this->titleAscending) as $i => $title) {
      $this->assertSame($title, $this->c->getDocument($i)->getField('title'));
    }

    # sort by 'order', ascending
    $this->c->sort('order', Fi::ASC);
    foreach ($this->orderAscending as $i => $order) {
      $this->assertSame($order, $this->c->getDocument($i)->getField('order'));
    }

    # sort by 'order', descending
    $this->c->sort('order', Fi::DESC);
    foreach (array_reverse($this->orderAscending) as $i => $order) {
      $this->assertSame($order, $this->c->getDocument($i)->getField('order'));
    }
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testInvalidSortByFieldName()
  {
    $this->c->sort('order', null);
  }

  private function assertDocumentEquals(array $expected, Document $actual)
  {
    $this->assertSame($expected['filePath'], $actual->getFilePath());
    $this->assertTrue($expected['fields'] == $actual->getFields());
    foreach ($actual->getFields() as $fieldName => $fieldVal) {
      $this->assertSame(isset($expected['fields'][$fieldName]), $actual->hasField($fieldName));
      $this->assertSame($expected['fields'][$fieldName], $actual->getField($fieldName));
    }
    $this->assertSame($expected['content'] !== '', $actual->hasContent());
    $this->assertSame($expected['content'], $actual->getContent());
  }

}
