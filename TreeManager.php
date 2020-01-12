<?php

namespace UWebPro\XML;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use SimpleXMLElement;
use SoapVar;

/**
 * Class TreeManager
 * @package UWebPro\XML
 * @php 7.3
 * @version 1.0.0
 */
class TreeManager
{
    protected $xml;
    protected $dom;
    protected $links;


    /**
     * TreeManager constructor.
     * @param string $xml
     * @param string $dom
     * @throws InvalidArgumentException
     */
    public function __construct($xml = SimpleXMLElement::class, $dom = DOMDocument::class)
    {
        if ($xml instanceof SimpleXMLElement) {
            $this->xml = $xml;
        } else {
            throw new InvalidArgumentException(__CLASS__ . ' Exception. This is not a SimpleXMLElement');
        }
        if ($dom instanceof DOMDocument) {
            $this->dom = $dom;
        } else {
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
                return $this->callXml($name, $arguments);
            } elseif ($this->dom->doctype !== null) {
                return $this->callDom($name, $arguments);
            } else {
                throw new InvalidArgumentException('Method clash, please use callDom or callXml and ensure dom or xml has been passed in');
            }
        } elseif (method_exists($this->xml, $name)) {
            return call_user_func_array($this->xml->$name, $arguments);
        } elseif (method_exists($this->dom, $name)) {
            return call_user_func_array($this->dom->$name, $arguments);
        } else {
            throw new InvalidArgumentException('Method name clash or non existent method, please use callDom or callXml for these functions');
        }
    }

    /**
     * @param $name
     * @param mixed ...$arguments
     * @return mixed
     */
    public function callDom($name, ...$arguments)
    {
        return call_user_func_array($this->dom->$name, $arguments);
    }

    /**
     * @param $name
     * @param mixed ...$arguments
     * @return mixed
     */
    public function callXml($name, ...$arguments)
    {
        return call_user_func_array($this->xml->$name, $arguments);
    }

    /**
     * @param string $tree
     * @return TreeManager
     */
    public function __invoke($tree = TreeManager::class)
    {
        if ($tree instanceof TreeManager) {
            return $tree;
        } else {
            return new TreeManager();
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
     * @return DOMDocument
     */
    public function getDOM(): DOMDocument
    {
        return $this->dom;
    }


    /**
     * @param array $array
     * @return TreeManager
     */
    public function arrayToXml(array $array): TreeManager
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
    public function rawXML($xml): TreeManager
    {
        $this->xml = simplexml_load_string($xml);
        $this->dom = dom_import_simplexml($this->xml);
        return $this;
    }


    /**
     * @usage Load HTML into $xml and $dom;
     * @param $html
     * @return $this
     */
    public function rawHTML($html): TreeManager
    {
        $this->dom = new DOMDocument();
        @$this->dom->loadHTML($html);
        $this->xml = simplexml_import_dom($this->dom);
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
     * @param null|SimpleXMLElement $xml
     * @param string $glue
     * @param string $parent
     * @param string $link
     * @return int
     */
    public function recurseXML($callback, SimpleXMLElement &$xml = null, $glue = '.', $parent = '', $link = ''): int
    {
        $xml = !$xml ? $this->xml : $xml;
        $child_count = 0;
        foreach ($xml as $key => $value) {
            $child_count++;
            if ($key == 'a') {
                $link = $value->attributes()['href'];
            }
            $link = isset($link) ? $link : null;
            if ($this->recurseXML($callback, $value, $glue, $parent . '.' . $key, $link) == 0)  // no childern, aka "leaf node"
            {
                $returnable = [
                    $parent . $glue . (string)$key => [
                        'value' => (string)$value,
                        'link' => $link
                    ]
                ];
                $callback($returnable);
            }
        }
        return $child_count;
    }

    /**
     * @param null|SimpleXMLElement $xml
     * @return array
     */
    public function getDocumentLinks(&$xml = null): array
    {
        $xml = !$xml ? $this->xml : $xml;
        $this->recurseXML(function ($array) {
            if ($array[0]['link']) {
                $this->links[] = $array[0]['link'];
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
        $xml = !$xml ? $this->xml : $xml;
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
    public function simpleXmlToArray(SimpleXMLElement $xml = null): array
    {
        $xml = !$xml ? $this->xml : $xml;
        return json_decode(json_encode($xml), true) ?? $this->xmlToArray($xml);
    }

    /**
     * @param SimpleXMLElement|null $xml
     * @return array
     */
    public function xmlToArray(SimpleXMLElement $xml = null): array
    {
        $xml = !$xml ? $this->xml : $xml;
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

    public function createSoapBody($array)
    {
        $this->xml = new SimpleXMLElement('<Body/>');
        array_walk_recursive($array, function ($name, $value) {
            $this->xml->addChild($value, $name);
        });
        return new SoapVar($this->xml->asXML(), XSD_ANYXML);
    }
}