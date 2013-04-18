<?php 

namespace Verband\Framework\Structure;

use Verband\Framework\Util\Nomenclature;
use Verband\Framework\Core;

/**
 * The Workflow component reads the Workflow.xml files in every package and converts it into a chain of Contexts.
 * The following XML structure is a detailed example of all valid node configurations.
 * <application>
 *   <package name="Verband\Framework">
 *     <process name="Initialize">
 *     <after process="Initialize" inject="Doctrine\Processes\Initialize">
 *     <after process="Doctrine\Processe\Initialize">
 *       <process name="CodeOtter\Rest\Processes\Initialize">
 *     </after>
 *   </package>
 * </application>
 * The following manipulation nodes will be created in the future: Before, Move, and Swap.
 */
class Workflow {

	const
		// Data nodes
		NODE_APPLICATION	= 'application',
		NODE_PACKAGE		= 'package',
		NODE_PROCESS		= 'process',
		
		// Action nodes
		NODE_AFTER			= 'after',
		NODE_BEFORE			= 'before',
		NODE_DELETE			= 'delete',
		NODE_MOVE			= 'move',
		NODE_SWAP			= 'swap';
	
	private
		/**
		 * @var Verband\Framework\Core
		 */
		$framework = null,

		/**
		 * @var array
		 */
		$xmlCache = array();
	
	
	/**
	 * Constructor
	 * @throws	\Exception
	 * @return	void
	 */
	public function __construct(Core $framework) {
		$this->framework = $framework;
	 } 

	 /**
	  * Assembles an Application's workflow.
	  * Enter description here ...
	  * @param	\Verband\Framework\Package		$package
	  * @param	\Verband\Framework\Context		$context
	  * @throws \Exception
	  * @return	\Framework\Context
	  */
	 public function gather(Package $package) {

	 	$xml = $this->getXml($package);

	 	if($xml->getName() == self::NODE_PACKAGE) {
	 		return $this->handlePackageNode($xml);
	 	} else if ($xml->getName() == self::NODE_APPLICATION) {
	 		return $this->handleApplicationNode($xml);
	 	}
	 }
	 
	 /**
	  * 
	  * Enter description here ...
	  * @param unknown_type $contexts
	  * @return unknown
	  */
	 public function assemble($contexts) {
	 	// Stitch the contexts together into a chain
	 	$initialContext = array_shift($contexts);
	 	$currentContext = $initialContext;
	 	foreach($contexts as $context) {
	 		$currentContext->addChild($context);
	 		$currentContext = $context;
	 	}
	 	
	 	return $initialContext;
	 }

	 /**
	  * Gets the Workflow XML of an package.
	  * @param \Verband\Framework\Package $package
	  * @return \SimpleXMLElement
	  */
	 public function getXml($package) {
	 	$packageName = $package->getName();
	 	if(!isset($this->xmlCache[$packageName])) {
	 		$filename = $package->getDirectory() . '/Settings/workflow.xml';
	 		if(!file_exists($filename)) {
	 			throw new \Exception('The ' . $packageName . ' package must have a Workflow.xml');
	 		}

	 		$this->xmlCache[$packageName] = simplexml_load_file($filename);
	 	}
	 	return $this->xmlCache[$packageName];
	 }
	 
	 /**
	  * Gets an Package from an XML Node 
	  * @param	\SimpleXMLElement
	  * @throws \Exception
	  * @return	 \Verband\Framework\Package
	  */
	 protected function getPackage($node) {
	 	if(!($node instanceof \SimpleXMLElement)) {
	 		throw new \Exception('Node is expected to be a SimpleXMLElement, but it is a ' . gettype($node));
	 	}

	 	if($node->getName() != self::NODE_PACKAGE) {
	 		throw new \Exception('Cannot extract the package for a ' . $node->getName() . ' workflow node.');
	 	}

	 	$attributes = $node->attributes();

	 	if($attributes->name === null) {
	 		throw new \Exception('Package node missing a "name" attribute.');
	 	}

	 	$package = $this->framework->getPackage((string)$attributes->name);

	 	if(!$package) {
	 		throw new \Exception('Package does not exist: ' . (string)$attributes->name);
	 	}

	 	return $package;
	 }

	 /**
	  * Formats a process name into a fuly-qualified class name
	  * @param	String	Process name
	  * @param	\Verband\Framework\Package	The package that owns the process
	  * @return	 String
	  */
	 protected function getProcessName($name, $parentPackage = null) {
	 	if(strpos($name, '\\') === false) {
	 		if($parentPackage === null) {
	 			throw new \Exception('Parent package expected to establish the namespace for the "' . $name . ': process.');
	 		}
	 		
	 		$packageName = $parentPackage->getName();

	 		return $packageName . '\\Process\\' . $name;
	 	}
	 	return $name;
	 }

	 /**
	  * Get the parent node of a SimpleXMLElement
	  * @param	\SimpleXMLElement
	  * @return \SimpleXMLElement
	  */
	 protected function getParentNode($node) {
	 	return current($node->xpath('parent::*'));
	 }

	 /**
	  * Handles the Applications node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return 	Array
	  */
	 protected function handleApplicationNode($node) {
	 	$result = array();
	 	 	
	 	foreach($node->children() as $child) {
	 		if($child->getName() == self::NODE_PACKAGE) {
	 			$result = array_merge($result, $this->handlePackageNode($child));
	 		} else if($child->getName() == self::NODE_PROCESS) {
	 			$result[] = $this->handleProcessNode($child);
	 		} else {
	 			throw new \Exception('Only Package and Process elements are allowed in a Application node.');
	 		}
	 	}
	 	return $result;
	 }

