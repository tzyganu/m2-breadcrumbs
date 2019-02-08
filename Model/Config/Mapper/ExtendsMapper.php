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
namespace Easylife\Breadcrumbs\Model\Config\Mapper;
class ExtendsMapper {
    /**
     * configuration to map
     * @var array
     */
    protected $_breadcrumbConfiguration;

    /**
     * @param array $data
     * @return array
     */
    public function map(array $data) {
        if (!isset($data['config']['page']) || !is_array($data['config']['page'])) {
            return $data;
        }
        $this->_breadcrumbConfiguration =& $data['config']['page'];
        foreach (array_keys($this->_breadcrumbConfiguration) as $nodeName) {
            $this->_traverseAndExtend($nodeName);
        }
        return $data;
    }

    /**
     * @param $path
     */
    protected function _traverseAndExtend($path) {
        $node = $this->_getDataByPath($path);
        if (!is_array($node)) {
            return;
        }

        if (!empty($node['methods'])) {
            foreach (array_keys($node['methods']) as $childName) {
                $this->_traverseAndExtend($path . '/' . $childName);
            }
        }
    }

    /**
     * @param $path
     * @return array|null
     */
    protected function _getDataByPath($path) {
        $result = $this->_breadcrumbConfiguration;
        $pathParts = $this->_transformPathToKeysList($path);

        foreach ($pathParts as $part) {
            $result = isset($result[$part]) ? $result[$part] : null;
            if (is_null($result)) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param $path
     * @return array
     */
    protected function _transformPathToKeysList($path) {
        $path = str_replace('/', '/methods/', $path);
        return explode('/', $path);
    }
}
