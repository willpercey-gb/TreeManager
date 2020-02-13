<?php


namespace UWebPro\Tree;


use UWebPro\Tree\Interfaces\JSXInterface;

class JSX extends TreeManager implements JSXInterface
{

    public function __construct()
    {
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }

    public function __invoke()
    {
        // TODO: Implement __invoke() method.
    }

    public function render($self = null)
    {
        return $self === null ? $this->renderJSX($this) : $this->renderJSX($self);
    }

    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    public function toHtml()
    {
        // TODO: Implement toHtml() method.
    }

    public function toJson()
    {
        // TODO: Implement toJson() method.
    }

    public function toXml()
    {
        // TODO: Implement toXml() method.
    }
}