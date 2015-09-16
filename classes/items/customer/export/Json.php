<?php
/**
 * Shopgate GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file AFL_license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to interfaces@shopgate.com so we can send you a copy immediately.
 *
 * @author    Shopgate GmbH, Schloßstraße 10, 35510 Butzbach <interfaces@shopgate.com>
 * @copyright Shopgate GmbH
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL"), in the version 3.0
 */

class ShopgateItemsCustomerExportJson extends ShopgateItemsCustomer
{
    /**
     * @param $user
     * @param $pass
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     */
    public function getCustomer($user, $pass)
    {
        $customerId = $this->getCustomerIdByEmailAndPassword($user, $pass);

        if (!$customerId) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD, 'Username or password is incorrect');
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($customerId);

        $shopgateCustomer = new ShopgateCustomer();

        $shopgateCustomer->setCustomerId($customer->id);
        $shopgateCustomer->setCustomerNumber($customer->id);
        $shopgateCustomer->setFirstName($customer->firstname);
        $shopgateCustomer->setLastName($customer->lastname);
        $shopgateCustomer->setGender($this->mapGender($customer->id_gender));
        $shopgateCustomer->setBirthday($customer->birthday);
        $shopgateCustomer->setMail($customer->email);
        $shopgateCustomer->setNewsletterSubscription($customer->newsletter);
        $shopgateCustomer->setCustomerToken(ShopgateCustomerPrestashop::getToken($customer));

        $addresses = array();

        foreach ($customer->getAddresses($this->getPlugin()->getLanguageId()) as $address) {
            $addressItem = new ShopgateAddress();

            $addressItem->setId($address['id_address']);
            $addressItem->setFirstName($address['firstname']);
            $addressItem->setLastName($address['lastname']);
            $addressItem->setCompany($address['company']);
            $addressItem->setStreet1($address['address1']);
            $addressItem->setStreet2($address['address2']);
            $addressItem->setCity($address['city']);
            $addressItem->setZipcode($address['postcode']);
            $addressItem->setCountry($address['country']);
            $addressItem->setState($address['state']);
            $addressItem->setPhone($address['phone']);
            $addressItem->setMobile($address['phone_mobile']);

            if ($address['alias'] == 'Default invoice address') {
                $addressItem->setAddressType(ShopgateAddress::INVOICE);
            } elseif ($address['alias'] == 'Default delivery address') {
                $addressItem->setAddressType(ShopgateAddress::DELIVERY);
            } else {
                $addressItem->setAddressType(ShopgateAddress::BOTH);
            }

            array_push($addresses, $addressItem);
        }

        $shopgateCustomer->setAddresses($addresses);

        /**
         * customer groups
         */
        $customerGroups = array();

        if (is_array($customer->getGroups())) {
            foreach ($customer->getGroups() as $customerGroupId) {
                $groupItem = new Group($customerGroupId, $this->getPlugin()->getLanguageId(), $this->getPlugin()->getContext()->shop->id ? $this->getPlugin()->getContext()->shop->id : false);

                $group = new ShopgateCustomerGroup();
                $group->setId($groupItem->id);
                $group->setName($groupItem->name);
                array_push($customerGroups, $group);
            }
        }

        $shopgateCustomer->setCustomerGroups($customerGroups);

        return $shopgateCustomer;
    }
}
