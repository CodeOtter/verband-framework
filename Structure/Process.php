<?php 

namespace Verband\Framework\Structure;

/**
 * A Process is a functional monad.  The Process is aware of what Context it is in when it executes.
 */
interface Process {

	/**
	 * Initializes the process right before it is executed in the Framework's process flow.
	 * @param	\Verband\Framework\Context
	 * @return	void
	 */
	public function init(Context $context);
	
	/**
	 * Executes the process within a specific Context.
	 * @param	\Verband\Framework\Context
	 * @param	mixed	The result of the previous Context Process.
	 * @return	mixed
	 */
	public function execute(Context $context, $lastResult);
}