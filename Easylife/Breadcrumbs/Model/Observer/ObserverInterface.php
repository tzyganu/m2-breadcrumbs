<?php
namespace Easylife\Breadcrumbs\Model\Observer;
use \Magento\Framework\Event\Observer as FrameworkObserver;
interface ObserverInterface {
    public function addBreadcrumbs(FrameworkObserver $observer);
}