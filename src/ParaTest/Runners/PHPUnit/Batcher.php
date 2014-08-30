<?php namespace ParaTest\Runners\PHPUnit;

/**
 * The "Batcher" takes a list of test-cases and groups them into batches.
 *
 * To reduce issues from bugs in the underlying test-cases:
 *  - All tests within the same class will be placed in the same batch
 *  - TODO: All tests within the same batch will execute in consistent relative order
 */
class Batcher {
  /**
   * @var int the number of concurrent test processes
   */
  private $processes;

  /**
   * @var mixed approx #functions to put in each batch; use one of:
   *   - the #functions (e.g. "10") which will put ~10 test-functions in each batch
   *   - a divisor (e.g. "/5") which will put ~1/5th of test-functions in each batch
   *   - zero ("0") which will disable batching
   */
  private $batchSize;

  function __construct($processes, $batchSize) {
    $this->processes = $processes;
    $this->batchSize = $batchSize;
  }

  /**
   * Given a list of test-methods, construct a set of batches which contain those test-methods.
   *
   * @param array<TestMethod> $testMethods
   * @return array<ExecutableTest>
   */
  public function batchMethods($testMethods) {
    // TODO
    return $testMethods;
  }

  /**
   * Given a list of test-suites, construct a set of batches which contain those test-suites.
   *
   * @param array<Suite> $testSuites
   * @return array<ExecutableTest>
   */
  public function batchSuites($testSuites) {
    if ($this->batchSize === 0 || $this->batchSize === '0') {
      return $testSuites;
    }

    $batches = array(); // array( mixed $batchKey => array(Suite) )
    $batchedSuites = array(); // array( mixed $batchKey => BatchedSuite )
    $batchSizes = array(); // array( mixed $batchKey => int $funcCount )
    $suiteSizes = array(); // array( mixed $suiteKey => int $funcCount )

    // Sort suites by size
    foreach ($testSuites as $suiteKey => $testSuite) {
      /** @var Suite $testSuite */
      $suiteSizes[$suiteKey] = count($testSuite->getFunctions());
    }
    asort($suiteSizes);
    $suiteSizes = array_reverse($suiteSizes);

    // Determine the target batch size
    $targetSize = $this->computeBatchSize(array_sum($suiteSizes));

    // Place suites into batches, going from largest to smallest
    foreach ($suiteSizes as $suiteKey => $funcCount) {
      $placed = FALSE;
      for ($batchKey = 0; $batchKey < count($batches); $batchKey++) {
        if ($batchSizes[$batchKey] + $funcCount <= $targetSize) {
          $batches[$batchKey][] = $testSuites[$suiteKey];
          $batchSizes[$batchKey] += $funcCount;
          $placed = TRUE;
        }
      }
      if (!$placed) {
        $batches[$batchKey] = array($testSuites[$suiteKey]);
        $batchSizes[$batchKey] = $funcCount;
      }
    }

    // TODO: reduce SLOC; above loop should use $batchedSuites instead of $batches?
    foreach ($batches as $batchKey => $suites) {
      $batchedSuite = new BatchedSuite();
      foreach ($suites as $suite) {
        $batchedSuite->addSuite($suite);
      }
      $batchedSuites[$batchKey] = $batchedSuite;
    }

    return $batchedSuites;
  }

  /**
   * @param int $totalFuncs
   * @return int
   */
  public function computeBatchSize($totalFuncs) {
    if (is_numeric($this->batchSize)) {
      $targetSize = $this->batchSize;
      return $targetSize;
    }
    elseif ($this->batchSize{0} == '/') {
      $divisor = substr($this->batchSize, 1);
      $targetSize = floor($totalFuncs / $divisor) + 1;
      return $targetSize;
    }
    else {
      throw new \Exception("Unsupported batch size");
    }
  }
}