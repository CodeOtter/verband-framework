<?php 

namespace Verband\Framework\Process;

use Symfony\Component\HttpFoundation\Request;

use Verband\Framework\Core;
use Verband\Framework\Caching\FileCache;
use Verband\Framework\Structure\Process;
use Verband\Framework\Structure\Context;

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
		$request = Request::createFromGlobals();
		$request->setSession($context->getState('session'));
		$context->setState('request', $request);
		return $request;
	}
}
