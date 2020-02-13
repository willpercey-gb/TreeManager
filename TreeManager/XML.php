<?php

namespace UWebPro\Tree;

/**
 * Uses
 */

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 *  Interface
 */

use UWebPro\Tree\Interfaces\XmlInterface;


/**
 * Class XMLDOM
 * @package UWebPro\XML
 * @php 7.3
 * @version 1.0.0
 */
class XML extends TreeManager implements XmlInterface
{
    protected $links;

    /**
     * XML constructor.
     * @param $xml
     * @param $dom
     * @throws InvalidArgumentException
     */
    public function __construct($xml = null, $dom = null)
    {
        if ($xml instanceof SimpleXMLElement) {
            $this->xml = $xml;
        } elseif ($xml !== null) {
            throw new InvalidArgumentException(__CLASS__ . ' Exception. This is not a SimpleXMLElement');
        }
        if ($dom instanceof DOMDocument || $dom instanceof DOMElement) {
            $this->dom = $dom;
        } elseif ($dom !== null) {
            throw new InvalidArgumentException(__CLASS__ . ' Exception. This is not a DOMDocument input');
        }
        $this->links = [];

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->xml, $name) && method_exists($this->dom, $name)) {
            if ($this->dom->doctype === null) {
                return call_user_func_array(array($this->xml, $name), $arguments);
            } elseif ($this->dom->doctype !== null) {
                return call_user_func_array(array($this->dom, $name), $arguments);
            } else {
                throw new InvalidArgumentException('Method name clash, please use callDom or callXml');
            }
        } elseif (method_exists($this->xml, $name)) {
            return call_user_func_array(array($this->xml, $name), $arguments);
        } elseif (method_exists($this->dom, $name)) {
            return call_user_func_array(array($this->dom, $name), $arguments);
        } else {
            throw new InvalidArgumentException('Method name clash or non existent method, please use callDom or callXml for these functions');
        }
    }

    /**#
     * @param string $name
     * @param mixed ...$arguments
     * @return mixed
     */
    public function callDom(string $name, ...$arguments)
    {
        return call_user_func_array(array($this->dom, $name), $arguments);
    }

    /**
     * @param string $name
     * @param mixed ...$arguments
     * @return mixed
     */
    public function callXml(string $name, ...$arguments)
    {
        return call_user_func_array(array($this->xml, $name), $arguments);
    }

    /**
     * @param string $tree
     * @return $this
     */
    public function __invoke($tree = self::class): self
    {
        if ($tree instanceof self) {
            return $tree;
        } else {
            return new self();
        }
    }

    /**
     * @return SimpleXMLElement
     */
    public function getXML(): SimpleXMLElement
    {
        return $this->xml;
    }

    /**
     * @return null|DOMElement
     */
    public function getDOM(): ?DOMElement
    {
        if ($this->dom === null) {
            $this->dom = dom_import_simplexml($this->xml ?? null);
        }
        return $this->dom;
    }


    /**
     * @param array $array
     * @return $this
     */
    public function arrayToXml(array $array): self
    {
        array_walk_recursive($array, function ($name, $value) {
            $this->xml->addChild($value, $name);
        });
        return $this;
    }

    /**
     * @usage Load XML into $this->xml
     * @param $xml
     * @return $this
     */
    public function rawXML($xml): self
    {
        $this->xml = simplexml_load_string($xml);
        $this->dom = dom_import_simplexml($this->xml);
        return $this;
    }

    /**
     * @param $xml
     * @return $this
     */
    public function setXml(SimpleXMLElement $xml): self
    {
        $this->xml = $xml;
        return $this;
    }

    /**
     * @usage Load HTML into $xml and $dom;
     * @param $html
     * @return $this
     */
    public function rawHTML($html): self
    {
        $this->dom = new DOMDocument();
        @$this->dom->loadHTML($html);
        $this->xml = simplexml_import_dom($this->dom);
        return $this;
    }

    public function setDom($dom): self
    {
        $this->dom = $dom;
        return $this;
    }

    /**
     * @param $expression
     * @param array $namespace
     * @return array
     */
    public function htmlXPath($expression, $namespace = ['prefix' => null, 'URI' => null]): array
    {
        $xpath = new DOMXpath($this->dom);
        $dom = $this->dom;
        if ($namespace['prefix'] && $namespace['URI']) {
            $xpath->registerNamespace($namespace['prefix'], $namespace['URI']);
        }
        $elements = @$xpath->query($expression);
        $content = [];
        foreach ($elements as $element) {
            $content[] = @$dom->saveHTML($element);
        }
        return $content;
    }

    /**
     * @param $callback
     * @param SimpleXMLElement|null $xml
     * @param string $glue
     * @param string $parent
     * @param string $link
     * @return int
     */
    public function recurse($callback, SimpleXMLElement &$xml = null, $glue = '.', $parent = '', &$link = ''): int
    {
        $xml = $xml === null ? $this->xml : $xml;
        $child_count = 0;
        foreach ($xml as $key => $value) {
            $child_count++;
            if ($key === 'a') {
                $link = @$value->attributes()['href'];
            }
            $link = isset($link) ? $link : null;
            if ($this->recurse($callback, $value, $glue, $parent . $glue . $key, $link) === 0)  // no childern, aka "leaf node"
            {
                $returnable = [
                    $parent . $glue . (string)$key => [
                        'value' => $value,
                        'link' => $link
                    ]
                ];
                $callback($returnable, $parent . $glue . $key);
            }
        }
        return $child_count;
    }

    /**
     * @param null|SimpleXMLElement $xml
     * @return array
     * @warn Massive Memory usage
     */
    public function getDocumentLinks(&$xml = null): array
    {
        $xml = $xml === null ? $this->xml : $xml;
        $this->recurse(function (&$array, $key) {
            if ($array[$key]['link']) {
                $this->links[] = &$array[$key]['link'];
            }
        }, $xml);
        return $this->links;
    }

    /**
     * @usage $xmlQueryAttribute('name', 'link') if you have multiple xml elements with the same attribute name, but different attribute values and want to query by the value of the attribute, enter the name and the value.
     *
     * @param $attr_name
     * @param $attr_value
     * @param null|SimpleXMLElement $xml
     * @return SimpleXMLElement
     */
    public function xmlQueryAttribute($attr_name, $attr_value, SimpleXMLElement $xml = null): SimpleXMLElement
    {
        $xml = $xml === null ? $this->xml : $xml;
        foreach ($xml as $node) {
            switch ($node[$attr_name]) {
                case $attr_value:
                    return $node;
            }
        }
    }

    /**
     * @param SimpleXMLElement|null $xml
     * @return array
     */
    public function simpleToArray(SimpleXMLElement $xml = null): array
    {
        $xml = $xml === null ? $this->xml : $xml;
        return json_decode(json_encode($xml), true) ?? $this->toArray($xml);
    }

    /**
     * @param SimpleXMLElement|null $xml
     * @return array
     */
    public function toArray(SimpleXMLElement $xml = null): array
    {
        $xml = $xml === null ? $this->xml : $xml;
        $parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
            $nodes = $xml->children();
            $attributes = $xml->attributes();

            if (0 !== @count($attributes)) {
                foreach ($attributes as $attrName => $attrValue) {
                    $collection['attributes'][$attrName] = strval($attrValue);
                }
            }

            if (0 === $nodes->count()) {
                $collection['value'] = strval($xml);
                return $collection;
            }

            foreach ($nodes as $nodeName => $nodeValue) {
                if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
                    $collection[$nodeName] = $parser($nodeValue);
                    continue;
                }

                $collection[$nodeName][] = $parser($nodeValue);
            }

            return $collection;
        };

        return [
            $xml->getName() => $parser($xml)
        ];
    }

    public function toJson()
    {
        return json_encode($this->xml);
    }
}
