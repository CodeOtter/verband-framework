<?php 

namespace Verband\Framework\Http;

use Verband\Framework\Util\MimeType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response represents an HTTP response.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ResourceResponse extends Response {

	public function __construct($fileMame, $fileContents, $headers = array()) {
		parent::__construct($fileContents, 200, array_merge($headers, array(
			'content-type'	=> MimeType::ExtensionToType($fileMame)
		)));
	}
}