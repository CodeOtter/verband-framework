<?php 

namespace Verband\Framework\Http;

use Verband\Framework\Util\MimeType;

/**
 * Response represents an HTTP response.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ResourceResponse extends Response {

	public function __construct($fileMame, $fileContents) {
		parent::__construct($fileContents, 200, array(
			'content-type'	=> MimeType::ExtensionToType($fileMame)
		));
	}
}