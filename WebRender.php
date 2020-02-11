<?php


namespace UWebPro\Tree;

class WebRender
{
    use \UWebPro\Tree\Traits\DOM;
    
    use \UWebPro\Tree\Traits\JSX{
        asHTML as jsxToHTML;
    }

    public function renderXML($xml)
    {
        $this->asHTML($xml);
    }

    public function renderDOM($dom)
    {
        $this->asHTML($dom);
    }

    public function renderJSX($jsx)
    {
        return $this->jsxToHTML($jsx);
    }
}

