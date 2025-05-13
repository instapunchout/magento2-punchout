<?php
namespace InstaPunchout\Punchout\Controller\Index;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\DataObject\Factory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;

class Index extends Action
{
    /**
     * Customer session
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

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
     * @var \Magento\Customer\Model\CustomerFactory
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
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    private $collection;

    /**
     * @var \Magento\Framework\HTTP\ClientInterface
     */
    protected ClientInterface $client;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected Cart $cart;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected Factory $objectFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected Customer $customerModel;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected RawFactory $resultRawFactory;

    /**
     * @var \Magento\Directory\Model\AllowedCountries
     */
    protected $allowedCountries;

    /**
     * Login constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session  $session
     * @param \Magento\Framework\UrlFactory $urlFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Quote\Model\Quote\Address\Rate $shippingRate
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $collection
     * @param \Magento\Directory\Model\AllowedCountries $allowedCountries
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\HTTP\ClientInterface $client
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
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
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Collection $collection,
        \Magento\Directory\Model\AllowedCountries $allowedCountries,
        Cart $cart,
        ClientInterface $client,
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        ProductRepository $productRepository,
        Factory $objectFactory,
        Customer $customerModel,
        RawFactory $resultRawFactory,
    ) {
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
        $this->collection = $collection;
        $this->allowedCountries = $allowedCountries;
        $this->cart = $cart;
        $this->client = $client;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->objectFactory = $objectFactory;
        $this->customerModel = $customerModel;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    /**
     * Retrieves a list of companies from the appropriate repository.
     *
     * @return array An array of companies with their IDs and labels.
     */
    private function getCompanies()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $repo = null;
        if (class_exists(\Aheadworks\Ca\Model\CompanyRepository::class)) {
            $repo = $objectManager->get('\Aheadworks\Ca\Model\CompanyRepository');
        } elseif (class_exists(\Magento\Company\Model\CompanyRepository::class)) {
            $repo = $objectManager->get('\Magento\Company\Model\CompanyRepository');
        } else {
            return [];
        }
        $builder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $searchCriteria = $builder->create();

