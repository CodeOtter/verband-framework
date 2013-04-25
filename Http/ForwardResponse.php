<?php 

namespace Verband\Framework\Http;

/**
 * Response represents an HTTP response.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ForwardResponse extends Response {

	public function __construct($location) {
		parent::__construct('', 303, array(
			'location'	=> $location
		));
	}
}