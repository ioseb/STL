<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../stl.lib.php';

class STL_ForEachTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @dataProvider foreachNestedArrayData
   */
  public function testForEachWithNestedArray($expected, $input) {
    
    $tpl = new STL_template('
      {for i in array}%i.key1%%i.key2%%i.key3%{/for}
    ');
    
    $tpl->put('array', $input);
    
    $this->assertEquals($expected, $tpl->process());
        
  }
  
  public static function foreachNestedArrayData() {
    
    $a = array(
      'key1' => 'v1',
      'key2' => 'v2',
      'key3' => 'v3',
    );
    
    return array(
      array(
        implode('', $a),
        array($a)
      )
    );
    
  }
  
  /**
   * @dataProvider foreachSimpleData
   */
  public function testSimpleForEach($expected, $input) {
    
    $tpl = new STL_template('{for i in array}%i%{/for}');
    
    $tpl->put('array', $input);
    
    $this->assertEquals($expected, $tpl->process());
        
  }
  
  public static function foreachSimpleData() {
    
    $a1 = range(0, 9);
    $a2 = range(0, 21);
    $a3 = array('a', 'b', 'c', 'd', 'e', 'f', 'j', 'h');
    $a4 = array(0, null, false, '');
    
    return array(
      array(
        implode('', $a1),
        $a1,
      ),
      array(
        implode('', $a2),
        $a2,
      ),
      array(
        implode('', $a3),
        $a3,
      ),
      array(
        implode('', $a4),
        $a4,
      )
    );
  }
  
}