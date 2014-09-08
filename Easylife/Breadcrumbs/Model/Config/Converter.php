<?php
/**
 * Easylife_Breadcrumbs extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE_EASYLIFE_BREADCRUMBS.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category       Easylife
 * @package        Easylife_Breadcrumbs
 * @copyright      Copyright (c) 2014
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Easylife\Breadcrumbs\Model\Config;

use Magento\Backend\Model\Config\Structure\MapperInterface;
use Magento\Framework\Config\ConverterInterface;
use \Easylife\Breadcrumbs\Model\Config\Mapper\Factory;

class Converter implements ConverterInterface {
    /**
     * @var Factory
     */
    protected $_mapperFactory;

    /**
     * Mapper type list
     *
     * @var string[]
     */
    protected $_mapperList = array(
        Factory::MAPPER_EXTENDS,
        Factory::MAPPER_SORTING,
    );

    /**
     * @param Factory $mapperFactory
     */
    public function __construct(Factory $mapperFactory) {
        $this->_mapperFactory = $mapperFactory;
    }

    /**
     * Convert dom document
     *
     * @param \DOMNode $source
     * @return array
     */
    public function convert($source) {
        $result = $this->_convertDOMDocument($source);
        foreach ($this->_mapperList as $type) {
            /** @var $mapper MapperInterface */
            $mapper = $this->_mapperFactory->create($type);
            $result = $mapper->map($result);
        }
        return $result;
    }

    /**
     * Retrieve \DOMDocument as array
     *
     * @param \DOMNode $root
     * @return array|null
     */
    protected function _convertDOMDocument(\DOMNode $root) {
        $result = $this->_processAttributes($root);
        $children = $root->childNodes;

        $processedSubLists = array();
        for ($i = 0; $i < $children->length; $i++) {
            $child = $children->item($i);
            $childName = $child->nodeName;

            switch ($child->nodeType) {
                case XML_COMMENT_NODE:
                    continue 2;
                    break;

                case XML_TEXT_NODE:
                    if ($children->length && trim($child->nodeValue, "\n ") === '') {
                        continue 2;
                    }
                    $childName = 'value';
                    $convertedChild = __($child->nodeValue);
                    break;

                case XML_CDATA_SECTION_NODE:
                    $childName = 'value';
                    $convertedChild = __($child->nodeValue);
                    break;

                default:
                    $convertedChild = $this->_convertDOMDocument($child);
                    break;
            }
            if (in_array($childName, $processedSubLists)) {
                $result = $this->_addProcessedNode($convertedChild, $result, $childName);
            } else if (array_key_exists($childName, $result)) {
                if ($childName == 'method') {
                    $key = '';
                    if (isset($convertedChild['class'])) {
                        $key = $convertedChild['class'].'::';
                    }
                    $key .= $convertedChild['name'];
                }
                elseif ($childName == 'page' || $childName == "group") {
                    $key = $convertedChild['id'];
                }
                else {
                    $key = null;
                }
                if ($key) {
                    $result[$childName][$key] = $convertedChild;
                }
                else {
                    $result[$childName][] = $convertedChild;
                }
                $processedSubLists[] = $childName;
            } else {
                if ($childName == 'method') {
                    $key = $convertedChild['name'];
                    $result[$childName][$key] = $convertedChild;
                }
                elseif ($childName == 'page' || $childName == "group") {
                    $key = $convertedChild['id'];
                    $result[$childName][$key] = $convertedChild;
                }
                else {
                    $result[$childName] = $convertedChild;
                }
            }
        }
        if (count($result) == 1 && array_key_exists('value', $result)) {
            $result = $result['value'];
        }
        if ($result == array()) {
            $result = null;
        }

        return $result;
    }

    /**
     * Add converted child with processed name
     *
     * @param array $convertedChild
     * @param array $result
     * @param string $childName
     * @return array
     */
    protected function _addProcessedNode($convertedChild, $result, $childName) {
        if ($childName == 'method') {
            $identifier = 'name';
        }
        else {
            $identifier = 'id';
        }
        if (is_array($convertedChild) && array_key_exists($identifier, $convertedChild)) {
            $result[$childName][$convertedChild[$identifier]] = $convertedChild;
        } else {
            $result[$childName][] = $convertedChild;
        }
        return $result;
    }

    /**
     * Process element attributes
     *
     * @param \DOMNode $root
     * @return array
     */
    protected function _processAttributes(\DOMNode $root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attributes = $root->attributes;
            foreach ($attributes as $attribute) {
                $result[$attribute->name] = $attribute->value;
            }
            return $result;
        }
        return $result;
    }
}