<?php 

namespace Verband\Framework;

use Verband\Framework\Structure\Package;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * 
 * @author Programmer
 *
 */
class Startup extends Package {

	/**
	 * Load CLI arguments to override settings
	 */
	public function init($contexts) {
	    $argv = null;
	    if(!isset($_SERVER['argv'])) {
	        $argv = array();
	    }

        $input = new ArgvInput($argv);
        
        if($argv === null) {
    	    $environment = $input->getParameterOption('--env');
    
            if($environment) {
                $contexts->getState('framework')->setEnvironment($environment);
            }
	    }

	    $contexts->setState('arguments', $input);
	}
	
	/**
	 * Set custom namespaces
	 * @see Framework.Package::getNamespaces()
	 * @return array
	 */
	/*public function getNamespaces($packagesPath) {
		return array(
			'Symfony\Component\HttpFoundation' => $packagesPath . '/{first.lc}/http-foundation/{first}/Component/{>1}',
			'Symfony\Component' => $packagesPath . '/{first.lc}/{2.lc}/{first}/Component/{>1}'
		);
	}*/
}