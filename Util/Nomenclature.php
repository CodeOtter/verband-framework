<?php 

namespace Verband\Framework\Util;

/**
 * Pluralize support from https://raw.github.com/FriendsOfSymfony/FOSRestBundle/master/Util/Pluralization.php
 */
use Verband\Framework\Core;

class Nomenclature {

	/**
	 * Uncountable words.
	 * @var array
	 */
	public static $uncountables = array(
		'equipment',
	    'information',
	    'rice',
	    'money',
	    'species',
	    'series',
	    'fish',
	    'sheep',
	    'media',
	    'content'
	);

	/**
	 * Irregular words.
	 * @var array
	 */
	public static $irregulars = array(
	    'person'  => 'people',
	    'man'     => 'men',
	    'child'   => 'children',
	    'sex'     => 'sexes',
	    'move'    => 'moves'
	);

	/**
	 * Singular rules.
	 * @var array
	 */
	public static $singulars = array(
		'/(quiz)zes$/i'         => '\1',
	    '/(matr)ices$/i'        => '\1ix',
	    '/(vert|ind)ices$/i'    => '\1ex',
	    '/^(ox)en/i'            => '\1',
	    '/(alias|status)es$/i'  => '\1',
	    '/([octop|vir])i$/i'    => '\1us',
	    '/(cris|ax|test)es$/i'  => '\1is',
	    '/(shoe)s$/i'           => '\1',
	    '/(o)es$/i'             => '\1',
	    '/(bus)es$/i'           => '\1',
	    '/([m|l])ice$/i'        => '\1ouse',
	    '/(x|ch|ss|sh)es$/i'    => '\1',
	    '/(m)ovies$/i'          => '\1ovie',
	    '/(s)eries$/i'          => '\1eries',
	    '/([^aeiouy]|qu)ies$/i' => '\1y',
	    '/([lr])ves$/i'         => '\1f',
	    '/(tive)s$/i'           => '\1',
	    '/(hive)s$/i'           => '\1',
	    '/([^f])ves$/i'         => '\1fe',
	    '/(^analy)ses$/i'       => '\1sis',
	    '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
	    '/([ti])a$/i'           => '\1um',
	    '/(n)ews$/i'            => '\1ews',
	    '/s$/i'                 => '',
	);

	/**
	 * Plural rules
	 * @var array
	 */
	public static $plurals = array(
		'/(quiz)$/i'                => '\1zes',
	    '/^(ox)$/i'                 => '\1en',
	    '/([m|l])ouse$/i'           => '\1ice',
	    '/(matr|vert|ind)ix|ex$/i'  => '\1ices',
	    '/(x|ch|ss|sh)$/i'          => '\1es',
	    '/([^aeiouy]|qu)y$/i'       => '\1ies',
	    '/(hive)$/i'                => '\1s',
	    '/(?:([^f])fe|([lr])f)$/i'  => '\1\2ves',
	    '/sis$/i'                   => 'ses',
	    '/([ti])um$/i'              => '\1a',
	    '/(buffal|tomat)o$/i'       => '\1oes',
	    '/(bu)s$/i'                 => '\1ses',
	    '/(alias|status)/i'         => '\1es',
	    '/(octop|vir)us$/i'         => '\1i',
	    '/(ax|test)is$/i'           => '\1es',
	    '/s$/i'                     => 's',
	    '/$/'                       => 's'
	);

	/**
	 * Converts an object to a fully-qualified namespace.
	 * @param	unknown_type $object
	 * @return	string
	 */
	protected static function convert($object) {
		if(is_object($object)) {
			return get_class($object);
		}
		return $object;
	}
	
	/**
	* Gets the namespace of an object.
	* @example	Vendor\Package\Directory\ClassName -> \Vendor\Package\Directory 
	* @param	Mixed
	* @return	string
	*/
	public static function getNamespace($value) {
		$value = self::convert($value);
		return substr($value, 0, strrpos($value, '\\'));
	}

	/**
	 * Gets the vendor of an object.
	 * @example	Vendor\Package\Directory\ClassName -> Vendor
	 * @param		mixed
	 * @return 		string 
	 */
	public static function getVendor($value) {
		$value = self::convert($value);
		return substr($value, 0, strpos($value, '\\'));
	}

	/**
	 * Gets the vendor and package of an object.
	 * @example	Vendor\Package\Directory\ClassName -> Vendor\Package
	 * @param		mixed
	 * @return 		string 
	 */
	public static function getVendorAndPackage($value) {
		$value = self::convert($value);
		$components = explode('\\', $value);
		return $components[0] . '\\' . $components[1];
	}

	/**
	* Gets the directory of an object.
	* @example	Vendor\Package\Directory\ClassName -> Directory
	* @param		mixed
	* @return 		string
	*/
	public static function getDirectory($value) {
		$value = self::convert($value);
		$components = explode('\\', $value);
		return $components[2];
	}

	/**
	* Gets the package name of an object.
	* @example		Vendor\Package\Directory\ClassName -> Package
	* @param		mixed
	* @return 		string
	*/
	public static function getPackage($value) {
		$value = self::convert($value);
		$components = explode('\\', $value);
		return $components[1];
	}
	
