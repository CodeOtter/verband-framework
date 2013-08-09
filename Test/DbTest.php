<?php

namespace Verband\Framework\Test;

use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Subject;
use Verband\Framework\Test\VerbandTestTrait;

/**
 * Base testcase class for all Doctrine testcases.
 */
class DbTest extends \PHPUnit_Extensions_Database_TestCase
{
    use VerbandTestTrait;

    // only instantiate pdo once for test clean-up/fixture load
    private static $conn;
    private static $subject;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection() {
        return $this->getSubject()->getEntityManager()->getConnection();
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet() {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($path);
    }
}