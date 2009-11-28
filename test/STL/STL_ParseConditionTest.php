<?php

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../stl.lib.php';

class STL_ParseConditionTest extends PHPUnit_Framework_TestCase {

  /**
   * @dataProvider provider
   */
  public function testCondition($expected, $condition) {
    $this->assertEquals($expected, STL_ParseCondition::parse($condition));
  } 
  
  public static function provider() {
    
    return array(
      array (
        array (
          'var' => array (
            0 => 'param1',
            1 => 'par2',
            2 => 'par3',
            3 => 'par4',
          ),
          'eq' => array (
            0 => '==',
            1 => '!=',
            2 => '>',
            3 => '>',
          ),
          'value' => array (
            0 => 'value1',
            1 => '%val2%',
            2 => 'val3',
            3 => 'val4',
          ),
          'oper' => array (
            0 => 'or',
            1 => '&&',
            2 => '||',
            3 => '',
          ),
        ),
        'param1=="value1" or par2!="%val2%" && par3>"val3" || par4>"val4"',
      ),
      array (
        array (
          'var' => array (
            0 => 'just',
          ),
          'eq' => array (
            0 => '==',
          ),
          'value' => array (
            0 => 'r',
          ),
          'oper' => array (
            0 => '',
          ),
        ),
        'just=="r"',
      ),
      array(
        array (
        'var' => array (
          0 => 'test',
          1 => 'test1',
          2 => 'test2',
          3 => 'test3',
          4 => 't',
        ),
        'eq' => array (
          0 => '!',
          1 => '==',
          2 => '==',
          3 => '==',
          4 => 'in',
        ),
        'value' => array (
          0 => '',
          1 => 't',
          2 => 'true',
          3 => 'true',
          4 => 'hello.world',
        ),
        'oper' => array (
          0 => '||',
          1 => '&&',
          2 => 'and',
          3 => 'or',
          4 => NULL,
        ),
      ),
        '!test || test1=="t" && test2 and test3 or t in hello.world'
      ),
      array(
        array (
          'var' => array (
            0 => 'test',
          ),
          'eq' => array (
            0 => '!',
          ),
          'value' => array (
            0 => '',
          ),
          'oper' => array (
            0 => NULL,
          ),
        ),
        '!test'
      )
    );
    
  } 
  
}