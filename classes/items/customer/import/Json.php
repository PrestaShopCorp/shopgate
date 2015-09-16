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

class ShopgateItemsCustomerImportJson extends ShopgateItemsCustomer
{
    /**
     * @param $user
     * @param $pass
     * @param ShopgateCustomer $customer
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        if (!Validate::isEmail($user)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_REGISTER_CUSTOMER_ERROR, 'E-mail Address validation error', true);
        }

        if ($pass && !Validate::isPasswd($pass)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_REGISTER_CUSTOMER_ERROR, 'Password validation error', true);
        }

        /** @var CustomerCore | Customer $customerModel */
        $customerModel = new Customer();

        if ($customerModel->getByEmail($user)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
        }

        $customerModel->active         = 1;
        $customerModel->lastname     = $customer->getLastName();
        $customerModel->firstname     = $customer->getFirstName();
        $customerModel->email         = $user;
        $customerModel->passwd         = Tools::encrypt($pass);
        $customerModel->id_gender     = $this->mapGender($customer->getGender());
        $customerModel->birthday     = $customer->getBirthday();
        $customerModel->newsletter     = $customer->getNewsletterSubscription();

        $validateMessage = $customerModel->validateFields(false, true);

        if ($validateMessage !== true) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, $validateMessage, true);
        }

        $customerModel->save();

        /**
         * addresses
         */
        foreach ($customer->getAddresses() as $address) {
            $this->createAddress($address, $customerModel);
        }

        return $customerModel->id;
    }

    /**
     * @param $address
     * @param $customer
     * @return int
     * @throws ShopgateLibraryException
     */
    public function createAddress($address, $customer)
    {
        /** @var AddressCore | Address $addressModel */
        $addressItem = new Address();

        $addressItem->id_customer     = $customer->id;
        $addressItem->lastname         = $address->getLastName();
        $addressItem->firstname     = $address->getFirstName();

        if ($address->getCompany()) {
            $addressItem->company = $address->getCompany();
        }

        $addressItem->address1         = $address->getStreet1();

        if ($address->getStreet2()) {
            $addressItem->address2 = $address->getStreet2();
        }

        $addressItem->city             = $address->getCity();
        $addressItem->postcode         = $address->getZipcode();

        if (!Validate::isLanguageIsoCode($address->getCountry())) {
            $customer->delete();
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                'invalid country code: '.$address->getCountry(),
                true
            );
        }

        $addressItem->id_country = Country::getByIso($address->getCountry());

        if ($address->getState() && !Validate::isStateIsoCode($address->getState())) {
            $customer->delete();
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                'invalid state code: '.$address->getState(),
                true
            );
        } else {
            $addressItem->id_state = State::getIdByIso($address->getState());
        }

        $addressItem->alias = $address->getIsDeliveryAddress() ? $this->getModule()->l('Default delivery address') : $this->getModule()->l('Default');

        $addressItem->alias = $address->getIsInvoiceAddress() ? $this->getModule()->l('Default invoice address') : $this->getModule()->l('Default');

        $addressItem->phone         = $address->getPhone();
        $addressItem->phone_mobile  = $address->getMobile();

        $validateMessage            = $addressItem->validateFields(false, true);

        if ($validateMessage !== true) {
            $customer->delete();
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                $validateMessage,
                true
            );
        }

        $addressItem->save();
        return $addressItem->id;
    }
}
