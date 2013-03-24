<?php 

namespace Verband\Framework\Util;

/**
 * 
 * Enter description here ...
 * @author 12dCode
 *
 */
class RelationshipDetector {

	const
		UNKNOWN			= 0,
		VASSAL			= 1,
		KING			= 2,
		FOLLOWER		= 3,
		IDOL			= 4,
		CITIZEN			= 5,
		ARBITER			= 6,
		STUDENT			= 7,
		TEACHER			= 8,
		CHILD_PEER		= 9,
		PARENT_PEER		= 10,
		PLEBEIAN		= 11,
		OLIGARCH		= 12,
		CHILD_GROUP		= 13,
		PARENT_GROUP	= 14;
	
	private static $names = array(
		'unknown',
		'vassal',
		'king',
		'follower',
		'idol',
		'citizen',
		'arbiter',
		'student',
		'teacher',
		'childPeer',
		'parentPeer',
		'plebeian',
		'oligarch',
		'childGroup',
		'parentGroup'
	);

	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	private $entityManager = null;
	
	private $associationCache = array();

	public static function getName($flag) {
		if(isset(self::$names[$flag])) {
			return self::$names[$flag];
		}
		self::$names[self::UNKNOWN];
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $entityManager
	 */
	public function __construct($entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $entityName
	 */
	private function getAssociations($entityName) {
		if(!isset($this->associationCache[$entityName])) {
			$this->associationCache[$entityName] = $this->entityManager->getClassMetadata($entityName)->associationMappings;
		}
		return $this->associationCache[$entityName];
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $entity
	 * @param unknown_type $parentEntity
	 */
	public function detect($entityName, $parentEntity) {
		//if($parentEntity === null) {
			// No parent?  Standalone complex!
			//return 'standalone';		
		//}

		if(is_object($entityName)) {
			$entityName = get_class($entityName);
		}

		if($parentEntity === null) {
			$parentEntityName = null;
		} else {
			$parentEntityName = get_class($parentEntity);
		}

	 	if($this->isVassal($entityName, $parentEntityName)) {
			return self::VASSAL;
		} elseif($this->isKing($entityName, $parentEntityName)) {
			return self::KING;
		} elseif($this->isCitizen($entityName, $parentEntityName)) {
			return self::CITIZEN;
		} elseif($this->isArbiter($entityName, $parentEntityName)) {
			return self::ARBITER;
		} elseif($this->isTeacher($entityName, $parentEntityName)) {
			return self::TEACHER;
		} elseif($this->isChildPeer($entityName, $parentEntityName)) {
			return self::CHILD_PEER;
		} elseif($this->isParentPeer($entityName, $parentEntityName)) {
			return self::PARENT_PEER;
		} elseif($this->isOligarch($entityName, $parentEntityName)) {
			return self::OLIGARCH;
		} elseif($this->isChildGroup($entityName, $parentEntityName)) {
			return self::CHILD_GROUP;
		} elseif($this->isParentGroup($entityName, $parentEntityName)) {
			return self::PARENT_GROUP;
		} elseif($this->isFollower($entityName, $parentEntityName)) {
			return self::FOLLOWER;
		} elseif($this->isIdol($entityName, $parentEntityName)) {
			return self::IDOL;
		} elseif($this->isStudent($entityName, $parentEntityName)) {
			return self::STUDENT;
		} elseif($this->isPlebeian($entityName, $parentEntityName)) {
			return self::PLEBEIAN;
		} else {
			// No relationships pass, this is a standalone API
			return self::UNKNOWN;
		}
	}

	/**
	 * 1->(M) relationship
	 * @return boolean
	 */
	public function isVassal($entityName, $parentEntityName) {
		if($parentEntityName === null) {
			return false;
		}
		$associations = $this->getAssociations($entityName);
		if($associations) {
			return false;
		}

		$parentAssociations = $this->getAssociations($parentEntityName);
		foreach($parentAssociations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY &&
				$mapping['targetEntity'] == $entityName &&
				$mapping['sourceEntity'] == $parentEntityName &&
				$mapping['isOwningSide'] == true &&
				$mapping['inversedBy'] === null &&
				$mapping['mappedBy'] === null &&
				isset($mapping['joinTable']['inverseJoinColumns'][0]['unique']) &&
				$mapping['joinTable']['inverseJoinColumns'][0]['unique'] == true) {
				return true;
			}
		}
		return false;
		
	}
	
	/**
	 * (1)->M relationship
	 * @return boolean
	 */
	public function isKing($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}
		$associations = $this->getAssociations($entityName);

		foreach($associations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY &&
				$mapping['targetEntity'] !== null &&
				$mapping['sourceEntity'] == $entityName &&
				$mapping['isOwningSide'] == true &&
				$mapping['inversedBy'] === null &&
				$mapping['mappedBy'] === null &&
				isset($mapping['joinTable']['inverseJoinColumns'][0]['unique']) &&
				$mapping['joinTable']['inverseJoinColumns'][0]['unique'] == true) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 1<-(M) relationship
	 * @return boolean
	 */
	public function isFollower($entityName, $parentEntityName) {
		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		$parentAssociations = $this->getAssociations($parentEntityName);
		foreach($associations as $mapping) {
			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE &&
				$mapping['inversedBy'] === null &&
				$mapping['isOwningSide'] == true &&
				$mapping['targetEntity'] == $parentEntityName &&
				$mapping['sourceEntity'] == $entityName) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (1)<-M relationship
	 * @return boolean
	 */
	public function isIdol($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}
		
		$associations = $this->getAssociations($entityName);

		if($associations) {
			return false;
		}
		return true;
	}
	
	/**
	 * 1<->(M) relationship
	 * @return boolean
	 */
	public function isCitizen($entityName, $parentEntityName) {
		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		$parentAssociations = $this->getAssociations($parentEntityName);
	
		foreach($associations as $mapping) {
			if(isset($parentAssociations[$mapping['inversedBy']])) {
				$parentMapping = $parentAssociations[$mapping['inversedBy']];
			} else {
				continue;
			}

			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE && 
				$mapping['targetEntity'] == $parentEntityName && 
				$mapping['sourceEntity'] == $entityName && 
				$mapping['isOwningSide'] == true &&
				$mapping['mappedBy'] === null &&
				$mapping['inversedBy'] == $parentMapping['fieldName'] &&
				$parentMapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY &&
				$parentMapping['inversedBy'] === null &&
				$parentMapping['targetEntity'] == $entityName &&
				$parentMapping['sourceEntity'] == $parentEntityName &&
				$parentMapping['mappedBy'] == $mapping['fieldName'] &&
				$parentMapping['isOwningSide'] == false) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (1)<->M relationship
	 * @return boolean
	 */
	public function isArbiter($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
	
		foreach($associations as $mapping) {
			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY && 
				$mapping['inversedBy'] === null && 
				$mapping['sourceEntity'] == $entityName &&
				$mapping['mappedBy'] !== null &&
				$mapping['isOwningSide'] == false) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 1->(1) relationship
	 * @return boolean
	 */
	public function isStudent($entityName, $parentEntityName) {
		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		if($associations) {
			return false;
		}

		$parentAssociations = $this->getAssociations($parentEntityName);

		foreach($parentAssociations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE && 
				$mapping['targetEntity'] == $entityName && 
				$mapping['sourceEntity'] == $parentEntityName && 
				$mapping['isOwningSide'] == true &&
				$mapping['mappedBy'] === null &&
				$mapping['inversedBy'] === null && 
				!isset($mapping['joinTable'])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (1)->1 relationship
	 * @return boolean
	 */
	public function isTeacher($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);

		foreach($associations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE &&
			$mapping['inversedBy'] === null &&
			$mapping['targetEntity'] !== null &&
			$mapping['sourceEntity'] == $entityName &&
			$mapping['mappedBy'] === null &&
			$mapping['isOwningSide'] == true  && 
			!isset($mapping['joinTable'])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 1<->(1) relationship
	 * @return boolean
	 */
	public function isChildPeer($entityName, $parentEntityName) {

		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		$parentAssociations = $this->getAssociations($parentEntityName);

		foreach($associations as $mapping) {
			if(isset($parentAssociations[$mapping['mappedBy']])) {
				$parentMapping = $parentAssociations[$mapping['mappedBy']];
			} else {
				continue;
			}

			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE && 
				$mapping['targetEntity'] == $parentEntityName && 
				$mapping['sourceEntity'] == $entityName && 
				$mapping['isOwningSide'] == false &&
				$mapping['mappedBy'] === $parentMapping['fieldName'] &&
				$mapping['inversedBy'] === null &&
				$parentMapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE && 
				$parentMapping['inversedBy'] == $mapping['fieldName'] && 
				$parentMapping['targetEntity'] == $entityName && 
				$parentMapping['sourceEntity'] == $parentEntityName &&
				$parentMapping['mappedBy'] === null &&
				$parentMapping['isOwningSide'] == true) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (1)<->1 relationship
	 * @return boolean
	 */
	public function isParentPeer($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
	
		foreach($associations as $mapping) {
			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE && 
				$mapping['inversedBy'] !== null && 
				$mapping['sourceEntity'] == $entityName &&
				$mapping['mappedBy'] === null &&
				$mapping['isOwningSide'] == true) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * M->(M) relationship
	 */
	public function isPlebeian($entityName, $parentEntityName) {
		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		if($associations) {
			return false;
		}

		$parentAssociations = $this->getAssociations($parentEntityName);

		foreach($parentAssociations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && 
				$mapping['targetEntity'] == $entityName && 
				$mapping['sourceEntity'] == $parentEntityName && 
				$mapping['isOwningSide'] == true &&
				$mapping['mappedBy'] === null &&
				$mapping['inversedBy'] === null && 
				!isset($mapping['joinTable']['inverseJoinColumns'][0]['unique'])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (M)->M relationship
	 */
	public function isOligarch($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}
		
		$associations = $this->getAssociations($entityName);
		
		foreach($associations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && 
				$mapping['targetEntity'] !== null && 
				$mapping['sourceEntity'] == $entityName && 
				$mapping['isOwningSide'] == true &&
				$mapping['mappedBy'] === null &&
				$mapping['inversedBy'] === null && 
				!isset($mapping['joinTable']['inverseJoinColumns'][0]['unique'])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * M<->(M) relationship
	 * @return boolean
	 */
	public function isChildGroup($entityName, $parentEntityName) {

		if($parentEntityName === null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);
		$parentAssociations = $this->getAssociations($parentEntityName);
		
		foreach($associations as $mapping) {
			if(isset($parentAssociations[$mapping['mappedBy']])) {
				$parentMapping = $parentAssociations[$mapping['mappedBy']];
			} else {
				continue;
			}

			if(
				$mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && 
				$mapping['targetEntity'] == $parentEntityName && 
				$mapping['sourceEntity'] == $entityName && 
				$mapping['isOwningSide'] == false &&
				$mapping['mappedBy'] === $parentMapping['fieldName'] &&
				$mapping['inversedBy'] === null &&
				$parentMapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && 
				$parentMapping['inversedBy'] == $mapping['fieldName'] && 
				$parentMapping['targetEntity'] == $entityName && 
				$parentMapping['sourceEntity'] == $parentEntityName &&
				$parentMapping['mappedBy'] === null &&
				$parentMapping['isOwningSide'] == true) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * (M)<->M relationship
	 * @return boolean
	 */
	public function isParentGroup($entityName, $parentEntityName) {
		if($parentEntityName !== null) {
			return false;
		}

		$associations = $this->getAssociations($entityName);

		foreach($associations as $mapping) {
			if($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && 
				$mapping['inversedBy'] !== null && 
				$mapping['targetEntity'] !== null && 
				$mapping['sourceEntity'] == $entityName &&
				$mapping['mappedBy'] === null &&
				$mapping['isOwningSide'] == true) {
				return true;
			}
		}
		return false;
	}
}