        $companies = [];
        foreach ($repo->getList($searchCriteria)->getItems() as $value) {
            $companyName = $value->getCompanyName();
            $companies[] = ['value' => $value->getId(), 'label' => $companyName ?? $value->getName()];
        }
        return $companies;
    }

    /**
     * Retrieves various options including companies, customer groups, websites, stores, and allowed countries.
     *
     * @return array An array containing options data.
     */
    private function getOptions()
    {
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
            "groups" => $this->collection->toOptionArray(),
            "websites" => $websites,
            "stores" => $stores,
            "allowed_countries" => $this->allowedCountries->getAllowedCountries(),
        ];
    }

    /**
     * Updates the customer object with the provided data.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer The customer object to update.
     * @param array $res The data to update the customer with.
     * @return bool True if the customer was updated, false otherwise.
     */
    private function updateCustomer($customer, $res)
    {
        $email = $res['email'];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $updated = false;
        if (isset($res['store_id'])) {
            $customer->setStoreId($res['store_id']);
            $updated = false;
        }

        if (isset($res['group_id'])) {
            $customer->setGroupId($res['group_id']);
            $updated = true;
        }

        if (isset($res['website_id'])) {
            $customer->setWebsiteId($res['website_id']);
            $updated = true;
        }

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

        return $updated;
    }

    /**
     * Prepares a customer object by retrieving or creating a customer based on the provided data.
     *
     * @param array $data Customer data including email, firstname, lastname, and other attributes.
     * @return \Magento\Customer\Api\Data\CustomerInterface The prepared customer object.
     */
    private function prepareCustomer($data)
    {
        // get magento 2 customer by email
        $email = $data['email'];
        try {
            $customer = $this->customerRepository->get($email);
        } catch (\Exception $e) {
            $customer = $this->customerFactory->create();
            $customer->setEmail($email);
            $customer->setFirstname($data['firstname']);
            $customer->setLastname($data['lastname']);
            $customer->setPassword($data['password']);
            $customer->setStoreId($this->storeManager->getStore()->getId());
            $customer->save();
        }

        $customer = $this->customerRepository->get($email);
        $this->updateCustomer($customer, $data);
        $this->customerRepository->save($customer);

        return $customer;
    }

    /**
     * Clears all items from the current customer's cart.
     */
    private function clearCart()
    {
        $quoteItems = $this->cart->getQuote()->getItemsCollection();
        foreach ($quoteItems as $item) {
            $this->cart->removeItem($item->getId());
        }
        $this->cart->save();
    }

    /**
     * Encodes the extension attributes of a given object into an array format.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesInterface $attributes The extension attributes to encode.
     * @return object Encoded extension attributes as an object.
     */
    private function encodeExtensionAttributes($attributes)
    {
        $data = [];
        foreach ($attributes->__toArray() as $key => $value) {
            try {
                if (is_array($value)) {
                    $values = [];
                    foreach ($value as $value2) {
                        $values[] = is_object($value2) ? $value2->getData() : $value2;
                    }
                    $data[$key] = $values;
                } elseif (is_object($value)) {
                    $data[$key] = $value->getValue();
                } else {
                    $data[$key] = $value;
                }
            } catch (\Throwable $th) {
                $data[$key] = ["error" => $th->getMessage()];
            }
        }
        return (object) $data;
    }

    /**
     * Encodes the product data, including extension attributes, custom attributes, and options.
     *
     * @param \Magento\Catalog\Model\Product $product The product to encode.
     * @return object Encoded product data as an object.
     */
    private function encodeProduct(\Magento\Catalog\Model\Product $product)
    {
        $item_data = $product->getData();

        $item_data['extension_attributes'] = $this->encodeExtensionAttributes($product->getExtensionAttributes());
        $data = [];
        foreach ($product->getCustomAttributes() as $key => $value) {
            $data[$value->getAttributeCode()] = $value->getValue();
        }
        $item_data['custom_attributes'] = (object) $data;

        $options = [];
        foreach ($product->getOptions() as $option) {
            $option_data = $option->getData();
            $values = [];
            $optionValues = $option->getValues();
            if ($optionValues) {
                foreach ($option->getValues() as $value) {
                    array_push($values, $value->getData());
                }
            }
            $option_data["values"] = $values;
            array_push($options, $option_data);
        }
        $item_data['options'] = $options;

        return (object) $item_data;
    }

    /**
     * Retrieves the current customer's cart details, including items and currency.
     *
     * @return array An array containing cart items and currency information.
     */
    private function getCart()
    {

        // get array of all items what can be display directly
        $itemsVisible = $this->cart->getQuote()->getAllVisibleItems();

        // get array of all items what can be display directly
        $items = [];

        foreach ($itemsVisible as $item) {
            $item_data = $item->getData();
            $options = [];
            foreach ($item->getOptions() as $option) {
                array_push($options, $option->getData());
            }
            $item_data['options'] = $options;
            $product = $this->productFactory->create()->load($item_data['product_id']);
            $item_data['product'] = $this->encodeProduct($product);

            $item_data['extension_attributes'] = $this->encodeExtensionAttributes($item->getExtensionAttributes());
            $data = [];
            foreach ($item->getCustomAttributes() as $key => $value) {
                $data[$value->getAttributeCode()] = $value->getValue();
            }
            $item_data['custom_attributes'] = (object) $data;

            $items[] = $item_data;

        }
        $currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        return [
            'items' => $items,
            'currency' => $currency,
        ];
    }

    /**
     * Checks if the API request is authorized by validating the provided token.
     *
     * @return bool True if the API is authorized, false otherwise.
     */
    private function checkAuthorization()
    {
        $token = $this->getRequest()->getHeader('Authorization') ?? $this->getRequest()->getParam('token');
        if (empty($token)) {
            throw new Exception(
                new Phrase('Unauthorized'),
                401,
                Exception::HTTP_UNAUTHORIZED
            );
        }
        $response = $this->post('https://punchout.cloud/authorize', ["authorization" => $token]);
        if ($response["authorized"] !== true) {
            throw new Exception(
                new Phrase('Unauthorized'),
                401,
                Exception::HTTP_UNAUTHORIZED
            );
        }
    }

    /**
     * Executes the controller action based on the provided request parameters.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $response = null;
            $path = $this->getRequest()->getParam('path');
            switch ($path) {
                case 'script':
                    $punchoutId = $this->session->getPunchoutId();
                    if (empty($punchout_id)) {
                        $response = "";
                    } else {
                        $this->client->get('https://punchout.cloud/punchout.js?id=' . $punchoutId);
                        $response = $this->client->getBody();

                    }
                    $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                    return $result
                        ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
                        ->setHeader('Content-Type', 'application/javascript;charset=UTF-8')
                        ->setContents($response);
                case 'cart':
                    $punchoutId = $this->session->getPunchoutId();
                    if (isset($punchoutId)) {
                        $cart = $this->getCart();
                        $data = [
                            'cart' => [
                                'Magento2' => $cart,
                            ]
                        ];
                        $response = $this->post('https://punchout.cloud/cart/' . $punchoutId, $data);
                        $this->session->logout();
                    } else {
                        $response = ['message' => "You're not in a punchout session"];
                    }
                    break;
                case 'order.json':
                    $this->checkAuthorization();
                    $body = $this->getRequest()->getContent();
                    $response = $this->createOrder(json_decode($body, true));
                    break;
                case 'options.json':
                    $this->checkAuthorization();
                    $response = $this->getOptions();
                    break;
            }

            if ($response != null) {
                // return json response
                $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
                return $result
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
                    ->setHeader('Content-Type', 'application/json;charset=UTF-8')
                    ->setContents(json_encode($response, JSON_PRETTY_PRINT));
            }

            $data = [
                'body' => $this->request->getContent(),
                'query' => $this->request->getParams(),
            ];

            $res = $this->post('https://punchout.cloud/proxy', $data);

            if (!is_array($res) || !isset($res['action'])) {
                $result = $this->resultJsonFactory->create();
                return $result->setHttpResponseCode(400)->setData([
                    'error' => true,
                    'message' => 'Please use a valid punchout URL.',
                    'debug' => $res
                ]);
            }

            switch ($res['action']) {
                case 'print':
                    $xml = new \SimpleXMLElement($res['body']);
                    $result = $this->resultRawFactory->create();
                    $result->setHeader('Content-Type', 'application/xml', true);
                    $result->setContents($xml->asXML());
                    return $result;
                case 'login':
                    if ($this->session->isLoggedIn()) {
                        $lastCustomerId = $this->session->getId();
                        $this->session->logout()->setLastCustomerId($lastCustomerId);
                    }

                    // use customer data object to trigger login event
                    $this->prepareCustomer($res);

                    // use customer object to login
                    $websiteId = $this->storeManager->getStore()->getWebsiteId();

                    $customer = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($res['email']);

                    // login magento 2 customer

                    $this->session->regenerateId();
                    $this->session->setCustomer($customer);
                    $this->session->regenerateId();

                    $this->clearCart();

                    // Add punchout session ID to customer session
                    $this->session->setPunchoutId($res['punchout_id']);

                    // return html response
                    $result = $this->resultRawFactory->create();
                    $result->setHeader('Content-Type', 'text/html', true);
                    $url = $res['redirect'] ?? '/';
                    $result->setContents(
                        "<html><head><title>Redirecting...</title></head><body><script>window.location.href = '"
                        . $url .
                        "';</script></body></html>"
                    );
                    return $result;

                default:
                    $result = $this->resultJsonFactory->create();
                    return $result->setHttpResponseCode(400)->setData([
                        'error' => true,
                        'message' => 'Unknown action ' . $res['action']
                    ]);
            }
        } catch (\Throwable $e) {
            $result = $this->resultJsonFactory->create();
            return $result->setHttpResponseCode($e->getCode() == 401 ? 401 : 400)->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Creates an order based on the provided order data.
     *
     * @param array $orderData The data required to create the order, including customer, items, and addresses.
     * @return array An array containing the created order ID or an error message.
     */
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
        $cart->setCustomerIsGuest(false);

        //add items in quote
        foreach ($orderData['items'] as $item) {
            $product = null;
            if (isset($item['product'])) {
                $product = $this->productFactory->create()->load($item['product']);
            } elseif (isset($item['sku'])) {
                $product = $this->productRepository->get($item['sku']);
                if (!isset($product)) {
                    return ['error' => 'Coudnt find product with sku ' . $item['sku']];
                }
            } else {
                return ['error' => 'Required field product or sku'];
            }
            $options = $this->objectFactory->create($item);
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

        // Collect Rates, Set Shipping & Payment Methoda
        $this->shippingRate
            ->setCode($orderData['shipping_method'])
            ->getPrice();

        $shippingAddress = $cart->getShippingAddress();

        //@todo set in order data
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($orderData['shipping_method']); // 'flatrate_flatrate'); //shipping method
        $cart->getShippingAddress()->addShippingRate($this->shippingRate);

        $cart->setPaymentMethod($orderData['payment_method']); //'checkmo'); //payment method
        if (isset($orderData['po'])) {
            $cart->setPoNumber($orderData['po']);
        }

        //@todo insert a variable to affect the invetory
        $cart->setInventoryProcessed(false);

        // Set sales order payment
        $paymentData = ['method' => $orderData['payment_method']];
        if (isset($orderData['po'])) {
            $paymentData['po_number'] = $orderData['po'];
        }
        $cart->getPayment()->importData($paymentData);

        if (isset($orderData['data'])) {
            foreach ($orderData['data'] as $key => $value) {
                $cart->setData($key, $value);
            }
        }

        // Collect total and save
        $cart->collectTotals();

        // Submit the quote and create the order
        $this->cartRepository->save($cart);
        $cart = $this->cartRepository->get($cart->getId());
        $order_id = $this->cartManagement->placeOrder($cart->getId());
        return ['id' => $order_id];
    }

    /**
     * Sends a POST request to the specified URL with the provided data.
     *
     * @param string $url The URL to send the POST request to.
     * @param array $data The data to include in the POST request body.
     * @return array The decoded JSON response from the server.
     */
    private function post($url, $data)
    {
        $this->client->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
        $this->client->post($url, json_encode($data));
        $res = $this->client->getBody();
        return json_decode($res, true);
    }
}
