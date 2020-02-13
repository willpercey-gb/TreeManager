<?php


namespace UWebPro\Tree\Interfaces;


interface TreeInterface
{
    public function __construct();

    public function __call($name, $arguments);

    public function __invoke();

    public function render();

    public function toArray();
}