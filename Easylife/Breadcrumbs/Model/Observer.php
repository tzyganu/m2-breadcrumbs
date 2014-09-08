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
use Magento\Framework\ObjectManager;
use Magento\Framework\Registry;
use Magento\Theme\Block\Html\Breadcrumbs;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Helper\Data as CustomerHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Easylife\Breadcrumbs\Model\Config;
use Magento\Framework\UrlInterface;
use Easylife\Breadcrumbs\Model\Observer\ObserverInterface;
class Observer implements ObserverInterface {
    /**
     * xml config path to enabled setting
     */
    const XML_PATH_ENABLE_ENABLED   = 'easylife_breadcrumbs/settings/enabled';
    /**
     * xml config path to use all pages
     */
    const XML_PATH_ENABLE_ALL       = 'easylife_breadcrumbs/settings/all';
    /**
     * xml config path to specific page
     */
    const XML_PATH_ENABLE_SPECIFIC  = 'easylife_breadcrumbs/settings/specific';
    /**
     * xml cofig path to add home breadcrumb
     */
    const XML_PATH_ENABLE_HOME      = 'easylife_breadcrumbs/settings/home';
    /**
     * event prefix
     */
    const EVENT_PREFIX              = 'controller_action_layout_render_before_';
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    /**
     * @var \Magento\Framework\View\Result\Page
     */
    protected $_page;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Customer\Helper\Data
     */
    protected $_customerHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    protected $_coreRegistry;
    protected $_configData;
    protected $_objectManager;

    /**
     * constructor
     * @param PageFactory $pageFactory
     * @param StoreManagerInterface $storeManager
     * @param CustomerHelper $customerHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param Config $configData
     * @param ObjectManager $objectManager
     */
    public function __construct(
        PageFactory $pageFactory,
        StoreManagerInterface $storeManager,
        CustomerHelper $customerHelper,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        Registry $registry,
        Config $configData,
        ObjectManager $objectManager

    ) {
        $this->_pageFactory = $pageFactory;
        $this->_storeManager = $storeManager;
        $this->_customerHelper = $customerHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_page = $pageFactory->create();
        $this->_coreRegistry = $registry;
        $this->_configData = $configData;
        $this->_objectManager = $objectManager;
    }
    /**
     * get the breadcrumb block
     * @return \Magento\Theme\Block\Html\Breadcrumbs
     */
    protected function _getBreadcrumbBlock() {
        $layout = $this->_page->getLayout();
        /** @var \Magento\Theme\Block\Html\Breadcrumbs $block */
        $block = $layout->getBlock('breadcrumbs');
        return $block;
    }

    /**
     * @param \Magento\Theme\Block\Html\Breadcrumbs $block
     * @return $this
     */
    protected function _addHome(Breadcrumbs $block) {
        $block->addCrumb('home', array('label' => __('Home'), 'link' => $this->_storeManager->getStore()->getBaseUrl()));
        return $this;
    }
    /**
     * check if breadcrumb can be added
     * @param string $page
     * @return bool
     */
    protected function _canAddBreadcrumb($page) {
        if (!$this->_scopeConfig->isSetFlag(self::XML_PATH_ENABLE_ENABLED)) {
            return false;
        }
        if ($this->_scopeConfig->isSetFlag(self::XML_PATH_ENABLE_ALL)){
            return true;
        }
        $specific = $this->_scopeConfig->getValue(self::XML_PATH_ENABLE_SPECIFIC);
        if (empty($specific)){
            return false;
        }
        $parts = explode(',', $specific);
        return (in_array($page, $parts));
    }

    /**
     * get the page name from the event name
     * @param $eventName
     * @return null|string
     */
    protected function _getPageName($eventName){
        if (substr($eventName, 0, strlen(self::EVENT_PREFIX)) == self::EVENT_PREFIX) {
            return substr($eventName, strlen(self::EVENT_PREFIX));
        }
        return null;
    }

