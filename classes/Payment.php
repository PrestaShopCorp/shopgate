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

class ShopgatePayment
{
	const DEFAULT_IDENTIFIER_MOBILE_PAYMENT = 'MOBILE_PAYMENT';

	/** @var ShopGate */
	protected $module;

	/**
	 * @var array
	 */
	protected $_methods = array();

	/**
	 * @var array
	 */
	public $paymentInfoStrings = array(
		'shopgate_payment_name' => 'Payment name',
		'upp_transaction_id' => 'Transaction ID',
		'authorization' => 'Authorization',
		'settlement' => 'Settlement',
		'purpose' => 'Purpose',
		'billsafe_transaction_id' => 'Transaction ID',
		'reservation_number' =>'Reservation number',
		'activation_invoice_number' => 'Invoice activation number',
		'bank_account_holder' => 'Account holder',
		'bank_account_number' => 'Account number',
		'bank_code' => 'Bank code',
		'bank_name' => 'Bank name',
		'iban' => 'IBAN',
		'bic' => 'BIC',
		'transaction_id' => 'Transaction ID',
		'payer_id' => 'Payer ID',
		'payer_email' => 'Payer email'
	);

	/**
	 * @param ShopGate $module
	 */
	public function __construct($module)
	{
		$this->module = $module;

		$dom = new DOMDocument();
		$dom->load(dirname(__FILE__).'/../config/payment.xml');

		/** @var SimpleXmlElement $payments */
		$payments = simplexml_import_dom($dom);

		foreach ($payments as $payment)
		{
			$key = (string)$payment->key;
			$this->_methods[$key]['title'] = $this->module->l((string)$payment->name);
			$this->_methods[$key]['blocked'] = $this->_getPaymentState($payment, 'blocked', $payments);
			$this->_methods[$key]['blocked_paid'] = $this->_getPaymentState($payment, 'blocked_paid', $payments);
			$this->_methods[$key]['not_blocked_paid'] = $this->_getPaymentState($payment, 'not_blocked_paid', $payments);
			$this->_methods[$key]['not_blocked_not_paid'] = $this->_getPaymentState($payment, 'not_blocked_not_paid', $payments);
			$this->_methods[$key]['blocked_shipped'] = $this->_getPaymentState($payment, 'blocked_shipped', $payments);
			$this->_methods[$key]['not_blocked_shipped'] = $this->_getPaymentState($payment, 'not_blocked_shipped', $payments);
		}
	}

	/**
	 * @param $payment
	 * @param $key
	 * @param $payments
	 * @return string
	 */
	protected function _getPaymentState($payment, $key, $payments)
	{
		return (string)$payment->{$key}
			? (string)$payment->{$key}
			: (string)$payments->default->{$key};
	}

	/**
	 * @param $key
	 * @return mixed
	 * @throws ShopgateLibraryException
	 */
	public function getPaymentMethodByKey($key)
	{
		return isset($this->_methods[$key]) ? $this->_methods[$key] : $this->_methods[self::DEFAULT_IDENTIFIER_MOBILE_PAYMENT];
	}

	/**
	 * @param $order ShopgateOrder
	 * @param bool $isNew
	 * @return array()
	 */
	public function getOrderStateId($order, $isNew = true)
	{
		$orderStateIds = array();
		$method = $this->getPaymentMethodByKey($order->getPaymentMethod());

		switch ($isNew)
		{
			/**
			 * new order
			 */
			case true :
				if ($order->getIsShippingBlocked())
				{
					/**
					 * shipping is blocked
					 */
					array_push($orderStateIds, Configuration::get($method['blocked']));
				}
				else
				{
					if ($order->getIsPaid())
					{
						/**
						 * not blocked paid
						 */
						array_push($orderStateIds, Configuration::get($method['not_blocked_paid']));
					}
					else
					{
						/**
						 * not blocked not paid
						 */
						array_push($orderStateIds, Configuration::get($method['not_blocked_not_paid']));
					}
				}
				break;
			/**
			 * update order
			 */
			case false :
				/**
				 * payment is updated
				 */
				if ($order->getUpdatePayment() && $order->getIsPaid() && !$order->getIsShippingBlocked())
					array_push($orderStateIds, Configuration::get($method['not_blocked_paid']));

				/**
				 * shipping is updated
				 */
				if ($order->getUpdateShipping())
					switch ($order->getIsShippingBlocked())
					{
						case false:
							array_push($orderStateIds, Configuration::get($method['not_blocked_shipped']));
							break;
						case true :
							array_push($orderStateIds, Configuration::get($method['blocked_shipped']));
							break;
					}

				break;
		}
		return $orderStateIds;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getPaymentTitleByKey($key)
	{
		$method = $this->getPaymentMethodByKey($key);
		return $method['title'];
	}

	/**
	 * @return array
	 */
	public function getPaymentMethods()
	{
		return $this->_methods;
	}

	/**
	 * @return array
	 */
	public function getPaymentInfoStrings()
	{
		return $this->paymentInfoStrings;
	}

	/**
	 * @param $key
	 * @return string
	 */
	public function getPaymentInfoStringByKey($key)
	{
		return $this->paymentInfoStrings[$key];
	}
}