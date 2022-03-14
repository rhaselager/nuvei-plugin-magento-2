<?php

namespace Nuvei\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Nuvei\Checkout\Model\Config as ModuleConfig;
use Nuvei\Checkout\Model\Request\Payment\Factory as PaymentRequestFactory;

/**
 * Nuvei Checkout payment model.
 *
 * * TODO - Cc class is deprecated. Use \Magento\Payment\Model\MethodInterface instead.
 */
class Payment extends Cc implements TransparentInterface
{
    /**
     * Method code const.
     */
    const METHOD_CODE   = 'nuvei';
    const MODE_LIVE     = 'live';

    /**
     * Additional information const.
     */
    const KEY_LAST_ST           = 'last_session_token';
    const KEY_CC_TEMP_TOKEN     = 'cc_temp_token';
    const KEY_CHOSEN_APM_METHOD = 'chosen_apm_method';

    /**
     * Transaction keys const.
     */
    const TRANSACTION_REQUEST_ID                = 'transaction_request_id';
    const TRANSACTION_ORDER_ID                  = 'nuvei_order_id';
    const TRANSACTION_AUTH_CODE                 = 'authorization_code';
    const TRANSACTION_ID                        = 'transaction_id';
    const TRANSACTION_PAYMENT_SOLUTION          = 'payment_solution';
    const TRANSACTION_PAYMENT_METHOD            = 'external_payment_method';
    const TRANSACTION_STATUS                    = 'status';
    const TRANSACTION_TYPE                      = 'transaction_type';
    const SUBSCR_IDS                            = 'subscr_ids'; // list with subscription IDs
    const TRANSACTION_UPO_ID                    = 'upo_id';
    const TRANSACTION_TOTAL_AMOUN               = 'total_amount';
    const REFUND_TRANSACTION_AMOUNT             = 'refund_amount';
    const AUTH_PARAMS                           = 'auth_params';
    const SALE_SETTLE_PARAMS                    = 'sale_settle_params';
    const ORDER_TRANSACTIONS_DATA               = 'nuvei_order_transactions_data';
    const CREATE_ORDER_DATA                     = 'nuvei_create_order_data';

    /**
     * Order statuses.
     */
    const SC_AUTH               = 'nuvei_auth';
    const SC_SETTLED            = 'nuvei_settled';
    const SC_VOIDED             = 'nuvei_voided';
    const SC_REFUNDED           = 'nuvei_refunded';
    const SC_PROCESSING         = 'nuvei_processing';
    const SC_SUBSCRT_STARTED    = 'nuvei_subscr_started';
    const SC_SUBSCRT_ENDED      = 'nuvei_subscr_ended';

    const SOLUTION_INTERNAL     = 'internal';
    const SOLUTION_EXTERNAL     = 'external';
    const APM_METHOD_CC         = 'cc_card';
    
    const PAYMETNS_SUPPORT_REFUND = ['cc_card', 'apmgw_expresscheckout'];

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Form block.
     *
     * @var string
     */
    protected $_formBlockType = \Magento\Payment\Block\Transparent\Info::class;

    /**
     * Info block.
     *
     * @var string
     */
    protected $_infoBlockType = \Nuvei\Checkout\Block\ConfigurableInfo::class;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var PaymentRequestFactory
     */
    private $paymentRequestFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Payment constructor.
     *
     * @param Context                         $context
     * @param CoreRegistry                    $registry
     * @param ExtensionAttributesFactory      $extensionFactory
     * @param AttributeValueFactory           $customAttributeFactory
     * @param Data                            $paymentData
     * @param ScopeConfigInterface            $scopeConfig
     * @param PaymentLogger                   $logger
     * @param ModuleListInterface             $moduleList
     * @param TimezoneInterface               $localeDate
     * @param PaymentRequestFactory           $paymentRequestFactory
     * @param CustomerSession                 $customerSession
     * @param ModuleConfig                    $moduleConfig
     * @param CheckoutSession                 $checkoutSession
     * @param AbstractResource|null           $resource
     * @param AbstractDb|null                 $resourceCollection
     * @param array                           $data
     */
    public function __construct(
        Context $context,
        CoreRegistry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        PaymentLogger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        PaymentRequestFactory $paymentRequestFactory,
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        CheckoutSession $checkoutSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );

        $this->paymentRequestFactory    = $paymentRequestFactory;
        $this->customerSession            = $customerSession;
        $this->moduleConfig                = $moduleConfig;
        $this->checkoutSession            = $checkoutSession;
    }

    /**
     * Assign data.
     *
     * @param DataObject $data Data object.
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $chosenApmMethod = !empty($additionalData[self::KEY_CHOSEN_APM_METHOD])
            ? $additionalData[self::KEY_CHOSEN_APM_METHOD] : null;
        
        $lastSessionToken = !empty($additionalData[self::KEY_LAST_ST])
            ? $additionalData[self::KEY_LAST_ST] : null;

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(self::KEY_LAST_ST, $lastSessionToken);
        $info->setAdditionalInformation(self::KEY_CHOSEN_APM_METHOD, $chosenApmMethod);

        return $this;
    }

    /**
     * Validate payment method information object.
     *
     * @return Payment
     * @throws LocalizedException
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Check if payment method can be used for provided currency.
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Authorize payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $this->processPayment($payment, $amount);

        return $this;
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        /** @var RequestInterface $request */
        $request = $this->paymentRequestFactory->create(
            AbstractRequest::PAYMENT_REFUND_METHOD,
            $payment,
            $amount
        );
        $request->process();

        return $this;
    }

    /**
     * Cancel payment method.
     *
     * @param InfoInterface $payment
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function cancel(InfoInterface $payment)
    {
        parent::cancel($payment);

        $this->void($payment);

        return $this;
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function void(InfoInterface $payment)
    {
        parent::void($payment);
        
        /** @var RequestInterface $request */
        $request = $this->paymentRequestFactory->create(
            AbstractRequest::PAYMENT_VOID_METHOD,
            $payment
        );
        
        $request->process();
        return $this;
    }

    /**
     * {inheritdoc}
     */
    public function getConfigInterface()
    {
        return $this;
    }
    
    /**
     * Check void availability
     * @return bool
     * @internal param \Magento\Framework\DataObject $payment
     */
    public function canVoid()
    {
        return $this->_canVoid;
    }
}
