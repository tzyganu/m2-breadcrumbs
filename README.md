Magento 2 Breadcrumbs
=============

Magento 2 (as Magento 1) has a lot of pages where breadcrumbs are missing.  
some of them might not need them, but for others I think they might be needed.  
Specially for the customer account pages.  
Having breadcrumbs will improve the customer experience and will make the navigation easier.

This is based on the no-longer maintained https://github.com/tzyganu/m2-breadcrumbs

Installation
---------

    composer require imi/m2-breadcrumbs

You can find a list of supported pages in `etc/frontend/breadcrumbs.xml`.  
If you don't want breadcrumbs for a specific page then you can manage the pages from `Stores->Configuration->Easylife Breadcrumbs`.  

Extending
-------

If you have other pages that you thing need breadcrumbs, create a module that depends on this one and add a file `etc/frontedn/breadcrumbs.xml` validated by the same xsd file `Easylife/Breadcrumbs/etc/frontend/breadcrumbs_file.xsd` where you can list your pages.  
You can add a new page by adding this in your config file:

    <page id="page_layout_handle" group="group_code"><!-- available groups are listed also in breadcrumbs.xml -->
        <label>Store configuration label  here</label>
        <methods>
            <method name="methodNameHere" sort="10" class="Class\Name\Here" /><!-- this will call the method Class\Name\Here::methodNameHere on the event controller_action_layout_render_before_page_layout_handle -->
           <method name="otherMethodNameHere" sort="20" class="Class\Name\Here" /><!-- this will call the method Class\Name\Here::otherMethodNameHere on the event controller_action_layout_render_before_page_layout_handle -->
        </methods>
    </page>

If the `class` attribute is missing it will call the method from the `Easylife\Breadcrumbs\Model\Observer` class.  

Educational
--------
You can use this module for learning purposes. Is shows you how to create a config loader (I hope I've done it right).  
It also shows you how to use `di.xml`.  The observer for all events is `Easylife\Breadcrumbs\Model\Observer\ObserverInterface` and the `di.xml` file contains a preference for this interface.  
`<preference for="Easylife\Breadcrumbs\Model\Observer\ObserverInterface" type="Easylife\Breadcrumbs\Model\Observer" />`  

