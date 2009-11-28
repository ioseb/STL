<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../stl.lib.php';

class STL_GlobalContextTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @dataProvider emptyValuesProvider
   */
  public function testEmptyValues($context, $expected, $var) {
    $this->assertEquals($expected, $context->lookup($var));
  }
  
  public static function emptyValuesProvider() {
    
    $context = new STL_Context();
    $context->put('zero_value', 0);
    $context->put('null_value', null);
    $context->put('false_value', false);
    $context->put('empty_string_value', '');
    
    return array(
      array($context, 0, 'zero_value'),
      array($context, null, 'null_value'),
      array($context, false, 'false_value'),
      array($context, '', 'empty_string_value')
    );
    
  }
  
  /**
   * @dataProvider lookupProvider
   */
  public function testLookup($value, $context, $param) {
    $this->assertEquals($value, $context->lookup($param));
  }
  
  public static function lookupProvider() {
    
    $value = 'test value';
    $context = new STL_Context();
    
    $context->put('value',  $value);
    $context->put('array1', array($value));
    $context->put('array2', array(array('sub'  => $value)));
    $context->put('array3', array(array('sub1' => array($value))));
    $context->put('array4', array(17 => array('sub1' => array(23 => $value))));
    
    $context->put('obj1', (object) array('prop' => $value, 'prop1' => $value));
    $context->put('obj2', new SampleData($value));
    
    return array(
      array($value, $context, '{value}'),
      array($value, $context, '{array1[0]}'),
      array($value, $context, '{array2[0].sub}'),
      array($value, $context, '{array3[0].sub1[0]}'),
      array($value, $context, '{array4[17].sub1[23]}'),
      array($value, $context, '{obj1.prop}'),
      array($value, $context, '{obj1.prop1}'),
      array($value, $context, '{obj2.prop1}'),
      array($value, $context, '{obj2.prop2.prop1}'),
      array($value, $context, '{obj2.prop2[0]}')
    );
    
  }
  
  /**
   * @dataProvider mergedLookupProvider
   */
  public function testMergedLookup($value, $context, $param) {
    $this->assertEquals($value, $context->lookup($param));
  }
  
  public static function mergedLookupProvider() {
    
    $value = 'test value';
    $context = new STL_Context();
    
    $context->put('value',   $value);
    $context->put('array1', 'array1');
    $context->put('array2', 'array2');
    
    $context1 = new STL_Context($context);
    $context->put('array1', array($value));
    $context->put('array2', array(array('sub'  => $value)));    
    
    return array(
      array($value, $context, '{value}'),
      array($value, $context, '{array1[0]}'),
      array($value, $context, '{array2[0].sub}')
    );
    
  }
  
}

class SampleData {
  
  private $prop1;
  private $prop2;
  
  public function __construct($prop1) {
    $this->prop1 = $prop1;
    $this->prop2 = array(
      $prop1,
      'prop1' => $prop1
    ); 
  }
  
  public function getProp1() {
    return $this->prop1;
  }
  
  public function isProp1() {
    return $this->prop1;
  }
  
  public function getProp2() {
    return $this->prop2;
  }
  
}

?>