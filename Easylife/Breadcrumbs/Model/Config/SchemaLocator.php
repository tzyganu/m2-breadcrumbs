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
use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir\Reader as DirReader;
class SchemaLocator implements SchemaLocatorInterface {
    /**
     * Path to corresponding XSD file with validation rules for merged config
     * @var string
     */
    protected $_schema = null;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     * @var string
     */
    protected $_perFileSchema = null;

    /**
     * @param DirReader $moduleReader
     */
    public function __construct(DirReader $moduleReader)
    {
        $this->_schema = $moduleReader->getModuleDir('etc', 'Easylife_Breadcrumbs') . '/frontend/breadcrumbs.xsd';
        $this->_perFileSchema = $moduleReader->getModuleDir('etc', 'Easylife_Breadcrumbs') . '/frontend/breadcrumbs_file.xsd';
    }

    /**
     * Get path to merged config schema
     *
     * @return string|null
     */
    public function getSchema() {
        return $this->_schema;
    }

    /**
     * Get path to pre file validation schema
     * @return string|null
     */
    public function getPerFileSchema() {
        return $this->_perFileSchema;
    }
}