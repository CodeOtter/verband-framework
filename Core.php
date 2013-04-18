<?php

namespace Verband\Framework;

require(__DIR__ . '/Structure/Autoloader.php');

use Verband\Framework\Caching\PhpCache;
use Verband\Framework\Http\ResourceResponse;
use Verband\Framework\Http\ParameterBag;
use Verband\Framework\Caching\FileCache;
use Verband\Framework\Process\Initialization;
use Verband\Framework\Structure\Autoloader;
use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Process;
use Verband\Framework\Structure\Package;
use Verband\Framework\Structure\Settings;
use Verband\Framework\Structure\Workflow;
use Verband\Framework\Structure\Autloader;
use Verband\Framework\Exceptions\ProcessHaltException;
use Verband\Framework\Exceptions\ApplicationHaltException;

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
	private $settings = array();

	/**
	 * Framework initialization.
	 * @return	void
	 */
	public function init() {
		try {
			$self = $this;
			// Set the environment
			if(isset($_SERVER['ENVIRONMENT'])) {
				$this->environment = $_SERVER['ENVIRONMENT'];
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

			// Initialize the autoloader
			$this->autoloader = new Autoloader($this->paths[self::PATH_ROOT]);
			$this->autoloader->setPath('Verband\Framework', $this->paths[self::PATH_PACKAGES]);

			// Load the Framework Package
			$this->loadPackage('Verband\Framework',$this->paths[self::PATH_PACKAGES]);

			//$this->autoloader->setPath('Verband\Framework', $this->paths[self::PATH_PACKAGES]);
			//$this->autoloader->setPath('Symfony\Component', $this->paths[self::PATH_PACKAGES] . '/{first.lc}/{2.lc}/{first}/Component/{>1}');

			// initialize contexts
			$this->contexts = new Context('Verband\Framework', null, new Initialization());
			$this->contexts->setState('framework', $this);	

			// Initialize settings
			$this->settings = new ParameterBag();
			$settings = new Settings($this->paths[self::PATH_APPLICATION] . '/Settings/config.yml');
			$this->settings->add($settings->getContents());

			// Initialize the 3rd party packages
			$this->loadPackages();

			// Load the application package
			$this->loadPackage('application', $this->paths[self::PATH_ROOT]);

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
	 * Returns a specific package
	 * @param string	$packageName
	 * @return Package
	 */
	public function getPackage($packageName) {
		$packageName = strtolower($packageName);
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
	 * Enter description here ...
	 */
	public function runWorkflow() {
		// Assemble the workflow
		$workflow = new Workflow($this);
		$applicationPackage = end($this->packages);
		reset($this->packages); 
		$this->contexts->addChild($workflow->assemble($workflow->gather($applicationPackage)));

		$this->executeWorkflow();
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

		$this->windDown();
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
			'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
		)));
		\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);
		return $cli;
	}
	
	/**
	 * Using https://github.com/symfony/symfony/tree/master/src/Symfony/Component/Console
	 */
	public function runConsole() {
		set_time_limit(0);
		$input = new \Symfony\Component\Console\Input\ArgvInput();
		$this->getConsole()->run($input);
		$this->contexts->getState('entityManager')->flush();
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function runWorker() {
		$this->contexts->getState('entityManager')->flush();
	}
	
	/**
	 * Establishes the paths to framework components.
	 * @TODO: Make this part of settings?
	 * @return	void
	 */
	private function initializePaths() {
		$this->paths[self::PATH_ROOT]			= realpath(__DIR__ . '/../../..');
		$this->paths[self::PATH_APPLICATION]	= $this->paths[self::PATH_ROOT] . '/application';
		$this->paths[self::PATH_CACHE]			= $this->paths[self::PATH_ROOT] . '/application/Cache';
		$this->paths[self::PATH_LOGS]			= $this->paths[self::PATH_ROOT] . '/application/Logs';
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
					if(is_dir($this->paths[self::PATH_PACKAGES] . '/' . $vendor . '/' . $package)) {
						$this->loadPackage($vendor.'\\'.$package, $this->paths[self::PATH_PACKAGES]);
					}
				}
			}
		}
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
	 * Loads a context into the framework.
	 * @param $packageName
	 */
	private function loadPackage($name, $pathPrefix) {
		$name = strtolower($name);
		$path = $pathPrefix . '/' . str_replace('\\', '/', $name);
		if(file_exists($path . '/Startup.php')) {
			require_once($path . '/Startup.php');
			//$this->autoloader->setPath($name, $this->getPath(self::PATH_PACKAGES));
			$packageName = '\\' . $name . '\Startup';
			$package = new $packageName($path);
			if($package instanceof Package) {
				$this->setPackage($package);
				$package->registerNamespaces($this->autoloader, $this->getPath(self::PATH_PACKAGES));
				return $package;
			}
		}
		return null;
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
		foreach($this->packages as $package) {
			// Load a packages settings
			$this->initializePackageConfiguration($package);
			$package->init($this->contexts);
		}
	}
}