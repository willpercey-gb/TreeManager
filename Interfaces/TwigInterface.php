<?php

namespace UWebPro\Tree\Interfaces;

interface TwigInterface extends TreeInterface
{
    public function toJson();

    public function toXml();

    public function toHtml();

}