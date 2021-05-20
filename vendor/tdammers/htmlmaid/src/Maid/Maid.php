<?php

namespace Maid;

/**
 * A friendly cleaner.
 */
class Maid {
	private $tagNameWhitelist;
	private $tagNameDeletelist;
	private $attribWhitelist;
	private $stripComments;
	private $fuseText;
	private $collapseWhitespace;
	private $outputFormat;
	private $fullDocument;
	private $errors;
	private $forceOldPhpWorkaround;

	/**
	 * @param array $options - Associative array containing options. Currently
	 *     supported options are:
	 *     'allowed-tags' (array) - A list of lower-case tag names. Maid will
	 *                              keep tags of this type intact.
	 *     'removed-tags' (array) - A list of lower-case tag names. Maid will
	 *                              remove tags of this type entirely,
	 *                              including their contents.
	 *     'allowed-attribs' (array) - A list of attribute names. Maid will
	 *                              only keep attributes on this list.
	 *     'strip-comments' (bool) - If set, XML comments will be stripped.
	 *     'full-document' (bool) - If set, Maid will assume the input is a
	 *                              complete HTML document. Otherwise, string
	 *                              input will be treated as a document
	 *                              fragment, and not wrapped in a document
	 *                              except for internal processing.
	 *     'output-format' (string) - one of 'text', 'html'. Default: 'html'.
	 *     'collapse-whitespace' (bool) - Not currently implemented.
	 *     'force-old-php-workaround' (bool) - If set, force the PHP < 5.4
	 *                     workaround even on PHP 5.4 and newer.
	 */
	public function __construct($options = array()) {
		$this->errors = array();
		if (isset($options['allowed-tags'])) {
			$this->tagNameWhitelist = $options['allowed-tags'];
		}
		else {
			$this->tagNameWhitelist = array(
					// Document structure
					'html', 'head', 'body', 'section', 'footer',
					'div', 'p', 'strong', 'em',
					'i', 'b', 'u', 's', 'sup', 'sub',
					'li', 'ul', 'ol', 'menu',
					'blockquote', 'pre', 'code', 'tt',
					'hr', 'br',
					'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
					'dd', 'dl', 'dh',
					'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr');
		}

		if (isset($options['remove-tags'])) {
			$this->tagNameDeletelist = $options['remove-tags'];
		}
		else {
			$this->tagNameDeletelist = array();
		}

		if (isset($options['allowed-attribs'])) {
			$this->attribWhitelist = $options['allowed-attribs'];
		}
		else {
			$this->attribWhitelist = array('id', 'class', 'name', 'value');
		}

		if (isset($options['strip-comments'])) {
			$this->stripComments = (bool)$options['strip-comments'];
		}
		else {
			$this->stripComments = false;
		}

		if (isset($options['collapse-whitespace'])) {
			$this->collapseWhitespace = (bool)$options['collapse-whitespace'];
		}
		else {
			$this->collapseWhitespace = false;
		}

		if (isset($options['full-document'])) {
			$this->fullDocument = (bool)$options['full-document'];
		}
		else {
			$this->fullDocument = false;
		}

		if (isset($options['force-old-php-workaround'])) {
			$this->forceOldPhpWorkaround = (bool)$options['force-old-php-workaround'];
		}
		else {
			$this->forceOldPhpWorkaround = false;
		}

		if (isset($options['output-format'])) {
			$this->outputFormat = $options['output-format'];
		}
		else {
			$this->outputFormat = 'html';
		}
	}

	private function loadDocument($html, $wrap = false) {
		$prevEntDisabled = libxml_disable_entity_loader(true);
		$prevInternalErrors = libxml_use_internal_errors(true);
		$srcDoc = new \DOMDocument();
		if (!$srcDoc->loadHTML($html)) {
			foreach (libxml_get_errors() as $error) {
				$this->errors[] = $error;
			}
		}
		libxml_disable_entity_loader($prevEntDisabled);
		libxml_use_internal_errors($prevInternalErrors);
		return $srcDoc;
	}

	/**
	 * Clears Maid's internal error buffer.
	 */
	public function clearErrors() {
		$this->errors = array();
	}

