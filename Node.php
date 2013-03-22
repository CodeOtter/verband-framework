<?php 

namespace Framework;

/**
 * A node of a tree structure.
 */
class Node {

	/**
	 * The name of the Node
	 */
	private $nodename;

	/**
	 * Children of the Node
	 */
	private $children = array();

	/**
	 * The parent of the Node
	 */
	private $parent;

	/**
	 * Construct
	 * @param	string	Name of the node.
	 * @param	Node	The parent Node of this Node
	 * @return	void
	 */
	public function __construct($nodename, $parent = null) {
		$this->nodename = $nodename;
		if($parent !== null) {
			$this->setParent($parent);
		}
	}
	
	/**
	 * Returns n array of the immediate children Nodes.
	 * @return	array
	 */
	public function getChildren() {
		return $this->children;
	}
	
	/**
	 * Sets the list of immediate children Nodes.
	 * @param	array
	 * @return	void
	 */
	public function setChildren(Array $children) {
		$this->children = $children;
	}
	
	/**
	 * Determines if the Node has children.
	 * @return boolean
	 */
	public function hasChildren() {
		return count($this->children) > 0;
	}
	
	/**
	 * Returns the parent Node of the Node
	 * @return	Node
	 */
	public function getParent() {
		return $this->parent;
	}

	public function hasParent() {
		return $this->parent !== null;
	}
	
	/**
	 * @todo	Fill this out
	 */
	public function getNodeName() {
		return $this->nodename;
	}
	
	/**
	 * Implied bidirectional association
	 * @todo	Fill this out
	 */
	public function setParent(Node $parent) {
		$this->parent = $parent;
		$parent->addChildOnly($this);
		return $this;
	}

	/**
	 * Implied bidirectional association
	 * @todo	Fill this out
	 */
	public function addChild(Node $child) {
		$this->children[] = $child;
		$child->setParentOnly($this);
		return $this;
	}

	/**
	 * Implied unidirectional association
	 * @todo	Fill this out
	 */
	public function setParentOnly(Node $parent) {
		$this->parent = $parent;
		return $this;
	}
	
	/**
	 * Implied unidirectional association
	 * @todo	Fill this out
	 */
	public function addChildOnly(Node $child) {
		$this->children[] = $child;
		return $this;
	}
	
	/**
	 * @todo	Fill this out
	 */
	public function addSibling(Node $sibling) {
		$this->parent->addChild($sibling);
		return $this;
	}
	
	/**
	 * @todo	Fill this out
	 */
	public function findChild(\Closure $search) {
		if($search($this)) {
			return $this;
		} else {
			foreach($this->children as $child) {
				$result = $child->findChild($search);
				if($result) {
					return $result;
				}
			}
		}
		return false;
	}

	/**
	 * @todo	Fill this out
	 */
	public function findChildByNodeName($name) {
		return $this->findChild(function($child) use ($name) {
			return $child->getNodeName() == $name;
		});
	}

	/**
	 * @todo	Fill this out
	 */
	public function remove() {
		$result = array();

		// Get all children of the parent node
		$children = $this->getParent()->getChildren();
		foreach($children as $index => $child) {
			if($child === $this) {
				$result[] = $index;
			}
		}

		// Destroy the presence of this node in the parent's children.
		foreach($result as $index) {
			unset($children[$index]);
		}

		// Execute the remove process for the child
		foreach($this->getChildren() as $child) {
			$child->remove();
			
		}
		
		$this->cleanup();
	}

	/**
	 * Flag this node for expedited garbage collection
	 * @return	void
	 */
	private function cleanup() {
		$this->setNodeName(null);
		$this->setChildren(null);
		$this->setParent(null);
	}

	/**
	 * @todo	Fill this out
	 */
	public function replace($node) {
		$this->getParent()->addChild($node);
		$node->setChildren($this->getChildren());
		foreach($node->getChildren() as $child) {
			$child->setParent($node);
		}
		$this->cleanup();
	}

	/**
	 * Wedges a node between a target node and its parent
	 * @param	Node
	 * @return	void
	 */
	public function wedge(Node $node) {
		$parent = $this->getParent();
		$parent->addChild($node);
		$node->addChild($this);

		$children = $parent->getChildren();
		foreach($children as $index => $child) {
			if($child === $this) {
				unset($children[$index]);
			}
		}
	}

	/**
	 * @todo	Fill this out
	 */
	public function findSibling(\Closure $search) {
		return $this->parent->findChild($search);
	}

	/**
	 * @todo	Fill this out
	 */
	public function findParent(\Closure $search) {
		if($search($this->parent)) {
			return $this->parent;
		} else {
			return $this->parent->findParent($search);
		}
		return false;
	} 
}