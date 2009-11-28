<?php

require_once 'PHPUnit/Framework.php';

class AllTests {
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('STL');
    $suite->addTest(STL_AllTests::suite());
    return $suite;
  }
}

class STL_AllTests {
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('STL');
    $suite->addTestSuite('STL_EvaluatorTest');
    $suite->addTestSuite('STL_GlobalContextTest');
    return $suite;
  }
}

?>