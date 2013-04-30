<?php 

namespace Verband\Framework\Process;

use Verband\Framework\Http\ResourceResponse;
use Verband\Framework\Routing\Router;
use Verband\Framework\Core;
use Verband\Framework\Structure\Process;
use Verband\Framework\Structure\Context;
use Verband\Framework\Exceptions\ProcessHaltException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Transforms the body of a request based on the Content-Type and Accept headers.
 */
class ResourceRouter implements Process {

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

		// Check if we are dealing with a resource
		$router = new Router($context->getState('framework')->getPath(Core::PATH_ROOT), $context);

		if($router->isResourceRequest($lastResult)) {
			// Attach a context to handle files
			$file = $lastResult->getRequestUri();
			$fileContents = $router->get($file);
			if($fileContents) {
    			$response = new ResourceResponse($file, $fileContents);
    			$response->send();
    			throw new ProcessHaltException();
			}
		}

		// Add to the context
		$context->setState('router', $router);

		return $lastResult;
	}
}
