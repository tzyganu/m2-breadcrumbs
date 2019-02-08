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
namespace Easylife\Breadcrumbs\Model\Source;

use Easylife\Breadcrumbs\Model\Config;
class Pages {
    /**
     * breadcrumbs config
     * @var Config
     */
    protected $_config;
    protected $_pages;

    /**
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->_config = $config;
    }

    /**
     * get the pages as options
     * @return array
     */
    public function toOptionArray(){
        if (is_null($this->_pages)) {
            $config = $this->_config->getConfig('page');
            $groupConfig = $this->_config->getConfig('group');
            $pages = array();
            $orphan = array();
            foreach ($config as $key=>$values) {
                $realValues = array('value' => $values['id'], 'label' => $values['label']);
                if (isset($values['group']) && isset($groupConfig[$values['group']])) {
                    if (!isset($pages[$values['group']])) {
                        $pages[$values['group']]['value'] = array();
                        $pages[$values['group']]['label'] = $groupConfig[$values['group']]['label'];
                        $pages[$values['group']]['sort']  = $groupConfig[$values['group']]['sort'];
                    }
                    $pages[$values['group']]['value'][] = $realValues;
                }
                else {
                    $orphan[] = $realValues;
                }
            }
            uasort($pages, array($this, '_cmp'));
            if (count($orphan)) {
                foreach ($orphan as $item) {
                    $pages[] = $item;
                }
            }

            $this->_pages = $pages;
        }
        return $this->_pages;
    }

    /**
     * sort by label
     * @param $elementA
     * @param $elementB
     * @return int
     */
    protected function _cmp($elementA, $elementB) {
        $sortIndexA = $elementA['sort'];
        $sortIndexB = $elementB['sort'];
        if ($sortIndexA == $sortIndexB) {
            return 0;
        }
        return $sortIndexA < $sortIndexB ? -1 : 1;
    }
}
