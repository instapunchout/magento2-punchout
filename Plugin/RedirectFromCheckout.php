<?php

namespace InstaPunchout\Punchout\Plugin;

use Magento\Framework\Controller\ResultFactory;

/**
 * Redirects punchout sessions away from the standard checkout page back to the cart.
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
     *
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
     * Redirect to cart when a punchout session attempts to open the checkout page.
     *
     * @param \Magento\Checkout\Controller\Onepage $subject
     * @param \Closure $next
     * @return \Magento\Framework\Controller\Result\Redirect|mixed
     */
    public function aroundExecute(
        \Magento\Checkout\Controller\Onepage $subject,
        \Closure $next
    ) {
        $punchoutId = $this->session->getPunchoutId();
        $punchoutSessionId = $this->session->getPunchoutSessionId();
        if (!$punchoutId || !$punchoutSessionId || $punchoutSessionId !== $this->session->getId()) {
            return $next();
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }
}
