<?php

namespace InstaPunchout\Punchout\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Customer session
     * @var Magento\Customer\Model\Session
     */
    protected $session;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \InstaPunchout\Punchout\Model\Configuration
     */
    protected $configuration;

    /**
     * @var \InstaPunchout\Punchout\Model\Punchout
     */
    protected $punchout;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    private $orderService;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Rate
     */
    private $shippingRate;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * Login constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\UrlFactory $urlFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Quote\Model\Quote\Address\Rate $shippingRate
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->url = $urlFactory->create();
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->orderService = $orderService;
        $this->shippingRate = $shippingRate;
        $this->productFactory = $productFactory;
    }

    private function getAllowedCountries()
    {
        $countries = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Directory\Model\AllowedCountries::class)->getAllowedCountries();
        $res = array();
        foreach ($countries as $k => $v) {
            $res[] = $v;
        }
        return $res;
    }

    private function getCompanies()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $repo = NULL;
        if (class_exists(\Aheadworks\Ca\Model\CompanyRepository::class)) {
            $repo = $objectManager->get('\Aheadworks\Ca\Model\CompanyRepository');
        } else if (class_exists(\Magento\Company\Model\CompanyRepository::class)) {
            $repo = $objectManager->get('\Magento\Company\Model\CompanyRepository');
        } else {
            return [];
        }
        $builder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $searchCriteria = $builder->create();

        $companies = [];
        foreach ($repo->getList($searchCriteria)->getItems() as $value) {
            $company_name = $value->getCompanyName();
            $companies[] = ['id' => $value->getId(), 'name' => isset($company_name) ? $company_name : $value->getName()];
        }
        return $companies;
    }

    private function getOptions()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->get('\Magento\Customer\Model\ResourceModel\Group\Collection');
        $websites = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $websites[] = ['value' => $website->getId(), 'label' => $website->getName()];
        }
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            $stores[] = ['value' => $store->getId(), 'label' => $store->getName()];
        }
        return [
            "companies" => $this->getCompanies(),
            "groups" => $collection->toOptionArray(),
            "websites" => $websites,
            "stores" => $stores,
            "allowed_countries" => $this->getAllowedCountries(),
        ];
    }

    private function prepareCustomer($res)
    {
        // get magento 2 customer by email
        $email = $res['email'];
        $updated = false;
        try {
            $customer = $this->customerRepository->get($email);
        } catch (\Exception $e) {
            $customer = $this->customerFactory->create();
            $customer->setEmail($email);
            $customer->setFirstname($res['firstname']);
            $customer->setLastname($res['lastname']);
            $customer->setPassword($res['password']);
            $customer->setStoreId($this->storeManager->getStore()->getId());
            $customer->save();
        }

        $customer = $this->customerRepository->get($email);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (isset($res['properties']) && isset($res['properties']['extension_attributes'])) {
            $customer = $this->customerRepository->get($email);
            $attributes = $customer->getExtensionAttributes();
            foreach ($res['properties']['extension_attributes'] as $key => $value) {
                $attributes->setData($key, $value);
            }
            $customer->setExtensionAttributes($attributes);
            $updated = true;
        }

        if (isset($res['properties']) && isset($res['properties']['custom_attributes'])) {
            $customer = $this->customerRepository->get($email);
            foreach ($res['properties']['custom_attributes'] as $key => $value) {
                $customer->setCustomAttribute($key, $value);
            }
            $updated = true;
        }

        if (isset($res['company_id'])) {
            if (class_exists(\Aheadworks\Ca\Api\Data\CompanyUserInterfaceFactory::class)) {
                $factory = $objectManager->get(\Aheadworks\Ca\Api\Data\CompanyUserInterfaceFactory::class);
                $attributes = $customer->getExtensionAttributes();
                $company_user = $attributes->getAwCaCompanyUser();
                if (!$company_user) {
                    $company_user = $factory->create();
                }

                $company_user->setCompanyId($res['company_id']);
                $attributes->setAwCaCompanyUser($company_user);
                $customer->setExtensionAttributes($attributes);
                $updated = true;
            }
        }

        if ($updated) {
            $this->customerRepository->save($customer);
        }

        return $customer;
    }

    private function clearCart()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get(\Magento\Checkout\Model\Cart::class);
        $quoteItems = $cart->getQuote()->getItemsCollection();
        foreach ($quoteItems as $item) {
            $cart->removeItem($item->getId());
        }
        $cart->save();
    }

    private function getCart()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get(\Magento\Checkout\Model\Cart::class);

        // get quote items collection
        $itemsCollection = $cart->getQuote()->getItemsCollection();

        // get array of all items what can be display directly
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();

        // get array of all items what can be display directly
        $items = [];

        foreach ($itemsVisible as $item) {
            $item_data = $item->getData();
            $options = [];
            foreach ($item->getOptions() as $option) {
                array_push($options, $option->getData());
            }
            $item_data['options'] = $options;
            $item_data['attributes'] = $item->getExtensionAttributes();
            $items[] = $item_data;
            /*                        echo 'ID: '.$item->getProductId().'<br />';
            echo 'Name: '.$item->getName().'<br />';
            echo 'Sku: '.$item->getSku().'<br />';
            echo 'Quantity: '.$item->getQty().'<br />';
            echo 'Price: '.$item->getPrice().'<br />';
            echo "<br />";    */
        }
        $currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        return [
            'items' => $items,
            'currency' => $currency,
        ];
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $response = NULL;
            $path = $this->getRequest()->getParam('path');
            if ($path == 'options.json') {
                $response = $this->getOptions();
            } else if ($path == 'script') {
                $punchout_id = $this->session->getPunchoutId();
                if (empty($punchout_id)) {
                    $response = "";
                } else {
                    $response = $this->get('https://punchout.cloud/punchout.js?id=' . $punchout_id);
                }
                $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                return $result
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
                    ->setHeader('Content-Type', 'application/javascript;charset=UTF-8')
                    ->setContents($response);
            } else if ($path == 'cart.json') {
                $response = $this->getCart();
            } else if ($path == 'cart') {
                $punchout_id = $this->session->getPunchoutId();
                if (isset($punchout_id)) {
                    $cart = $this->getCart();
                    $data = [
                        'cart' => [
                            'Magento2' => $cart,
                        ]
                    ];
                    $response = $this->post('https://punchout.network/cart/' . $punchout_id, $data);
                } else {
                    $response = ['message' => "You're not in a punchout session"];
                }
            } else if ($path == 'order.json') {
                $token = $this->getRequest()->getParam('token');
                $res = $this->post('https://punchout.cloud/authorize', ["authorization" => $token]);
                if ($res["authorized"] == true) {
                    $body = $this->getRequest()->getContent();
                    $response = $this->createOrder(json_decode($body, true));
                } else {
                    $response = ["error" => "You're not authorized"];
                }
            }


            if ($response != NULL) {
                // return json response
                $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                return $result
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
                    ->setHeader('Content-Type', 'application/json;charset=UTF-8')
                    ->setContents(json_encode($response, JSON_PRETTY_PRINT));
            }

            $resultRedirect = $this->resultRedirectFactory->create();

            // no need for further sanization as we need to capture all the server data as is
            $server = json_decode(json_encode($_SERVER), true);
            // no need for further sanization as we need to capture all the query data as is
            $query = json_decode(json_encode($_GET), true);

            $data = array(
                'headers' => getallheaders(),
                'server' => $server,
                'body' => file_get_contents('php://input'),
                'query' => $query,
            );

            $res = $this->post('https://punchout.cloud/proxy', $data);

            if ($res['action'] == 'print') {
                header('content-type: application/xml');
                $xml = new SimpleXMLElement($res['body']);
                echo $xml->asXML();
            } else if ($res['action'] == 'login') {

                // log out customer
                if ($this->session->isLoggedIn()) {
                    $lastCustomerId = $this->session->getId();
                    $this->session->logout()->setLastCustomerId($lastCustomerId);
                }

                $customer = $this->prepareCustomer($res);

                $this->_eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);

                // login magento 2 customer
                $this->session->setCustomerDataAsLoggedIn($customer);
                $this->session->regenerateId();

                if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                    $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                    $metadata->setPath('/');
                    $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
                }

                $this->clearCart();

                // Add punchout session ID to customer session
                $this->session->setPunchoutId($res['punchout_id']);

                // Fake request method to trigger version update for private content
                $this->_request->setMethod(\Zend\Http\Request::METHOD_POST);

                // redirect to punchout
                $resultRedirect->setUrl('/');
                return $resultRedirect;
            } else {
                echo "v0.0.28 unknwon action " . esc_html($res['action']);
                echo json_encode($data);
                echo json_encode($res);
            }
            exit;
        } catch (\Throwable $e) {
            die(json_encode(["error" => $e->getMessage()]));
        }
        $defaultUrl = $this->url->getUrl('/', ['_secure' => true]);
        $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
        return $resultRedirect;
    }

    private function createOrder($orderData)
    {
        //init the store id and website id @todo pass from array
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        //init the customer
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']); // load customet by email address

        //check the customer
        if (!$customer->getEntityId()) {

            //If not available then create this customer
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderData['firstname'])
                ->setLastname($orderData['lastname'])
                ->setEmail($orderData['email'])
                ->setPassword($orderData['password']);

            $customer->save();
        }

        //init the quote
        $cart_id = $this->cartManagement->createEmptyCart();
        $cart = $this->cartRepository->get($cart_id);

        $cart->setStore($store);

        // if you have already had the buyer id, you can load customer directly
        $customer = $this->customerRepository->getById($customer->getEntityId());
        $this->session->setCustomerDataAsLoggedIn($customer);
        $cart->setCurrency();
        $cart->assignCustomer($customer); //Assign quote to customer

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectFactory = $objectManager->get('\Magento\Framework\DataObject\Factory');
        //add items in quote
        foreach ($orderData['items'] as $item) {
            $product = $this->productFactory->create()->load($item['product']);
            $options = $objectFactory->create($item);
            $cart->addProduct(
                $product,
                $options,
            );
        }

        //Set Address to quote @todo add section in order data for seperate billing and handle it
        $cart->getBillingAddress()->addData($orderData['billing']);
        $cart->getShippingAddress()->addData($orderData['shipping']);


        if ($orderData['ignore_address_validation']) {
            $cart->getBillingAddress()->setShouldIgnoreValidation(true);
            if (!$cart->getIsVirtual()) {
                $cart->getShippingAddress()->setShouldIgnoreValidation(true);
            }
        }

        // Collect Rates, Set Shipping & Payment Method
        $this->shippingRate
            ->setCode($orderData['shipping_method'])
            ->getPrice(1);


        $shippingAddress = $cart->getShippingAddress();

        //@todo set in order data
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($orderData['shipping_method']); // 'flatrate_flatrate'); //shipping method
        $cart->getShippingAddress()->addShippingRate($this->shippingRate);

        $cart->setPaymentMethod($orderData['payment_method']); //'checkmo'); //payment method

        //@todo insert a variable to affect the invetory
        $cart->setInventoryProcessed(false);

        // Set sales order payment
        $cart->getPayment()->importData(['method' => $orderData['payment_method']]);

        // Collect total and save
        $cart->collectTotals();

        // Submit the quote and create the order
        $cart->save();
        $cart = $this->cartRepository->get($cart->getId());
        $order_id = $this->cartManagement->placeOrder($cart->getId());
        return ['id' => $order_id];
    }

    private function post($url, $data = null, $format = 'json', $response = 'json')
    {
        $headers = [
            'Accept: application/' . $response,
            'Content-Type: application/' . $format,
        ];
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        if ($format == 'json' && isset($data)) {
            $data = json_encode($data);
        }
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        if ($response == 'json') {
            $response = json_decode(curl_exec($handle), true);
        } else {
            $response = curl_exec($handle);
        }
        curl_close($handle);
        if (isset($response->error) && isset($response->message)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($response->message));
        }
        return $response;
    }

    public function get($url)
    {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($handle);
        curl_close($handle);
        return $response;
    }
}
