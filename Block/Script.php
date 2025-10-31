<?php
namespace InstaPunchout\Punchout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\ClientInterface;
use Psr\Log\LoggerInterface;

class Script extends Template
{
    protected $client;
    protected $logger;
    protected $session;

    public function __construct(
        Template\Context $context,
        Session $session,
        ClientInterface $client,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->session = $session;
        $this->client = $client;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Retrieves the inline script for the punchout session.
     *
     * @return string The inline script or an error message.
     */
    public function getInlineScript(): string
    {
        try {
            $punchoutId = $this->session->getPunchoutId();
            if (empty($punchoutId)) {
                return '<script nonce="punchout" async src="/punchout?path=script"></script>';
            } elseif (!$this->session->isLoggedIn()) {
                return '<script>// Punchout: Not logged in</script>';
            } else {
                // Fetch the external script
                $this->client->get("https://punchout.cloud/punchout.js?id=$punchoutId");
                if ($this->client->getStatus() === 200) {
                    return '<script nonce="punchout">' . $this->client->getBody() . '</script>';
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch external script: ' . $e->getMessage());
        }
        return '<script>// Failed to load external script</script>';
    }
}
