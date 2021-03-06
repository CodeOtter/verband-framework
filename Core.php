<?php

namespace Verband\Framework;

use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Process;
use Verband\Framework\Structure\Package;
use Verband\Framework\Structure\Settings;
use Verband\Framework\Structure\Workflow;
use Verband\Framework\Structure\Subject;
use Verband\Framework\Exceptions\ProcessHaltException;
use Verband\Framework\Exceptions\ApplicationHaltException;
use Verband\Framework\Process\Initialization;
use Verband\Framework\Caching\PhpCache;
use Verband\Framework\Caching\SettingsCache;
use Verband\Framework\Caching\PackageCache;
use Verband\Framework\Caching\FileCache;
use Symfony\Component\HttpFoundation\ResourceResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Verband\Framework\Test\VerbandTestTrait;
use Verband\Framework\Test\DbTest;
use Verband\Framework\Test\FunctionalTest;
use Verband\Framework\Test\UnitTest;
use Application\Startup;

/**
 * This is the core of the Verband Framework.  This contains the autoloader, the path configuration, 
 * the packages, and the context tree.
 */
class Core {

	const
		PATH_ROOT				= 'root',
		PATH_PACKAGES			= 'packages',
		PATH_APPLICATION		= 'application',
		PATH_CACHE				= 'Cache',
		PATH_LOGS				= 'Logs',
		PATH_ORM_SETTINGS		= '/Settings/orms',

		ENVIRONMENT_TEST		= 'test',
		ENVIRONMENT_LOCAL		= 'local',
		ENVIRONMENT_DEV			= 'dev',
		ENVIRONMENT_QA			= 'qa',
		ENVIRONMENT_STAGING		= 'staging',
		ENVIRONMENT_PRODUCTION	= 'production';

	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	private $environment = 'local';

	/**
	 * Paths where framework components can be found.
	 */
	private $paths = array();

	/**
	 * The autoloader the framework uses to discover components dynamically.
	 */
	private $autoloader = null;

	/**
	* The caching mechanism
	*/
	private $caches = null;

	/**
	 * An array of loaded packages the framework is using.
	 */
	private $packages = array();

	/**
	 * A tree of contexts the framework uses as its process flow. 
	 */
	private $contexts = array();

	/**
	 * A ParameterBag of settings
	 */
	private $settings;

	/**
	 * Framework initialization.
	 * @return	void
	 */
	public function init($environment = null) {
		try {
		    $this->autoloader = require_once(__DIR__ . '/../../../../autoload.php');

			$self = $this;
			// Set the environment
			if(isset($_SERVER['ENVIRONMENT'])) {
				$this->environment = $_SERVER['ENVIRONMENT'];
			} elseif($environment !== null) {
			    $this->environment = $enviornment;
			}

			// Set the error handler
			set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
				throw new \Exception($errstr . ' in ' . $errfile . ' on line ' . $errline);
			});

			set_exception_handler(function($exception) {
				echo $exception->getMessage() . "\n";
				echo $exception->getTraceAsString() . "\n";
			});

			/**
			 * Even catch fatal errors
			 */
			register_shutdown_function(function() use($self) {
				$error = error_get_last();
				if($error !== null) {
					echo $error['message']." in ".$error['file'].' '.$error['line']."\n";
				}
			});

			// Initialize the path management
			$this->initializePaths();

			// Establish caching
			FileCache::setCacheFile($this->paths[self::PATH_CACHE] . '/verband.cache');

			$packageCache = new PackageCache($this->paths[self::PATH_CACHE] . '/packages.cache');
			$packageCache->load();
			if(true || $packageCache->isEmpty()) {
			    // Load the Framework Package
			    $this->loadPackage('Verband\Framework');

    			// Initialize the 3rd party packages
    			$this->loadPackages();
    
    			// Load the application package
    			$this->loadPackage('Application');
    			foreach($this->getPackages() as $package) {
    			    $packageCache->set(get_class($package), $package->compact());
    			}
    			$packageCache->rebuild();
			} else {
			    // Load packages from cache
			    foreach($packageCache->getAll() as $packageName => $packageData) {
   			        require_once($packageData['directory'] . '/Startup.php');
   			        $package = new $packageName($packageData['directory']);
   		            $this->setPackage($package);
   			        //$package->registerNamespaces(/*$this->autoloader, */$this->getPath(self::PATH_PACKAGES));
			    }
			}

