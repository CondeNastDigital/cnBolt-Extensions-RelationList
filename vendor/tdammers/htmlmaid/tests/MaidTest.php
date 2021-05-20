<?php

mb_internal_encoding('utf-8');
mb_http_output('utf-8');

include dirname(__FILE__) . '/../src/Maid/Maid.php';

class MaidTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider renderFragmentProvider
     */
    public function testRenderFragment($node, $expected)
    {
        $actual54 = \Maid\Maid::renderFragment($node, false);
        $actual53 = \Maid\Maid::renderFragment($node, true);
        $this->assertEquals($actual54, $expected);
        $this->assertEquals($actual53, $expected);
    }

    public function renderFragmentProvider() {
        $result = array();
        // This is kind of filthy: we're pasting a bit of workaround
        // code from Maid here... but if that's what it takes...
        $html =
            '<?xml version="1.0" encoding="utf-8" >' .
            '<!DOCTYPE html>' .
            '<html>' .
            '<head>' .
            '<meta http-equiv="Content-type" content="text/html;charset=utf-8"/>' .
            '<meta charset="utf-8"/>' .
            '</head>' .
            '<body>' .
            '<div></div>' .
            '</body>' .
            '</html>';
		$prevEntDisabled = libxml_disable_entity_loader(true);
		$prevInternalErrors = libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		if (!$doc->loadHTML($html)) {
			foreach (libxml_get_errors() as $error) {
				echo $error;
			}
		}
		libxml_disable_entity_loader($prevEntDisabled);
		libxml_use_internal_errors($prevInternalErrors);

        // Now for the actual tests.

        // Straightforward: just a <p> with a bit of text inside.
        $node = $doc->createElement('p', 'Hello');
        $expected = '<p>Hello</p>';
        $result[] = array($node, $expected);

        // Some plain text
        $node = $doc->createTextNode('Hello');
        $expected = 'Hello';
        $result[] = array($node, $expected);

        // UTF-8 content
        $node = $doc->createTextNode('Íṅŧèřñåṫıøńäł');
        $expected = 'Íṅŧèřñåṫıøńäł';
        $result[] = array($node, $expected);

        // Respect trailing whitespace
        $node = $doc->createElement('i', 'Hello ');
        $expected = '<i>Hello </i>';
        $result[] = array($node, $expected);

        // Handle nested elements correctly
        $node = $doc->createElement('p');
        $inner = $doc->createElement('strong', 'Hello');
        $node->appendChild($inner);
        $expected = '<p><strong>Hello</strong></p>';

        // Handle multiple child elements correctly
        $node = $doc->createElement('p');
        $inner = $doc->createElement('strong', 'Hello');
        $node->appendChild($inner);
        $node->appendChild($doc->createTextNode(', '));
        $inner = $doc->createElement('i', 'world');
        $node->appendChild($inner);
        $expected = '<p><strong>Hello</strong>, <i>world</i></p>';

        $result[] = array($node, $expected);

        return $result;
    }

    /**
     * @dataProvider cleanProvider
     */
    public function testClean($maidOptions, $htmlSrc, $expected)
    {
        // Test plain functionality
        $maid = new \Maid\Maid($maidOptions);
        $actual = $maid->clean($htmlSrc);
        $this->assertEquals($actual, $expected);

        // Test with forced workaround
        $maidOptions['force-old-php-workaround'] = true;
        $maid = new \Maid\Maid($maidOptions);
        $actual = $maid->clean($htmlSrc);
        $this->assertEquals($actual, $expected);
    }

    public function cleanProvider()
    {
        $defOptions = array();
        $result = array();
        // Plain text
        $result[] = array(
                $defOptions,
                'foobar',
                'foobar');
        // Keep trailing whitespace
        $result[] = array(
                $defOptions,
                'foobar ',
                'foobar ');
        // Keep "sane" tags
        $result[] = array(
                $defOptions,
                'foo<i>bar</i>',
                'foo<i>bar</i>');
        // Remove "insane" tags
        $result[] = array(
                $defOptions,
                'foo<remove>bar</remove>',
                'foobar');
        // Keep inline whitespace intact
        $result[] = array(
                $defOptions,
                'foo  bar',
                'foo  bar');
        // Handle entities correctly
        $result[] = array(
                $defOptions,
                'foo&amp;bar',
                'foo&amp;bar');
        // Keep utf-8 content
        $result[] = array(
                $defOptions,
                'Íṅŧèřñåṫıøńäł',
                'Íṅŧèřñåṫıøńäł');

        return $result;
    }

}