    /**
     * add breadcrumbs to a page
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function addBreadcrumbs(\Magento\Framework\Event\Observer $observer) {
        $block = $this->_getBreadcrumbBlock();
        if (!$block) {
            return $this;
        }
        $eventName = $observer->getEvent()->getName();
        $page = $this->_getPageName($eventName);
        if ($this->_canAddBreadcrumb($page)) {
            $methods = $this->_configData->getConfig('page/'.$page.'/methods/method');
            $callable = array();
            if (is_array($methods)) {
                foreach ($methods as $method) {
                    if (isset($method['disabled']) && ($method['disabled'] === "true" || $method['disabled'] === "1")) {
                        continue;
                    }
                    if (isset($method['class'])) {
                        if (isset($method['shared']) && $method['shared']) {
                            $class = $this->_objectManager->get($method['class']);
                        }
                        else {
                            $class = $this->_objectManager->create($method['class']);
                        }
                    }
                    else {
                        $class = $this;
                    }
                    if (is_callable(array($class, $method['name']))) {
                        $callable[] = array('object' => $class, 'method' => $method['name']);
                    }
                }
            }

            if (count($callable) > 0) {
                if ($this->_scopeConfig->isSetFlag(self::XML_PATH_ENABLE_HOME)) {
                    $this->_addHome($block);
                }
                foreach ($callable as $index=>$call) {
                    $object = $call['object'];
                    $method = $call['method'];
                    $object->$method($block, ($index != count($callable) - 1));
                }
            }

        }
        return $this;
    }

    /**
     * add login breadcrumb
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addLogin(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_customerHelper->getLoginUrl() : '';
        $block->addCrumb('login', array('label' => __('Customer Login'), 'link' => $link));
        return $this;
    }

    /**
     * add forgot password
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addForgotPassword(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_customerHelper->getForgotPasswordUrl() : '';
        $block->addCrumb('forgot_password', array('label' => __('Forgot Your Password?'), 'link' => $link));
        return $this;
    }

    /**
     * add register
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRegister(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_customerHelper->getRegisterUrl() : '';
        $block->addCrumb('register', array('label' => __('Create New Customer Account'), 'link' => $link));
        return $this;
    }

    /**
     * add customer dashboard
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addDashboard(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_customerHelper->getDashboardUrl() : '';
        $block->addCrumb('dashboard', array('label' => __('Account Dashboard'), 'link' => $link));
        return $this;
    }

    /**
     * add edit account
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addEditAccount(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_customerHelper->getEditUrl() : '';
        $block->addCrumb('edit_account', array('label' => __('Edit Account Information'), 'link' => $link));
        return $this;
    }

    /**
     * add address book
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addAddressBook(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('customer/address') : '';
        $block->addCrumb('address_book', array('label' => __('Address Book'), 'link' => $link));
        return $this;
    }

    /**
     * add address
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addAddress(Breadcrumbs $block, $withLink) {
        /** @var \Magento\Customer\Block\Address\Edit $addressEditBlock */
        $addressEditBlock = $this->_page->getLayout()->getBlock('customer_address_edit');
        if (!$addressEditBlock) {
            return $this;
        }
        $address = $addressEditBlock->getAddress();
        if ($address && $address->getId()) {
            $link = ($withLink) ? $this->_urlBuilder->getUrl('customer/address/edit', array('id' => $address->getId())) : '';
            $block->addCrumb('address', array('label' => __('Edit Address'), 'link' => $link));
        }
        else {
            $link = ($withLink) ? $this->_urlBuilder->getUrl('customer/address/new') : '';
            $block->addCrumb('address', array('label' => __('Add New Address'), 'link' => $link));
        }
        return $this;
    }

    /**
     * add my downloadable products
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addDownloadable(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('downloadable/customer/products/') : '';
        $block->addCrumb('downloadable', array('label' => __('My Downloadable Products'), 'link' => $link));
        return $this;
    }

    /**
     * add recurring profiles
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRecurring(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/recurringPayment/') : '';
        $block->addCrumb('recurring', array('label' => __('Recurring Payments'), 'link' => $link));
        return $this;
    }

    /**
     * add recurring profiles
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRecurringItem(Breadcrumbs $block, $withLink) {
        $recurring = $this->_coreRegistry->registry('current_recurring_payment');
        if (!$recurring) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/recurringPayment/view', array('payment'=>$recurring->getId())) : '';
        $block->addCrumb('recurring', array('label' => __('Recurring Payment # '). $recurring->getReferenceId(), 'link' => $link));
        return $this;
    }
    /**
     * add recurring profiles
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRecurringOrders(Breadcrumbs $block, $withLink) {
        $recurring = $this->_coreRegistry->registry('current_recurring_payment');
        if (!$recurring) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/recurringPayment/orders', array('payment'=>$recurring->getId())) : '';
        $block->addCrumb('recurring', array('label' => __('Orders'), 'link' => $link));
        return $this;
    }
    /**
     * add recurring profiles
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRecurringUpdatePayment(Breadcrumbs $block, $withLink) {
        $recurring = $this->_coreRegistry->registry('current_recurring_payment');
        if (!$recurring) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/recurringPayment/updatePayment', array('payment'=>$recurring->getId())) : '';
        $block->addCrumb('recurring', array('label' => __('Update Payment'), 'link' => $link));
        return $this;
    }
    /**
     * add recurring profiles
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRecurringUpdateState(Breadcrumbs $block, $withLink) {
        $recurring = $this->_coreRegistry->registry('current_recurring_payment');
        if (!$recurring) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/recurringPayment/updateState', array('payment'=>$recurring->getId())) : '';
        $block->addCrumb('recurring', array('label' => __('Update State'), 'link' => $link));
        return $this;
    }

    /**
     * add newsletter
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addNewsletter(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('newsletter/manage') : '';
        $block->addCrumb('newsletter', array('label' => __('Newsletter Subscription'), 'link' => $link));
        return $this;
    }

    /**
     * add billing agreements
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addBillingAgreements(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('paypal/billing_agreement') : '';
        $block->addCrumb('billing_agreements', array('label' => __('Billing Agreements'), 'link' => $link));
        return $this;
    }

    /**
     * add my reviews
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addReviews(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('review/customer') : '';
        $block->addCrumb('reviews', array('label' => __('My Product Reviews'), 'link' => $link));
        return $this;
    }

    /**
     * add current review
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addReview(Breadcrumbs $block, $withLink) {
        /** @var \Magento\Review\Block\Customer\View $reviewBlock */
        $reviewBlock = $this->_page->getLayout()->getBlock('customers_review');
        if (!$reviewBlock) {
            return $this;
        }
        $reviewData = $reviewBlock->getReviewData();
        $productData = $reviewBlock->getProductData();
        if (!$reviewData || !$productData) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('review/customer/view', array('id'=>$reviewData->getId())) : '';
        $block->addCrumb('review', array('label' => __('Review Details: ').$productData->getName(), 'link' => $link));
        return $this;
    }

    /**
     * add wish list
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addWishList(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('wishlist') : '';
        $block->addCrumb('wishlist', array('label' => __('Wish List'), 'link' => $link));
        return $this;
    }

    /**
     * add orders
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addOrders(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/order/history') : '';
        $block->addCrumb('orders', array('label' => __('My Orders'), 'link' => $link));
        return $this;
    }

    /**
     * add one order
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addOrder(Breadcrumbs $block, $withLink) {
        return $this->_addOrderBreadcrumb($block, $withLink, 'sales.order.view');
    }

    /**
     * add order on invoice
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addOrderInvoice(Breadcrumbs $block, $withLink) {
        return $this->_addOrderBreadcrumb($block, $withLink, 'sales.order.invoice');
    }

    /**
     * add invoice
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addInvoice(Breadcrumbs $block, $withLink) {
        $block->addCrumb('invoices', array('label' => __('Invoices'), 'link' => ''));
        return $this;
    }

    /**
     * add order on creditmemo
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addOrderCreditMemo(Breadcrumbs $block, $withLink) {
        return $this->_addOrderBreadcrumb($block, $withLink, 'sales.order.creditmemo');
    }

    /**
     * add creditmemo
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addCreditMemo(Breadcrumbs $block, $withLink) {
        $block->addCrumb('creditmemos', array('label' => __('Refunds'), 'link' => ''));
        return $this;
    }

    /**
     * add order on shipment
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addOrderShipment(Breadcrumbs $block, $withLink) {
        return $this->_addOrderBreadcrumb($block, $withLink, 'sales.order.info');
    }

    /**
     * add shipment
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addShipment(Breadcrumbs $block, $withLink) {
        $block->addCrumb('shipments', array('label' => __('Shipments'), 'link' => ''));
        return $this;
    }

    /**
     * add order breadcrumb depending on the block name
     * @param Breadcrumbs $block
     * @param $withLink
     * @param $blockAlias
     * @return $this
     */
    protected function _addOrderBreadcrumb(Breadcrumbs $block, $withLink, $blockAlias) {
        /** @var \Magento\Sales\Block\Order\View $orderBlock */
        $orderBlock = $this->_page->getLayout()->getBlock($blockAlias);
        if (!$orderBlock) {
            return $this;
        }
        $order = $orderBlock->getOrder();
        if (!$order || !$order->getId()) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('sales/order/view', array('id' => $order->getId())) : '';
        $block->addCrumb('order', array('label' => __('Order # ').$order->getIncrementId(), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addBillingAgreement(Breadcrumbs $block, $withLink) {
        $_agreement = $this->_coreRegistry->registry('current_billing_agreement');
        if (!$_agreement || !$_agreement->getId()) {
            return $this;
        }
        $link = ($withLink) ? $this->_urlBuilder->getUrl('paypal/billing_agreement/view', array('agreement' => $_agreement->getId())) : '';
        $block->addCrumb('order', array('label' => __('Billing Agreement # ').$_agreement->getReferenceId(), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addSearchTerms(Breadcrumbs $block , $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('catalogsearch/term/popular/') : '';
        $block->addCrumb('search_terms', array('label' => __('Popular Search Terms'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addContact(Breadcrumbs $block , $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('contact') : '';
        $block->addCrumb('contact', array('label' => __('Contact Us'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addCart(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('checkout/cart') : '';
        $block->addCrumb('cart', array('label' => __('Shopping Cart'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addProductSend(Breadcrumbs $block, $withLink) {
        /** @var \Magento\Catalog\Model\Product $product */
        $product =  $this->_coreRegistry->registry('product');
        if (!$product) {
            return $this;
        }
        $link = ($withLink) ? $product->getProductUrl() : '';
        $block->addCrumb('send_product_id', array('label' => $product->getName(), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addSendFriend(Breadcrumbs $block, $withLink) {
        if ($withLink) {
            $product =  $this->_coreRegistry->registry('product');
            if (!$product) {
                return $this;
            }
            $link = $this->_urlBuilder->getUrl('sendfriend/product/send', array('id' => $product->getId()));
        }
        else {
            $link = '';
        }
        $block->addCrumb('send_product', array('label' => __('Email to a Friend'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addCheckout(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('checkout/onepage') : '';
        $block->addCrumb('checkout', array('label' => __('Checkout'), 'link' => $link));
        return $this;
    }
    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiCheckoutAddresses(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout/addresses') : '';
        $block->addCrumb('multi_checkout', array('label' => __('Ship to Multiple Addresses'), 'link' => $link));
        return $this;
    }
    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiCheckoutAddress(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout_address/newShipping') : '';
        $block->addCrumb('multi_checkout_address', array('label' => __('Create Shipping Address'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiCheckoutShipping(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout/shipping') : '';
        $block->addCrumb('multi_checkout_shipping', array('label' => __('Shipping Information'), 'link' => $link));
        return $this;
    }
    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiEditShipping(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout_address/newShipping') : '';
        $block->addCrumb('multi_edit_shipping', array('label' => __('Edit Shipping Address'), 'link' => $link));
        return $this;
    }
    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiBilling(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout/billing') : '';
        $block->addCrumb('multi_checkout_billing', array('label' => __('Billing Information'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiChangeBilling(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout_address/selectBilling') : '';
        $block->addCrumb('multi_checkout_change_billing', array('label' => __('Change Billing Address'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addMultiReview(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('multishipping/checkout/overview/') : '';
        $block->addCrumb('multi_checkout_overview', array('label' => __('Review Order'), 'link' => $link));
        return $this;
    }

    /**
     * @param Breadcrumbs $block
     * @param $withLink
     * @return $this
     */
    protected function _addRss(Breadcrumbs $block, $withLink) {
        $link = ($withLink) ? $this->_urlBuilder->getUrl('rss') : '';
        $block->addCrumb('rss', array('label' => __('RSS'), 'link' => $link));
        return $this;
    }

}