			// initialize contexts
			$this->contexts = new Context('Verband\Framework', null, new Initialization());
			$this->contexts->setState('framework', $this);

			// Initialize the packages
			$this->intializePackages();
		} catch(\Framework\Exceptions\ProcessHaltException $e) {
			// This process tree is terminated, end it now
			return;
		} catch(\Exception $e) {
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
			echo $this->contexts->traceHtml();
			debug_print_backtrace();
			exit;
		} 
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getEnvironment() {
		return $this->environment;
	}

	/**
	 * Returns a list of all loaded packages
	 * @return	array
	 */
	public function getPackages() {
		return $this->packages;
	}

	/**
	 * 
	 */
	public function getApplication() {
	    end($this->packages);
	    $result = current($this->packages);
	    reset($this->packages);
	    return $result;
	}
	
	/**
	 * Returns a specific package
	 * @param string	$packageName
	 * @return Package
	 */
	public function getPackage($packageName) {

	    // Creating an exception for the Application namespace
	    if(strcasecmp(substr($packageName, 0, 12), 'Application\\') == 0) {
	        $packageName = 'application';
	    } else {
	        $packageName = strtolower($packageName);
	    }

		if(isset($this->packages[$packageName])) {
			return $this->packages[$packageName];
		} else {
			return false;
		}
	}

	/**
	 * Returns a path.
	 * @param string	Name of a path.
	 * @return	string
	 */
	public function getPath($name) {
		if(isset($this->paths[$name])) {
			return $this->paths[$name];
		}
		return false;
	}

	/**
	 * Returns the autoloader
	 * @return \Framework\Autoloader
	 */
	public function getAutoloader() {
		return $this->autoloader;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * 
	 */
	public function getSetting($path, $default = null, $deep = true) {
		return $this->settings->get($path, $default, $deep);
	}

	/**
	 *
	 */
	public function setSetting($key, $value) {
		$this->settings->set($key, $value);
		return $this;
	}

	/**
	 *
	 */
	public function setEnvironment($environment) {
	    $this->environment = $environment;
	    return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function runWorkflow() {
		// Assemble the workflow
		$workflow = new Workflow($this);
		$applicationPackage = end($this->packages);
		reset($this->packages); 
		$this->contexts->addChild($workflow->assemble($workflow->gather($applicationPackage)));

		$this->executeWorkflow();
		$this->windDown();
	}
	
	/**
 	 * Tells the framework to execute the processes in all contexts.
	 * @param	\Framework\Context
	 * @param	mixed
	 * @return	void
	 */
	public function executeWorkflow($context = null, $lastResult = null) {
		if($context === null) {
			$context = $this->contexts;
		}

		try {
			$result = $context->run($lastResult);
		} catch(ProcessHaltException $e) {
			// This process tree is terminated, end it now
			return;
		} catch(ApplicationHaltException $e) {
			// This application is finished
			exit;
		} catch(\Exception $e) {
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
			echo $this->contexts->traceHtml($context->getNodeName());
			exit;
		} 

		foreach($context->getChildren() as $child) {
			$this->executeWorkflow($child, $result);
		}
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function windDown() {
		// Rebuild cache on the way out
		//$phpCache = new PhpCache($this->getPath(self::PATH_CACHE).'/verband.php', $this->getPath(self::PATH_ROOT));
		//$phpCache->build();
		FileCache::rebuild();
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function getConsole() {
		$em = $this->contexts->getState('entityManager');

		$cli = new \Symfony\Component\Console\Application('Doctrine Command Line Interface', \Doctrine\ORM\Version::VERSION);
		$cli->setCatchExceptions(true);
		$cli->setHelperSet(new \Symfony\Component\Console\Helper\HelperSet(array(
			'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
			'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
			'dialog' => new \Symfony\Component\Console\Helper\DialogHelper(),
		)));
		\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

		// Migrations
		$cli->addCommands(array(
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
			new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
		));
		
		return $cli;
	}
	
	/**
	 * Using https://github.com/symfony/symfony/tree/master/src/Symfony/Component/Console
	 */
	public function runConsole() {
		set_time_limit(0);
		$this->getConsole()->run($this->contexts->getState('arguments'));
		$this->contexts->getState('entityManager')->flush();
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function runWorker() {
		// @TODO: Create a test worker that autoloads... or just trust the damned Composer autoloader and solve all your problems you dense imbecile
		//require_once(__DIR__ . '/../../autoload.php');

		// @TODO: Make a test worker
		if($this->environment == self::ENVIRONMENT_TEST) {
		    $subject = new Subject($this->contexts);
    		UnitTest::setSubject($subject);
    		FunctionalTest::setSubject($subject);
    		DbTest::setSubject($subject);
    		// @TODO: Deal with fixtures here
		}
		//$this->contexts->getState('entityManager')->flush();
	}
	
	/**
	 * Establishes the paths to framework components.
	 * @TODO: Make this part of settings?
	 * @return	void
	 */
	private function initializePaths() {
		$this->paths[self::PATH_ROOT]			= realpath(__DIR__ . '/../../../../..');
		$this->paths[self::PATH_APPLICATION]	= $this->paths[self::PATH_ROOT] . '/Application';
		$this->paths[self::PATH_CACHE]			= $this->paths[self::PATH_ROOT] . '/Application/Cache';
		$this->paths[self::PATH_LOGS]			= $this->paths[self::PATH_ROOT] . '/Application/Logs';
		$this->paths[self::PATH_PACKAGES]		= $this->paths[self::PATH_ROOT] . '/packages';
	}

	/**
	 * Loads all contexts into the framework.
	 * @return	void
	 */
	private function loadPackages() {  
		$vendors = array_diff(scandir($this->paths[self::PATH_PACKAGES]), array('..', '.'));
		foreach($vendors as $vendor) {
			if(is_dir($this->paths[self::PATH_PACKAGES] . '/' . $vendor)) {
				$packages = array_diff(scandir($this->paths[self::PATH_PACKAGES] . '/' . $vendor), array('..', '.'));
				foreach($packages as $package) {
				    $startupName = $this->findStartup($this->paths[self::PATH_PACKAGES] . '/' . $vendor . '/' . $package);
				    if($startupName != 'Verband\Framework') {
                        $this->loadPackage($startupName);
				    }
				}
			}
		}
	}

	/**
	 * Loads a context into the framework.
	 * @param $packageName
	 */
	private function loadPackage($name) {

	    if(!$name) {
	        return false;
	    }

	    $name .= '\Startup';

        $startup = new $name();

        if($startup instanceof Package) {
            $this->setPackage($startup);
            return true;
        }
        return false;
	}
	
	/**
	 *
	 * @param unknown_type $path
	 */
	public function findStartup($path) {
	    if(!is_dir($path)) {
	        return false;
	    }

	    $results = array_diff(scandir($path), array('..', '.'));
	    $namespace = array();
	
	    if(count($results) == 1 && is_dir($path . '/' . current($results))) {
	        $namespace[0] = current($results);
	        $results = array_diff(scandir($path . '/' . $namespace[0]), array('..', '.'));
	        if(count($results) == 1 && is_dir($path . '/' . $namespace[0] . '/' . current($results))) {
	            $namespace[1] = current($results);
	        }
	    }

	    if($namespace && is_file($path . '/' . implode('/', $namespace) . '/' . 'Startup.php')) {
            return implode('\\', $namespace);
	    }

	    return false;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 * @param unknown_type $package
	 */
	public function setPackage($package) {
		$this->packages[strtolower($package->getName())] = $package;
		return $this;
	}

	/**
	 * If a environment specific configuration was not determined, default to the catch all
	 * @param unknown_type $package
	 */
	private function initializePackageConfiguration(Package $package) {
		$settingsFilename = $package->getDirectory() + '/Settings/config.' . $this->getEnvironment() . '.yml';
		if(!file_exists($settingsFilename)) {
			$settingsFilename = $package->getDirectory() . '/Settings/config.yml';
		}

		$packageSettings = new Settings($settingsFilename);
		$this->settings->add($packageSettings->getContents());
	}
	
	/**
	 * Initializes all packages.
	 * @return void
	 */
	private function intializePackages() {
	    $settingsCache = new SettingsCache();
	    if($settingsCache->isEmpty()) {
	        // Initialize settings
	        $this->settings = new ParameterBag();
	        $settings = new Settings($this->paths[self::PATH_APPLICATION] . '/Settings/config.yml');
	        $this->settings->add($settings->getContents());

	        foreach($this->packages as $package) {
	            // Load a packages settings
	            $this->initializePackageConfiguration($package);
	            $package->init($this->contexts);
	        }
	        $settingsCache->setAll($this->settings);
	    } else {
	        $this->settings = $settingsCache->getAll();
	        foreach($this->packages as $package) {
	            // Load a packages settings
	            $package->init($this->contexts);
	        }
	    }
	}
}