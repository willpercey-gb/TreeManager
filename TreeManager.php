<?php

namespace UWebPro\Tree;

use DOMDocument;
use DOMElement;
use SimpleXMLElement;
use UWebPro\Tree\WebRender as Render;

class TreeManager extends Render
{
    protected $xml;
    protected $dom;
    protected $jsx;
    protected $md;
    protected $json;

    public function render($tree = null)
    {
        if ($tree instanceof SimpleXMLElement) {
            return $this->renderXML($tree);
        }
        if ($tree instanceof DOMElement || $tree instanceof DOMDocument) {
            return $this->renderDOM($tree);
        }
        if ($tree instanceof JSX) {
            return $tree->renderJSX($tree);
        }
        if (isset($this->xml, $this->dom)) {
            return $this->renderXML($this->xml);
        }
        if (isset($jsx)) {
            return $this->renderJSX($this->jsx);
        }
        if ($this->isJson($tree)) {
            return $this->renderJSON($tree);
        }
    }

    protected function isJson($string): bool
    {
        @json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}