	/**
	 * Gets Maid's internal error buffer, an array of libxml error messages.
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Performs cleaning according to current options (@see __construct() for
	 * available options).
	 * @param mixed $thing The thing to clean. Can be a DOMDocument, a DOMNode,
	 *                     or (the most common case) a string.
	 * @return mixed Return type depends on the $thing argument. If $thing is a
	 *                     string, the return value is also a string. If $thing
	 *                     is a DOMDocument, the return value is a new,
	 *                     independent DOMDocument. If $thing is a DOMNode, the
	 *                     return value is a DOMNodeList, with nodes whose 
	 *                     ownerDocument is a new, undisclosed DOMDocument.
	 */
	public function clean($thing) {
		if ($thing instanceof \DOMDocument) {
			return $this->cleanDocument($thing);
		}
		elseif ($thing instanceof \DOMNode) {
			$srcNode = $thing;
			$dstDoc = new \DOMDocument();
			$dstNode = $dstDoc->createElement('html');
			$this->cleanNode($srcNode, $dstNode);
			return $dstNode->childNodes;
		}
		elseif (is_string($thing)) {
			if ($this->fullDocument) {
				$srcDoc = $this->loadDocument($thing);
				$dstDoc = $this->cleanDocument($srcDoc);
				switch ($this->outputFormat) {
					case 'text':
						return $dstDoc->textContent;
					case 'html':
					default:
						return $dstDoc->saveHTML();
				}
			}
			else {
				$wrapper =
					'<!DOCTYPE html>' .
					'<html>' .
					'<head>' .
					'<meta charset="utf-8"/>' .
					'<meta http-equiv="Content-type" content="text/html;charset=utf-8"/>' .
					'</head>' .
					'<body>' .
					'<div>%s</div>' .
					'</body>' .
					'</html>';
				$srcDoc = $this->loadDocument(sprintf($wrapper, $thing));
				$srcParent = $srcDoc->documentElement->firstChild->nextSibling->firstChild;

				$dstDoc = $this->loadDocument(sprintf($wrapper, ''));
				$dstParent = $dstDoc->documentElement->firstChild->nextSibling->firstChild;

				for ($i = 0; $i < $srcParent->childNodes->length; ++$i) {
					$this->cleanNode($srcParent->childNodes->item($i), $dstParent);
				}
				$html = '';
				for ($i = 0; $i < $dstParent->childNodes->length; ++$i) {
					switch ($this->outputFormat) {
						case 'text':
							$html .= $dstParent->childNodes->item($i)->textContent;
							break;
						case 'html':
						default:
							$html .= self::renderFragment(
										$dstParent->childNodes->item($i),
										$this->forceOldPhpWorkaround);
							break;
					}
				}
				return $html;
			}
		}
	}

	/**
	 * A very very ugly wrapper around DOMDocument::saveHTML(); it is required
	 * because PHP versions before 5.4* don't support writing out individual
	 * nodes from a DOMDocument.
	 *
	 * The approach taken here is inspired by Symfony, particularly this fix:
	 *
	 * https://github.com/jakzal/symfony/commit/a4e3ebf38555bec17dd29dca1bf6e85abdc5769b
	 *
	 * For some reason, DOMDocument::saveHTML() produces a malformed document
	 * (text node at top level), but in exactly the right way...
	 *
	 * * According to the PHP documentation (http://php.net/manual/en/domdocument.savehtml.php),
	 * the extra parameter was added in version 5.3.6, but tests reveal that
	 * even PHP 5.3.23 doesn't support this parameter yet. Digging through
	 * PHP's git repository and bug tracker, we found a change that was
	 * intended to add this parameter, but in a way that doesn't actually
	 * enable it. This was fixed later, so PHP 5.4 will work fine.
	 */
	public static function renderFragment(\DOMNode $node, $forceWorkaround = false) {
		if (version_compare(PHP_VERSION, '5.4', '>=') && !$forceWorkaround) {
			return $node->ownerDocument->saveHTML($node);
		}
		else {
			$doc = new \DOMDocument('1.0', 'UTF-8');
			$wrapper =
				'<!DOCTYPE html>' .
				'<html>' .
				'<head>' .
				'<meta charset="utf-8"/>' .
				'<meta http-equiv="Content-type" content="text/html;charset=utf-8"/>' .
				'</head>' .
				'<body>' .
				'</body>' .
				'</html>';
			$doc->loadHTML($wrapper);
			$doc->appendChild($doc->importNode($node, true));
			$html = $doc->saveHTML();
			$html = preg_replace('/\n$/', '', $html);
			$html = preg_replace('/^[\s\S]*?\/html\>/', '', $html);
			return $html;
		}
	}

	/**
	 * Clean a DOMDocument.
	 * Calling `Maid::clean($srcDoc)` is equivalent.
	 */
	public function cleanDocument(\DOMDocument $srcDoc) {
		$dstDoc = $this->loadDocument('<html></html>');
		for ($i = 0; $i < $srcDoc->documentElement->childNodes->length; ++$i) {
			$srcNode = $srcDoc->documentElement->childNodes->item($i);
			$this->cleanNode($srcNode, $dstDoc->documentElement);
		}
		return $dstDoc;
	}

	private function cleanNode(\DOMNode $node, \DOMNode $parentNode) {
		switch ($node->nodeType) {
			case XML_ELEMENT_NODE:
				if (in_array(strtolower($node->nodeName), $this->tagNameDeletelist)) {
					return;
				}
				if (in_array(strtolower($node->nodeName), $this->tagNameWhitelist)) {
					// Yay, it's whitelisted!
					$container = $parentNode->ownerDocument->createElement($node->nodeName);
					foreach ($node->attributes as $attr) {
						if (in_array($attr->name, $this->attribWhitelist))
							$container->setAttribute($attr->name, $attr->value);
					}
					$parentNode->appendChild($container);
				}
				else {
					$container = $parentNode;
				}
				for ($i = 0; $i < $node->childNodes->length; ++$i) {
					$this->cleanNode($node->childNodes->item($i), $container);
				}
				break;
			case XML_COMMENT_NODE:
				if (!$this->stripComments) {
					$parentNode->appendChild($parentNode->ownerDocument->importNode($node, false));
				}
				break;
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$parentNode->appendChild($parentNode->ownerDocument->importNode($node, false));
				break;
			default:
				return;
		}
	}
}

// vim:noexpandtab
