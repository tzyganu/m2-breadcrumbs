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
use Magento\Framework\ObjectManagerInterface;

class Factory {
    /**
     * sorting mapper key
     */
    const MAPPER_SORTING = 'sorting';
    /**
     * extend mapper key
     */
    const MAPPER_EXTENDS = 'extends';
    /**
     * supported mappers
     * @var array
     */
    protected $_typeMap = array(
        self::MAPPER_SORTING => 'Easylife\Breadcrumbs\Model\Config\Mapper\Sorting',
        self::MAPPER_EXTENDS => 'Easylife\Breadcrumbs\Model\Config\Mapper\ExtendsMapper'
    );
    /**
     * object manager
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * constructor
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager) {
        $this->_objectManager = $objectManager;
    }

    /**
     * create mapper
     * @param $type
     * @return MapperInterface
     * @throws \Exception
     */
    public function create($type) {
        $className = $this->_getMapperClassNameByType($type);

        /** @var MapperInterface $mapperInstance  */
        $mapperInstance = $this->_objectManager->create($className);

        if (false == $mapperInstance instanceof MapperInterface) {
            throw new \Exception(
                'Mapper object is not instance on \Magento\Backend\Model\Config\Structure\MapperInterface'
            );
        }
        return $mapperInstance;
    }

    /**
     * @param $type
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function _getMapperClassNameByType($type) {
        if (false == isset($this->_typeMap[$type])) {
            throw new \InvalidArgumentException('Invalid mapper type: ' . $type);
        }
        return $this->_typeMap[$type];
    }
}
