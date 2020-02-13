<?php


namespace UWebPro\Tree\Traits;


use DOMElement;
use SimpleXMLElement;

trait DOM
{
    protected function asHTML($input)
    {
        if ($input instanceof SimpleXMLElement) {
            $node = dom_import_simplexml($input);
        }
        if ($input instanceof DOMElement) {
            $node = &$input;
        }
        if (isset($node)) {
            $dom = $node->ownerDocument;
        }
        if ($input instanceof \DOMDocument) {
            $dom = $input;
        }
        return isset($dom) ? $dom->saveHTML($node ?? null) : null;
    }
}