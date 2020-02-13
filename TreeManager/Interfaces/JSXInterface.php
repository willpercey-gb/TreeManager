<?php

namespace UWebPro\Tree\Interfaces;

interface JSXInterface extends TreeInterface
{
    public function toJson();

    public function toXml();

    public function toHtml();

}