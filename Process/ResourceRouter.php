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
		$request = Request::createFromGlobals();
		$router = new Router($context->getState('framework')->getPath(Core::PATH_ROOT));

		if($router->isResource($request)) {
			// Attach a context to handle files
			$file = $request->getRequestUri();
			$response = new ResourceResponse($file, $router->get($file));
			$response->send();
			throw new ProcessHaltException();
		}

		// Add to the context
		$context->setState('request', $request);
		$context->setState('router', $router);

		return $request;
	}
}
