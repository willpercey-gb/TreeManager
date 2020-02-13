<?php


namespace UWebPro\Tree;


use UWebPro\Tree\Interfaces\JsonInterface;

class JSON extends TreeManager implements JsonInterface
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

    public function toArray()
    {
        return json_decode($this->json, true);
    }
}