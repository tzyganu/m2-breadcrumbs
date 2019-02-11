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
namespace Easylife\Breadcrumbs\Model;

use Magento\Backend\Model\Config\Structure;
use Easylife\Breadcrumbs\Model\Config\Reader;
class Config {
    /**
     * breadcrumbs config
     * @var array
     */
    protected $_config;
    /**
     * @var Reader
     */
    protected $_reader;
    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader) {
        $this->_reader = $reader;
    }

    /**
     * get the config
     * @param null $path
     * @param null $default
     * @return array|null
     */
    public function getConfig($path = null, $default = null) {
        if (is_null($this->_config)) {
            $config = $this->_reader->read();
            $this->_config = isset($config['config']) ? $config['config'] : array();
            unset($this->_config['noNamespaceSchemaLocation']);
        }
        if (is_null($path)) {
            return $this->_config;
        }
        $parts = explode('/', $path);
        $config = $this->_config;
        foreach ($parts as $part) {
            if (isset($config[$part])) {
                $config = $config[$part];
            }
            else {
                return $default;
            }
        }
        return $config;
    }
}