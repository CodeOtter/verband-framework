<?php

namespace Verband\Framework\Test;

use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Subject;
/**
 * Base testcase class for all Doctrine testcases.
 */
abstract class UnitTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Verband\Framework\Core
	 */
	private static $subject = null;
	
	/**
	 * 
	 * @param Core $application
	 */
	static public function setSubject(Context $contexts) {
		self::$subject = new Subject($contexts);
	}

	/**
	 * @return
	 */
	public function getSubject() {
		return self::$subject;
	}

	/**
	 * 
	 * @param unknown $email
	 */
	public function login($email, $id = 1) {
		
	}
}