<?php
/**
 * User: WibWeb
 * Date: 1/12/14
 * Time: 2:25 PM
 */

class Wib_Percustdiscount_Model_Observer
{

    public function __construct()
    {
    }

    public function getConfig($var){
        /* Check config of module */
        switch ($var) {
            case 'specialAllowed':
                $specialAllowed = Mage::getStoreConfig('percustdiscount_settings/general/special_price',Mage::app()->getStore());
                return $specialAllowed;
                break;
            case 'disallowedCategories':
                $disallowedCategories = Mage::getStoreConfig('percustdiscount_settings/general/categories',Mage::app()->getStore());
                $disallowedCategories = preg_replace('/\s+/', '', $disallowedCategories);
                $disallowedCategories = explode(',', $disallowedCategories);
                return $disallowedCategories;
                break;
        }
        
        
    }
    public function get_final_list_price($observer)
    {
        /* Check if the customer is logged in */

        if(Mage::getSingleton('customer/session')->isLoggedIn())
        {
            //get the current customer
            $customer = Mage::getSingleton('customer/session')->getId();
            //load the customer and get their Percustdiscount
            $customer_discount = Mage::getModel('customer/customer')->load($customer)->getPerCustDiscount();
            //var_dump($customer_discount);

            //convert discount to percentage
            $customer_discount = $customer_discount/100;

            //get the original price and overwrite it with the discount applied.
            $event = $observer->getEvent();
            $products = $event->getCollection();

            foreach( $products as $product )
            {  
                $productCategories = $product->getCategoryIds();

                if(!array_intersect($this->getConfig('disallowedCategories'), $productCategories))
                {
                    // Not in disallowedCategorie

                    if(!empty($product->getSpecialPrice()))
                    {
                        if($this->getConfig('specialAllowed') == true)
                        {
                            // Use SpecialPrice instead of price in case, it falls back to the base price if no group price is available.
                            $product->original_price = $product->getSpecialPrice();
                            $final_price = $product->original_price * (1 - $customer_discount);
                            $product->setFinalPrice($final_price);
                        }
                        elseif($this->getConfig('specialAllowed') == false)
                        {
                            // Use SpecialPrice instead of price in case, it falls back to the base price if no group price is available.
                            $product->setFinalPrice($product->getSpecialPrice());
                        }
                    }
                    elseif(empty($product->getSpecialPrice()))
                    {
                        // Use Group Price instead of price in case, it falls back to the base price if no group price is available.
                        $product->original_price = $product->getGroupPrice();
                        $final_price = $product->original_price * (1 - $customer_discount);
                        $product->setFinalPrice($final_price);
                    }
                }
                elseif(array_intersect($this->getConfig('disallowedCategories'), $productCategories))
                {
                    // IN disallowedCategorie
                    if(!empty($product->getSpecialPrice())){
                        // IN disallowedCategorie and specialPrice is NOT empty
                        $product->setFinalPrice($product->getSpecialPrice());
                    }
                    elseif(empty($product->getSpecialPrice())){
                        // Use Group Price instead of price in case, it falls back to the base price if no group price is available.
                        $product->setFinalPrice($product->getGroupPrice());
                    } 
                }
            }

            return $this;
        }
    }



    public function get_final_price($observer)
    {
        /* Check if the customer is logged in */

        if(Mage::getSingleton('customer/session')->isLoggedIn())
        {
            //get the current customer
            $customerId = Mage::getSingleton('customer/session')->getId();
            //load the customer and get their Percustdiscount
            $customer = Mage::getModel('customer/customer')->load($customerId);
            //var_dump($customer);
            $customer_discount = $customer->getData('per_cust_discount');

            //convert discount to percentage
            $customer_discount = $customer_discount/100;

            //get the original price and overwrite it with the discount applied.
            $event = $observer->getEvent();
            $product = $event->getProduct();

            $productCategories = $product->getCategoryIds();
            if(!array_intersect($this->getConfig('disallowedCategories'), $productCategories))
            {
                // Use Group Price instead of price in case, it falls back to the base price if no group price is available.
                $product->original_price = $product->getGroupPrice();

                $final_price = $product->original_price * (1 - $customer_discount);

                $product->setFinalPrice($final_price);
            }

            return $this;
        }
    }
}
?>