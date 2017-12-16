<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface;
use ParadoxLabs\Subscriptions\Model\Service\RelatedObjectManager;
use ParadoxLabs\Subscriptions\Model\Source\Status;

/**
 * Subscription data storage and processing
 */
class Subscription extends \Magento\Framework\Model\AbstractExtensibleModel implements SubscriptionInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'paradoxlabs_subscription';

    /**
     * @var string
     */
    protected $_eventObject = 'subscription';

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @var Source\Status
     */
    protected $statusSource;

    /**
     * @var Source\Period
     */
    protected $periodSource;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $relatedObjects = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * @var array
     */
    protected $additionalInfo;

    /**
     * @var TimezoneInterface
     */
    protected $dateProcessor;

    /**
     * @var RelatedObjectManager
     */
    protected $relatedObjectManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param LogFactory $logFactory
     * @param Source\Status $statusSource
     * @param Source\Period $periodSource
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository *Proxy
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $emulator
     * @param TimezoneInterface $dateProcessor
     * @param RelatedObjectManager $relatedObjectManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \ParadoxLabs\Subscriptions\Model\LogFactory $logFactory,
        \ParadoxLabs\Subscriptions\Model\Source\Status $statusSource,
        \ParadoxLabs\Subscriptions\Model\Source\Period $periodSource,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulator,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor,
        \ParadoxLabs\Subscriptions\Model\Service\RelatedObjectManager $relatedObjectManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logFactory = $logFactory;
        $this->statusSource = $statusSource;
        $this->periodSource = $periodSource;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->emulator = $emulator;
        $this->dateProcessor = $dateProcessor;
        $this->relatedObjectManager = $relatedObjectManager;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Set source quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return $this
     */
    public function setQuote(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $this->setData('quote', $quote);
        $this->setQuoteId($quote->getId());

        return $this;
    }

    /**
     * Get subscription quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Exception
     */
    public function getQuote()
    {
        if ($this->hasData('quote') !== true && $this->hasData('quote_id')) {
            // If we are not in the correct scope, we have to emulate it to load the quote.
            $emulate = ($this->storeManager->getStore()->getStoreId() != $this->getStoreId());
            if ($emulate === true) {
                $this->emulator->startEnvironmentEmulation($this->getStoreId());
            }

            try {
                $quote = $this->cartRepository->get($this->getQuoteId());
            } catch (\Exception $e) {
                if ($emulate === true) {
                    $this->emulator->stopEnvironmentEmulation();
                }

                throw $e;
            }

            if ($emulate === true) {
                $this->emulator->stopEnvironmentEmulation();
            }

            $this->setData('quote', $quote);
        }

        return $this->getData('quote');
    }

    /**
     * Set source quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get source quote ID
     *
     * @return int
     */
    public function getQuoteId()
    {
        return (int)$this->getData('quote_id');
    }

    /**
     * Set subscription frequency count
     *
     * @param int $frequencyCount
     * @return $this
     */
    public function setFrequencyCount($frequencyCount)
    {
        return $this->setData('frequency_count', (int)$frequencyCount);
    }

    /**
     * Set subscription frequency unit
     *
     * @param string $frequencyUnit
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setFrequencyUnit($frequencyUnit)
    {
        if ($this->periodSource->isAllowedPeriod($frequencyUnit)) {
            $this->setData('frequency_unit', $frequencyUnit);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid frequency unit "%1"', $frequencyUnit)
            );
        }

        return $this;
    }

    /**
     * Set subscription length (number of billings to last for)
     *
     * @param int $length
     * @return $this
     */
    public function setLength($length)
    {
        return $this->setData('length', (int)$length);
    }

    /**
     * Set subscription description. This will typically (but not necessarily) be the item name.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData('description', $description);
    }

    /**
     * Get description. This will typically be the item name.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData('description');
    }

    /**
     * Set subscription subtotal. Mostly for record's sake; actual amount is handled by the quote.
     *
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        return $this->setData('subtotal', (float)$subtotal);
    }

    /**
     * Associate a given order with the subscription, and record the transaction details.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string|null $message
     * @return $this
     */
    public function recordBilling(
        \Magento\Sales\Api\Data\OrderInterface $order,
        $message = null
    ) {
        /**
         * Update last_run
         */
        $this->updateLastRunTime();

        /**
         * Increment run_count
         */
        $this->incrementRunCount();

        /**
         * Check completion case
         */
        if ($this->isComplete()) {
            $this->setStatus(Status::STATUS_COMPLETE);
        }

        /**
         * Log the event
         */
        $this->addLog($message, $order->getIncrementId(), $order->getId());

        return $this;
    }

    /**
     * Set subscription status.
     *
     * @param string $newStatus
     * @param string $message Message to log for the change (optional)
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setStatus($newStatus, $message = null)
    {
        $oldStatus = $this->getStatus();

        if ($newStatus != $oldStatus) {
            if ($this->statusSource->isAllowedStatus($newStatus)) {
                $this->setData('status', $newStatus);

                $this->_eventManager->dispatch(
                    'paradoxlabs_subscription_status_change',
                    [
                        'old_status'   => $oldStatus,
                        'new_status'   => $newStatus,
                        'message'      => $message,
                        'subscription' => $this,
                    ]
                );

                /**
                 * If status changed, log the event.
                 */
                if ($oldStatus != '') {
                    if ($message !== null) {
                        $this->addLog($message);
                    } else {
                        $this->addLog(__("Status changed to '%1'", $this->getStatus()));
                    }
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid status "%1"', $newStatus));
            }
        }

        return $this;
    }

    /**
     * Calculate and set next run date for the subscription.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function calculateNextRun()
    {
        if ($this->getFrequencyCount() == '' || $this->getFrequencyUnit() == '') {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Subscription frequency must be set to calculate schedule.')
            );
        }

        /**
         * Try to maintain prior schedule. New next_run is current next_run + frequency,
         * by multiple if needed to hit a future date.
         */
        $nextRunTime = strtotime($this->getData('next_run'));

        if ($nextRunTime == 0) {
            $nextRunTime = time();
        }

        do {
            $nextRunTime = strtotime(
                sprintf('+%s %s', $this->getFrequencyCount(), $this->getFrequencyUnit()),
                $nextRunTime
            );
        } while ($nextRunTime < time());

        /**
         * Convert to UTC date and set.
         */
        $next = $this->dateProcessor->date('@' . $nextRunTime, null, false);

        return $this->setNextRun($next->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
    }

    /**
     * Set subscription customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Get subscription customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Increment run_count by one.
     *
     * @return $this
     */
    public function incrementRunCount()
    {
        $this->setData('run_count', $this->getRunCount() + 1);

        return $this;
    }

    /**
     * Set subscription store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Get subscription store ID.
     *
     * @return int
     */
    public function getStoreId()
    {
        return (int)$this->getData('store_id');
    }

    /**
     * Set the next run date for the subscription.
     *
     * @param string|int $nextRun Next run date (date or timestamp) IN UTC
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setNextRun($nextRun)
    {
        if (!is_numeric($nextRun)) {
            $nextRun = strtotime($nextRun);
        }

        if ($nextRun == 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide a valid date for the next scheduled run.')
            );
        }

        $next = $this->dateProcessor->date('@' . $nextRun, null, false);

        return $this->setData('next_run', $next->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
    }

    /**
     * Get next-run date.
     *
     * @return string
     */
    public function getNextRun()
    {
        return $this->getData('next_run');
    }

    /**
     * Get created-at date.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set created-at date.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData('created_at', $createdAt);

        return $this;
    }

    /**
     * Get updated-at date.
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated-at date.
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData('updated_at', $updatedAt);

        return $this;
    }

    /**
     * Get last-run date.
     *
     * @return string
     */
    public function getLastRun()
    {
        return $this->getData('last_run');
    }

    /**
     * Set the next run date for the subscription.
     *
     * @param string|int $lastRun Next run date (date or timestamp) IN UTC
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setLastRun($lastRun)
    {
        if (!is_numeric($lastRun)) {
            $lastRun = strtotime($lastRun);
        }

        if ($lastRun == 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide a valid date for last run.')
            );
        }

        $next = $this->dateProcessor->date('@' . $lastRun, null, false);

        return $this->setData('last_run', $next->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
    }

    /**
     * Get subscription subtotal.
     *
     * @return float
     */
    public function getSubtotal()
    {
        return (float)$this->getData('subtotal');
    }

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ParadoxLabs\Subscriptions\Model\ResourceModel\Subscription');
    }

    /**
     * Finalize before saving.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        /**
         * Save child records in conjunction with the parent.
         */
        $this->relatedObjectManager->saveRelatedObjects(
            $this,
            $this->relatedObjects['before']
        );

        /**
         * Make sure we have the quote.
         */
        if ($this->getQuoteId() < 1 && $this->hasData('quote')) {
            $this->setQuoteId($this->getQuote()->getId());
        }

        /**
         * Update dates.
         */
        $now = $this->dateProcessor->date(null, null, false);

        if ($this->isObjectNew()) {
            $this->setCreatedAt($now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        $this->setUpdatedAt($now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return $this;
    }

    /**
     * Check whether subscription has billed to the prescribed length.
     *
     * @return bool
     */
    public function isComplete()
    {
        if ($this->getStatus() === Source\Status::STATUS_COMPLETE) {
            return true;
        }

        if ($this->getLength() > 0 && $this->getRunCount() >= $this->getLength()) {
            return true;
        }

        return false;
    }

    /**
     * Get subscription length.
     *
     * @return int
     */
    public function getLength()
    {
        return (int)$this->getData('length');
    }

    /**
     * Get number of times the subscription has run.
     *
     * @return int
     */
    public function getRunCount()
    {
        return (int)$this->getData('run_count');
    }

    /**
     * Set number of times the subscription has run.
     *
     * @param int $runCount
     * @return $this
     */
    public function setRunCount($runCount)
    {
        $this->setData('run_count', $runCount);

        return $this;
    }

    /**
     * Get subscription status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set last_run to the current datetime.
     *
     * @return $this
     */
    public function updateLastRunTime()
    {
        $now = $this->dateProcessor->date(null, null, false);

        $this->setLastRun($now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return $this;
    }

    /**
     * Get subscription frequency count
     *
     * @return int
     */
    public function getFrequencyCount()
    {
        return (int)$this->getData('frequency_count');
    }

    /**
     * Get subscription frequency unit
     *
     * @return string
     */
    public function getFrequencyUnit()
    {
        return $this->getData('frequency_unit');
    }

    /**
     * Add a new log to the subscription.
     *
     * @param string $message
     * @param string $incrementId
     * @param string $orderId
     * @return $this
     */
    public function addLog($message, $incrementId = null, $orderId = null)
    {
        /** @var \ParadoxLabs\Subscriptions\Model\Log $log */
        $log = $this->logFactory->create();
        $log->setSubscription($this);
        $log->setStatus($this->getStatus());
        $log->setOrderIncrementId($incrementId);
        $log->setOrderId($orderId);
        $log->setDescription((string)$message);

        $this->addRelatedObject($log);

        return $this;
    }

    /**
     * Retrieve array of related objects
     *
     * @return array
     */
    public function getRelatedObjects()
    {
        return $this->relatedObjects;
    }

    /**
     * Add object to related objects, to be saved with the parent model
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param bool $saveBeforeParent
     * @return $this
     */
    public function addRelatedObject(\Magento\Framework\Model\AbstractModel $object, $saveBeforeParent = false)
    {
        if ($saveBeforeParent === false) {
            $this->relatedObjects['after'][] = $object;
        } else {
            $this->relatedObjects['before'][] = $object;
        }

        return $this;
    }

    /**
     * Processing object after save data
     *
     * @return $this
     */
    public function afterSave()
    {
        /**
         * Save child records in conjunction with the parent.
         */
        $this->relatedObjectManager->saveRelatedObjects(
            $this,
            $this->relatedObjects['after']
        );

        parent::afterSave();

        return $this;
    }

    /**
     * Get additional information.
     *
     * If $key is set, will return that value or null; otherwise, will return an array of all additional date.
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function getAdditionalInformation($key = null)
    {
        if ($this->additionalInfo === null) {
            $this->additionalInfo = json_decode(parent::getData('additional_information'), 1);

            if (empty($this->additionalInfo)) {
                $this->additionalInfo = [];
            }
        }

        if ($key !== null) {
            return (isset($this->additionalInfo[$key]) ? $this->additionalInfo[$key] : null);
        }

        return $this->additionalInfo;
    }

    /**
     * Set additional information.
     *
     * Can pass in a key-value pair to set one value, or a single parameter (associative array) to overwrite all data.
     *
     * @param string|array $key
     * @param string|null $value
     * @return $this
     */
    public function setAdditionalInformation($key, $value = null)
    {
        if ($value !== null) {
            if ($this->additionalInfo === null) {
                $this->getAdditionalInformation();
            }

            $this->additionalInfo[$key] = $value;
        } elseif (is_array($key)) {
            $this->additionalInfo = $key;
        }

        parent::setData('additional_information', json_encode($this->additionalInfo));

        return $this;
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \ParadoxLabs\Subscriptions\Api\Data\SubscriptionExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
