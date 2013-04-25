<?php 

namespace Verband\Framework;

use Verband\Framework\Structure\Package;

/**
 * 
 * @author Programmer
 *
 */
class Startup extends Package {

	/**
	 * 
	 */
	public function init($contexts) {
	}
	
	/**
	 * Set custom namespaces
	 * @see Framework.Package::getNamespaces()
	 * @return array
	 */
	public function getNamespaces($packagesPath) {
		return array(
			'Symfony\Component\HttpFoundation' => $packagesPath . '/{first.lc}/http-foundation/{first}/Component/{>1}',
			'Symfony\Component' => $packagesPath . '/{first.lc}/{2.lc}/{first}/Component/{>1}'
		);
	}
}