<?php namespace ParaTest\Runners\PHPUnit;

/**
 * BatchedSuite is a suite of tests whose membership is determined at runtime. It currently depends on the
 * test-suite including an adapter called "EnvTests"
 *
 * @see https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/EnvTests.php
 */
class BatchedSuite extends ExecutableTest {

  private $classes;

  public function __construct() {
    parent::__construct('tests/phpunit/EnvTests.php', 'EnvTests');
    $this->classes = array();
  }

  /**
   * @param Suite $suite
   */
  public function addSuite($suite) {
    $this->classes[] = $suite->getFullyQualifiedClassName();
  }

  protected function getCommandString($binary, $options = array()) {
    $r = parent::getCommandString($binary, $options);
    $env = "env PHPUNIT_TESTS=\"" . implode(' ', $this->classes) . "\" "; // FIXME escapeshellarg
    return $env . $r;
  }
}