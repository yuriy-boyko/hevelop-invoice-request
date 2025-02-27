<?php
/**
 * Copyright © Hevelop srl. All rights reserved.
 * @license https://opensource.org/licenses/agpl-3.0  AGPL-3.0 License
 * @author Samuele Martini <samuele.martini@hevelop.com>
 * @copyright Copyright (c) 2020 Hevelop srl (https://hevelop.com)
 * @package Hevelop_InvoiceRequest
 */

namespace Hevelop\InvoiceRequest\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SaveDataToOrderObserver implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * SaveDataToOrderObserver constructor.
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param EventObserver $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();
        // This is a mapping of the customer vatId and Taxvat
        $vatData = explode(",", (string)$billingAddress->getVatId());

        $order->setEcWantInvoice($quote->getEcWantInvoice());

        if ($quote->getEcWantInvoice() == "1") {
            $order->setEcInvoiceType($quote->getEcInvoiceType());

            $addresses = $order->getAddresses();

            foreach ($addresses as $address) {
                if ($address->getAddressType() == 'billing') {
                    $address->setCompany($billingAddress->getCompany());
                    $address->setVatId($vatData[0]);
                    $address->setCodiceFiscale($billingAddress->getCodiceFiscale());
                    $address->setSdiCode($billingAddress->getSdiCode());
                    $address->setPec($billingAddress->getPec());
                }
            }

            if (isset($vatData[1])) {
                $order->setCustomerTaxvat($vatData[1]);
            }
        }

        return $this;
    }
}