	 /**
	  * Handles an Package node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return	 Array
	  */
	 protected function handlePackageNode($node) {

	 	$package = $this->getPackage($node);
	 	$attributes = $node->attributes();
		$children = $node->children();
		$result = array();

		if($this->getParentNode($node)) {
			// This is a package initialization request
			$workflow = new Workflow($this->framework);
			$result = $workflow->gather($package);
		}	
		
		if(count($node->children()) > 0) {
			// The package is a process loader
			foreach($children as $child) {
				$nodeName = $child->getName();
				$transformMethod = 'handle' . ucfirst($nodeName) . 'Node';
				if(!method_exists($this, $transformMethod)) {
					throw new \Exception('Unknown workflow element "' . $tag . '" in ' . $package->getName());
				}

				if($nodeName == self::NODE_PROCESS) {
					// Dealing with a data node
					$result[] = $this->$transformMethod($child);
				} else if($nodeName == self::NODE_PACKAGE) {
					$result = array_merge($this->$transformMethod($child), $result);
				} else {
					// Dealing with an action node
					$result = $this->$transformMethod($child, $result);
				}
			}
		} else {
			// The package is being injected		

		}
		return $result;
	 }

	 /**
	  * Handles a Process node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return 	Array
	  */
	 protected function handleProcessNode($node) {
	 	$parent = $this->getPackage($this->getParentNode($node));
	 	 	
	 	if(!$parent) {
	 		throw new \Exception('Better parent detection needed');
	 	}

	 	$attributes = $node->attributes();
	 	
	 	if($attributes->name === null) {
	 		throw new \Exception('Process node missing name.');
	 	}

	 	$process = $this->getProcessName((string)$attributes->name, $parent);
	 	return new Context($process, $parent, new $process());
	 }

	 /**
	  * Handles an After node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @param	Array	Chain of Contexts
	  * @return	Array
	  */
	 protected function handleAfterNode($node, $chain) {

	 	$parent = $this->getPackage($this->getParentNode($node));
	 	$attributes = $node->attributes();

	 	if($attributes->process === null) {
	 		throw new \Exception('The After action node requires a "process" attribute.');
	 	}

	 	$process = $this->getProcessName((string)$attributes->process, $parent);
	 	$targetIndex = null;


	 	if($attributes->inject === null) {
	 		throw new \Exception('The After action node requires an "inject" attribute.');	
	 	}

		// Target is in the attributes
 		$injectClass = $this->getProcessName((string)$attributes->inject, $parent);

 		if(strpos($injectClass, '\\Process\\')) {
 			// Dealing with a process
 			$result = array(new Context($injectClass, $parent, new $injectClass()));
 		} else {
 			// Dealing with an package
 			$injectObject = new $injectClass($this->framework->getPath(Core::PATH_PACKAGES) . '/' . Nomenclature::toPath(Nomenclature::getVendorAndPackage($injectClass)));
 			$workflow = new Workflow($this->framework);
 			$result = $workflow->gather($injectObject);
 			
 		}

 		// Find where to inject the process
 		foreach($chain as $index => $context) {
 			if(get_class($context->getProcess()) == $process) {
 				$targetIndex = $index + 1;
 				break;
 			}
 		}
 		
 		if($targetIndex === null) {
 			throw new \Exception('Process "' . $process . '" cannot be found.');
 		}
 		
 		// Perform the injection
 		array_splice($chain, $targetIndex, 0, $result);
 		return $chain;
	 }

	 /**
	  * Handles a Before node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return	Array
	  */
	 protected function handleBeforeNode($node, $results) {	
	 	$parent = $this->getParentNode($node);
	 	$attributes = $node->attributes();
	 	$process = $this->getProcessName((string)$attributes->process, $parent);
	 	$inject = $this->getProcessName((string)$attributes->inject, $parent);
	 }

	 /**
	  * Handles a Move node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return	Array
	  */
	 protected function handleMoveNode($node, $results) {
	 	$parent = $this->getParentNode($node);
	 	$attributes = $node->attributes();
	 	$process = $this->getProcessName($attributes['process'], $parent);
	 	$to = $this->getProcessName($attributes['to'], $parent);
	 }

	 /**
	  * Handles a Swap node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return	Array
	  */
	 protected function handleSwapNode($node, $results) {
	 	$parent = $this->getParentNode($node);
	 	$attributes = $node->attributes();
	 	$process = $this->getProcessName($attributes['process'], $parent);
	 	$with = $this->getProcessName($attributes['with'], $parent);
	 }

	 /**
	  * Handles a Remove node and converts it to a chain of contexts.
	  * @param	\SimpleEXMLElement
	  * @return	Array
	  */
	 protected function handleRemovNode($node, $results) {
	 	$parent = $this->getParentNode($node);
	 	$attributes = $node->attributes();
	 	$process = $this->getProcessName($attributes['process'], $parent);;
	 }

	 /**
	  * Gets the last context the packages process has set.
	  * @return	\Verband\Framework\Context
	  */
	 protected function getFramework() {
	 	return $this->framework;
	 }
}