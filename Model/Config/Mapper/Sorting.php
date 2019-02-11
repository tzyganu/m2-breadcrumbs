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
use \Magento\Backend\Model\Config\Structure\MapperInterface;
class Sorting implements MapperInterface{
    /**
     * @param array $data
     * @return array
     */
    public function map(array $data) {
        foreach ($data['config']['page'] as &$element) {
            $element = $this->_processConfig($element);
        }
        if (isset($data['config']['group'])) {
            uasort($data['config']['group'], array($this, '_cmp'));
        }
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function _processConfig($data) {
        if (isset($data['methods']['method'])) {
            uasort($data['methods']['method'], array($this, '_cmp'));
        }
        return $data;
    }

    /**
     * @param $elementA
     * @param $elementB
     * @return int
     */
    protected function _cmp($elementA, $elementB) {
        $sortIndexA = 0;
        if (isset($elementA['sort'])) {
            $sortIndexA = intval($elementA['sort']);
        }
        $sortIndexB = 0;
        if (isset($elementB['sort'])) {
            $sortIndexB = intval($elementB['sort']);
        }
        if ($sortIndexA == $sortIndexB) {
            return 0;
        }
        return $sortIndexA < $sortIndexB ? -1 : 1;
    }
}