	/**
	 * Gets the class name of an object.
	 * @example	Vendor\Package\Directory\ClassName -> ClassName
	 * @param		mixed
	 * @return 		string 
	 */
	public static function getClass($value) {
		$value = self::convert($value);
		return substr($value, strrpos($value, '\\') + 1); 
	}

	/**
	 * Converts a namespace to a path
	 * @example	Vendor\Package\Directory\ClassName -> Vendor/Package/ClassName
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toPath($value) {
		$value = self::convert($value);
		return str_replace('\\', '/', $value);
	}

	/**
	 * Converts a path to a namespace
	 * @example	Vendor/Package/ClassName -> Vendor\Package\Directory\ClassName
	 * @param		mixed
	 * @return 		string 
	 */
	public static function pathToNamespace($path) {
		return str_replace('/', '\\', $path);
	}

	/**
	 * Converts an entity namespace to an ORM Path
	 * @example	Vendor\Package\Entity\EntityName -> Vendor/Package/Entity/Maps/Vendor.Package.Entity.EntityName.dmc.yml
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toOrmPath($value) {
		$value = self::convert($value);
		return self::toPath(self::getVendorAndPackage($value)) . Core::PATH_ORM_SETTINGS . '/' . str_replace('\\' , '.', $value) . '.dcm.yml';
	}

	/**
	* Converts an entity namespace to a repository namespace
	* @example	Vendor\Package\Entity\EntityName -> Vendor\Package\Repository\EntityNameRepository
	* @param		mixed
	* @return 		string
	*/
	public static function toRepositoryName($value) {
		$value = self::convert($value);
		return str_replace('\\Entity\\', '\\Repository\\', $value) . 'Repository';
	}
	
	/**
	* Converts an entity namespace to a service namespace
	* @example	Vendor\Package\Entity\EntityName -> Vendor\Package\Service\EntityNameService
	* @param		mixed
	* @return 		string
	*/
	public static function toServiceName($value) {
		$value = self::convert($value);
		return str_replace('\\Entity\\', '\\Service\\', $value) . 'Service';
	}
	
	/**
	 * Converts an entity namespace to a repository path
	 * @example	Vendor\Package\Entity\EntityName -> Vendor/Package/Repository/EntityNameRepository.php
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toRepositoryPath($value) {
		$value = self::convert($value);
		return self::toPath(self::getVendorAndPackage($value)) . '/Repository/' . self::getClass($value) . 'Repository.php';
	}
	
	/**
	 * Converts an entity namespace to a repository path
	 * @example	Vendor\Package\Entity\EntityName -> Vendor/Package/Maps/EntityName.json
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toRestMapName($value) {
		$value = self::convert($value);
		return str_replace('\\Entity\\', '\\Form\\', $value) . 'Form';
	}

	/**
	* Converts an entity namespace to a controller namespace
	* @example		Vendor\Package\Entity\EntityName -> Vendor\Package\Controllers\EntityNameController
	* @param		mixed
	* @return 		string
	*/
	public static function toControllerName($value) {
		$value = self::convert($value);
		return str_replace('\\Entity\\', '\\Controller\\', $value) . 'Controller';
	}
	
	/**
	 * Converts an entity namespace to a table name
	 * @example	Vendor\Package\Entity\EntityName -> package_entityname
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toTableName($value) {
		$value = self::convert($value);
		return strtolower(self::getVendor($value) . '_' . self::getClass($value));
	}
	
	/**
	 * Converts an entity namespace to an id field name
	 * @example	Vendor\Package\Entity\EntityName -> entityName_id
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toId($value) {
		$value = self::convert($value);
		return lcfirst(self::getClass($value)) . '_id';
	}

	/**
	 * Converts an entity namespace to an ORM association name
	 * @example	Vendor\Package\Directory\ClassName -> className
	 * @param		mixed
	 * @return 		string 
	 */
	public static function toAssociation($value) {
		$value = self::convert($value);
		return lcfirst(self::getClass($value));
	}
	
	/**
	 * Converts a word to the plural version of it.
	 * @example	table -> tables
	 * @param		string
	 * @return 		string 
	 */
    public static function pluralize($word)
    {
        $lowerCasedWord = strtolower($word);
        foreach (static::$uncountables as $uncountable) {
            if (substr($lowerCasedWord, (-1 * strlen($uncountable))) == $uncountable) {
                return $word;
            }
        }
        foreach (static::$irregulars as $plural => $singular) {
            if (preg_match('/(' . $plural . ')$/i', $word, $arr)) {
                return preg_replace(
                    '/(' . $plural . ')$/i',
                    substr($arr[0], 0, 1) . substr($singular, 1),
                    $word
                );
            }
        }
        foreach (static::$plurals as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return false;
    }

	/**
	 * Converts a word to the singular version of it.
	 * @example	tables -> table
	 * @param		string
	 * @return 		string 
	 */
    public static function singularize($word)
    {
        $lowerCasedWord = strtolower($word);
        foreach (static::$uncountables as $uncountable) {
            if (substr($lowerCasedWord, (-1 * strlen($uncountable))) == $uncountable) {
                return $word;
            }
        }
        foreach (static::$irregulars as $plural => $singular) {
            if (preg_match('/(' . $singular.')$/i', $word, $arr)) {
                return preg_replace(
                    '/(' . $singular . ')$/i',
                    substr($arr[0], 0, 1) . substr($plural, 1),
                    $word
                );
            }
        }
        foreach (static::$singulars as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }
}