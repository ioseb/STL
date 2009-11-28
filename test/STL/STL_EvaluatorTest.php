<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../stl.lib.php';

class STL_EvaluatorTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @dataProvider valueProvider
   */
  /*public function testFixedValue($v1, $v2) {
    $this->assertEquals($v1, STL_Evaluator::getFixedValue($v2));
  }*/
  
  public static function valueProvider() {
    return array(
      array(true,  'true'),
      array(false, 'false'),
      array(null,  'null'),
      array(1,     '1'),
      array(2,     '2.0'),
      array(8.133, '8.133')
    );
  }
  
  /**
   * @dataProvider provider
   */
  public function testIfTrue($context, $value, $expression) {
    $condition = STL_ParseCondition::parse($expression);
    $this->assertEquals($value, STL_Condition::evaluate($condition, $context));
  }
  
  public static function provider() {
    
    $context = new STL_Context();
    $context->put('var1', 'test value');
    $context->put('var2', 'test value');
    $context->put('var3', 'hello');
    $context->put('var4', true);
    $context->put('var5', false);
    $context->put('var6', null);
    $context->put('var7', 4.8);
    $context->put('var8', 11);
    
    $context->put('test', 'a');
    $context->put('arr', array('b', 'c', 'a'));
    
    return array(
      array(
        $context,
        ('test value' == 'test value') && ('test value' != 'test value') || ('hello' == 'hello'),
        'var1=="test value" and var2!="test value" or var3=="hello"'
      ),
      array(
        $context,
        ('test value' == 'test value') && ('test value' != 'test value') || ('hello' == 'hello'),
        'var1=="test value" && var2!="test value" || var3=="hello"'
      ),
      array(
        $context,
        ('test value' == 'test value') && ('test value' != 'test value') || ('hello' == 'yes'),
        'var1=="test value" and var2!="test value" or var3=="yes"'
      ),
      array(
        $context,
        ('test value' == 'test value') && ('test value' != 'test value') || ('hello' == 'yes'),
        'var1=="test value" && var2!="test value" or var3=="yes"'
      ),
      array(
        $context,
        ('test value' == 'test value'),
        'var1=="test value"'
      ),
      array(
        $context,
        (true == true) && (false == false) && (null == null),
        'var4 == "true" and var5 == "false" and var6 == "null"'
      ),
      array(
        $context,
        (true == true) && (false == false) && (null == null),
        'var4 == "true" and var5 == "false" && var6 == "null"'
      ),
      array(
        $context,
        (false == true) || (false == false) && (null == null),
        'var4 == "false" or var5 == "false" and var6 == "null"'
      ),
      array(
        $context,
        (false == true) || (false == false) && (null == null),
        'var4 == "false" || var5 == "false" && var6 == "null"'
      ),
      array(
        $context,
        (4.8 == 4.8) && (11 == 11),
        'var7 == "4.8" and var8 == "11"'
      ),
      array(
        $context,
        (4.8 == 88) || (4.8 == 4.8) && (11 == 11),
        'var7 == "88" or var7 == "4.8" and var8 == "11"'
      ),
      array(
        $context,
        ('test value' == 'test value') && ('test value' != 'test value') || ('hello' == 'yes') || ('hello' == 'hello'),
        'var1=="test value" and var2!="test value" or var3=="yes" or var3=="hello"'
      ),
      array(
        $context,
        in_array('a', array('b', 'c', 'a')),
        'test && test in arr'
      )
    );
    
  }
  
}

?>