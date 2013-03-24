<?php 

namespace Verband\Framework\Process;

use Verband\Framework\Process;
use Verband\Framework\Context;

/**
 * Transforms the body of a request based on the Content-Type and Accept headers.
 */
class Initialization implements Process {

	/**
	 * An empty initialization.
	 * @param	\Framework\Context
	 * @return	void
	 */
	public function init(Context $context) {}
	
	/**
	 * Based on the Context of the execution, transforms a value to match a MIME Type.
	 * @param	\Framework\Context
	 * @param	mixed
	 * @return	mixed
	 */
	public function execute(Context $context, $lastResult) {
		return $lastResult;
	}
}
