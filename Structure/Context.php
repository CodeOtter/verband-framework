<?php 

namespace Verband\Framework\Structure;

/**
 * A Context is a tree node that contains a Process.  When the Process is executed, the result will be
 * passed to the immediate children of the current Context.  This allows the agnostic, functional, and monadic
 * paradigm that makes Verband possible.
 */
class Context extends Node {

	/**
	 * The Package the Context came from.
	 */
	private $package = null;

	/**
	 * An array of user-defined states that have been bound to this Context.
	 */
	private	$states = array();

	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	private $process;

	/**
	* A helper utility that makes all Contexts in an array children of each other. 
	* @param	array	An array of Contexts
	* @return	Context
	*/
	public static function stitch(Array $contexts) {
		if($contexts) {
			// Stitch the contexts together
			for($i = 0; $i < count($contexts); $i++) {
				$next = $i + 1;
				if(isset($contexts[$next])) {
					$contexts[$i]->addChild($contexts[$next]);
				}
			}
			return $contexts[0];
		} else {
			return false;
		}
	}
	
	/**
	 * Constructor
	 * @param	string	The name of the Context.
	 * @param	\Framework\Package
	 * @param	mixed	The process the context will execute.
	 * @return	void
	 */
	public function __construct($nodename, Package $package = null, $process = null) {
		parent::__construct($nodename);
		$this->package = $package;
		if($process === null) {
			$process = function($context, $lastResult) { };
		}
		$this->process = $process;
	} 
	
	/**
	 * Returns the Package instance the Context came from.
	 * @return	\Framework\Package
	 */
	public function getPackage() {
		return $this->package;
	}
	
	/**
	 * Sets the Package instance the Context came from.
	 * @param	\Framework\Package
	 * @return	void
	 */
	public function setPackage($package) {
		$this->package = $package;
	}
	
	/**
	 * Returns the states bound to the context.
	 * @return	array
	 */
	public function getStates() {
		return $this->states;
	}
	
	/**
	 * Sets the states bound to the context.
	 * @param	array
	 * @return	void
	 */
	public function setStates($states) {
		$this->states = $states;
	}
	
	/**
	 * Returns the Process the Context will run.
	 * @return	mixed
	 */
	public function getProcess() {
		return $this->process;
	}
	
	/**
	 * Sets the Process the Context will run.
	 * @param	mixed
	 * @return	void
	 */
	public function setProcess($process) {
		$this->process = $process;
	}
	
	/**
	 * Runs the Process attached to the Context.
	 * @param	mixed	The result of the parent Context Process.
	 * @return	mixed
	 */
	public function run($lastResult) {
		$process = $this->process;
		if(is_callable($process)) {
			// The Process is a Closure
			return $process($this, $lastResult);
		} else {
			// The Process is a Process object
			$process->init($this);
			return $process->execute($this, $lastResult);
		}
	}
	
	/**
	 * Binds a state to the Context.
	 * @param	string	Name of the state.
	 * @param	mixed	The contents of the state.
	 * @return void
	 */
	public function setState($key, $state) {
		$this->states[$key] = $state;
	}
	
	/**
	 * Retrieves the state bound to a Context.  If the state is not part of this Context, all parent Contexts
	 * will be searched for the state.
	 * @param	string	Name of the state
	 * @return	mixed
	 */
	public function getState($key) {
		if(isset($this->states[$key])) {
			return $this->states[$key];
		} else {
			if($this->hasParent()){
				return $this->getParent()->getState($key);
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Replaces a Context with another Context.
	 * @param	Context
	 * @return	void
	 */
	public function replace($context) {
		$context->setStates(array_merge($context->getStates(), $this->getStates()));
		parent::replace($context);
	}

	/**
	 * Prints out the structure of all children of a Context to HTML.
	 * @param	string	The name of a Context to highlight in the printout. 
	 * @return	string
	 */
	public function traceHtml($highlight = '') {
		return self::trace($this, $highlight, '<ul>', '</ul>', '<li>', '</li>');
	}

	/**
	 * Assembles the printout of all children of a Context.
	 * @param	Context
	 * @param	String
	 * @param	String
	 * @param	String
	 * @param	String
	 * @return	String
	 */
	public static function trace(Context $context, $highlight, $preGroup, $postGroup, $preElement, $postElement) {	
		if($context->getNodeName() == $highlight) {
			$information = '<font color="orange"><b>' . $context->getNodeName() . '</font></b> : ';
		} else {
			$information = '<font color="green"><b>' . $context->getNodeName() . '</font></b> : ';
		}
		
		if(is_callable($context->getProcess())) {
			$information .= '<i>' . get_class($context->getPackage()) . '.php</i>';
		} else {
			$information .= '<i>' . get_class($context->getProcess()) . '.php</i>';
		}
		$result = $preGroup . $preElement . $information;
		$children = $context->getChildren();
		foreach($children as $index => $child) {
			$result .= self::trace($child, $highlight, $preGroup, $postGroup, $preElement, $postElement);
		}
		return $result . $postElement . $postGroup;
	}
}