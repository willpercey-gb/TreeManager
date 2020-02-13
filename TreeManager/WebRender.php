<?php


namespace UWebPro\Tree;

use UWebPro\Tree\Traits\DOM;

class WebRender
{
    use DOM;

    public function renderXML($xml)
    {
        return $this->asHTML($xml);
    }

    public function renderDOM($dom)
    {
        return $this->asHTML($dom);
    }

    public function renderJSX($jsx)
    {
//        return $this->jsxToHTML($jsx);
    }
}

