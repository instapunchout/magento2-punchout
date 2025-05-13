<?php

namespace InstaPunchout\Punchout\Plugin;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class RedirectFromCheckout
 * @package InstaPunchout\Punchout\Plugin
 */
class RedirectFromCheckout
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * RedirectFromCheckout constructor.
     * @param ResultFactory $resultFactory
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Customer\Model\Session $session
    ) {
        $this->resultFactory = $resultFactory;
        $this->session = $session;
    }

    /**
     * @param \Magento\Checkout\Controller\Onepage $subject
     * @param \Closure $next
     * @return \Magento\Framework\Controller\Result\Redirect|mixed
     */
    public function aroundExecute(
        \Magento\Checkout\Controller\Onepage $subject,
        \Closure $next
    ) {
        if (!$this->session->getPunchoutId() || !$this->session->getPunchoutSessionId() || $this->session->getPunchoutSessionId() !== $this->session->getId()) {
            return $next();
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }
}