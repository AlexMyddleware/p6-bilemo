<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use App\Service\VersioningService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Customer;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class CustomerController extends AbstractController
{
    private $logger;
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, LoggerInterface $logger)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->logger = $logger;
    }

    #[Route('/customers/test', name: 'app_customers_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new customer controller, this is a test!',
            'path' => 'src/Controller/CustomerController.php',
        ]);
    }

    /**
    * Function to get all the customers
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of customers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomers"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number of the list of customers",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The limit of customers per page",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Customers")
     *
     * @param CustomerRepository $customerRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/customers', name: 'app_customers', methods: ['GET'])]
    public function getCustomers(Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, VersioningService $versioningService): JsonResponse
    {
        try {
            // Check if the current user has admin privileges
            if (!$this->isGranted('ROLE_USER')) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not a client!'], Response::HTTP_FORBIDDEN);
            }

            // if the user is an admin, then set the $isAdmin to true
            $isAdmin = false;
            if ($this->isGranted('ROLE_ADMIN')) {
                $isAdmin = true;
            }

            $version = $versioningService->getVersion();

            $context = SerializationContext::create()->setGroups(['getCustomers']);

            $context->setVersion($version);

            // extract the page number and limit from url parameters
            $page = $request->query->get('page', 1);
            $limit = $request->query->get('limit', 10);

            $idCache = "getAllCustomers_{$page}_{$limit}";

            // get the current logged in user
            $user = $this->getUser()->getId();

            $customerList = $cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $page, $limit, $user, $isAdmin) {
                $this->logger->info("Cache miss for the customer list with page {$page} and limit {$limit}");
                $item->tag('customers');
                if ($isAdmin) {
                    return $customerRepository->findAllWithPagination($page, $limit);
                }
                return $customerRepository->findAllWithPaginationForCurrentClient($page, $limit, $user);
            });

            if (empty($customerList)) {
                return new JsonResponse(['message' => 'No customers found.'], Response::HTTP_NOT_FOUND);
            }

            $jsonCustomers = $serializer->serialize($customerList, 'json', $context);

            return new JsonResponse($jsonCustomers, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            // Handle the exception
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/customers/{id}",
     *     summary="Retrieve a specific customer",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The id of the customer to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns the customer data",
     *         @OA\JsonContent(ref="#/components/schemas/Customer")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized, not a client or customer does not belong to the client"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found, either it is not yours or it was deleted"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while retrieving the customer"
     *     ),
     *     security={{"bearerAuth":{}}},
     *   tags={"Customers"}
     * )
     */
    #[Route('/api/customers/{id}', name: 'app_customers_id', methods: ['GET'])]
    public function getCustomer(Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, $id, TagAwareCacheInterface $cache, VersioningService $versioningService): JsonResponse
    {
        try {
            // Check if the current user has admin privileges
            if (!$this->isGranted('ROLE_USER')) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not a client!'], Response::HTTP_FORBIDDEN);
            }

            $version = $versioningService->getVersion();

            $context = SerializationContext::create()->setGroups(['getCustomer']);

            $context->setVersion($version);

            // get the current logged in user
            // The potential intellephense error is not an error, it is a bug in the intellephense extension that falsely interpret the user as the UserInterface but it is the User entity which indeed has the getId() method
            $user = $this->getUser()->getId();

            $idCache = "getCustomer_{$id}";

            $customer = $cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $id, $user) {
                $this->logger->info("Cache miss for the customer with id {$id}");
                $item->tag('customer');
                return $customerRepository->findOneByIdForCurrentClient($id, $user);
            });

            // if $customer is null, return a 403 forbidden response
            if ($customer === null) {
                // return a not found response with a message that the customer was not found or deleted
                return new JsonResponse(['message' => 'Customer not found, either it is not yours or it was deleted'], Response::HTTP_NOT_FOUND);
            }

            $jsonCustomer = $serializer->serialize($customer, 'json', $context);

            return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            // Handle the exception
            return new JsonResponse(['message' => 'An error occurred while retrieving the customer'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/customers",
     *     summary="Create a new customer",
     *     @OA\RequestBody(
     *         description="Data for the new customer",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Returns the created customer data",
     *         @OA\JsonContent(ref="#/components/schemas/Customer")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, email and password are required"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized, you are not a client"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while creating the customer"
     *     ),
     *     security={{"bearerAuth":{}}},
     *     tags={"Customers"}
     * )
     */
    #[Route('/api/customers', name: 'app_customers_create', methods: ['POST'])]
    public function createCustomer(Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            // Check if the current user has admin privileges
            if (!$this->isGranted('ROLE_USER')) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not a client!'], Response::HTTP_FORBIDDEN);
            }

            $context = SerializationContext::create()->setGroups(['getCustomer']);

            // get the current logged in user
            // The potential intellephense error is not an error, it is a bug in the intellephense extension that falsely interpret the user as the UserInterface but it is the User entity which indeed has the getId() method
            // For the creation, we use the user, not the id, because the user entity is used in the function createCustomer() in the CustomerRepository
            // Intellephense is still not happy so we use an annotation to tell it that the user is an entity
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // get the data from the request
            $data = json_decode($request->getContent(), true);

            // check if email and password are provided
            if (empty($data['email']) || empty($data['password'])) {
                return new JsonResponse(['message' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
            }

            $email = $data['email'];
            $password = $data['password'];

            $hashedPassword = $this->userPasswordHasher->hashPassword($user, $password);

            // create a new customer
            $customer = $customerRepository->createCustomer($email, $hashedPassword, $user);

            $jsonCustomer = $serializer->serialize($customer, 'json', $context);

            $cache->invalidateTags(["customer", "customers"]);

            return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            // Handle the exception
            return new JsonResponse(['message' => 'An error occurred while creating the customer'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Function to update a customer, only accessible by a logged in client
    #[Route('/api/customers/{id}', name: 'app_customers_update', methods: ['PUT'])]
    public function updateCustomer(Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, $id, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            // Check if the current user has admin privileges
            if (!$this->isGranted('ROLE_USER')) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not a client!'], Response::HTTP_FORBIDDEN);
            }

            $context = SerializationContext::create()->setGroups(['getCustomer']);

            // get the current logged in user
            // The potential intellephense error is not an error, it is a bug in the intellephense extension that falsely interpret the user as the UserInterface but it is the User entity which indeed has the getId() method
            // For the creation, we use the user, not the id, because the user entity is used in the function createCustomer() in the CustomerRepository
            // Intellephense is still not happy so we use an annotation to tell it that the user is an entity
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // use the function findOneByIdForCurrentClient() to get the customer for the current user
            $customer = $customerRepository->findOneByIdForCurrentClient($id, $user);

            // if $customer is null, return a 403 forbidden response
            if ($customer === null) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not the owner of this customer!'], Response::HTTP_FORBIDDEN);
            }

            // get the data from the request
            $data = json_decode($request->getContent(), true);

            // check if email and password are provided
            if (empty($data['email']) || empty($data['password'])) {
                return new JsonResponse(['message' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
            }

            $hashedPassword = $this->userPasswordHasher->hashPassword($user, $data['password']);

            // update the customer with the new data
            $customer->setEmail($data['email']);
            $customer->setPassword($hashedPassword);

            // save the updated customer to the database
            $customerRepository->save($customer, true);

            $jsonCustomer = $serializer->serialize($customer, 'json', $context);

            $cache->invalidateTags(["customer", "customers"]);

            return new JsonResponse($jsonCustomer, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            // Handle the exception
            return new JsonResponse(['message' => 'An error occurred while updating the customer'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Function to delete a customer, only accessible by a logged in client
    #[Route('/api/customers/{id}', name: 'app_customers_delete', methods: ['DELETE'])]
    public function deleteCustomer(Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, $id, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            // Check if the current user has admin privileges
            if (!$this->isGranted('ROLE_USER')) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not a client!'], Response::HTTP_FORBIDDEN);
            }

            // get the current logged in user
            // The potential intellephense error is not an error, it is a bug in the intellephense extension that falsely interpret the user as the UserInterface but it is the User entity which indeed has the getId() method
            // For the creation, we use the user, not the id, because the user entity is used in the function createCustomer() in the CustomerRepository
            // Intellephense is still not happy so we use an annotation to tell it that the user is an entity
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // use the function findOneByIdForCurrentClient() to get the customer for the current user
            $customer = $customerRepository->findOneByIdForCurrentClient($id, $user);

            // if $customer is null, return a 403 forbidden response
            if ($customer === null) {
                return new JsonResponse(['message' => 'Unable to access this page, you are not the owner of this customer!'], Response::HTTP_FORBIDDEN);
            }

            // delete the customer
            $customerRepository->remove($customer, true);

            $cache->invalidateTags(["customer", "customers"]);

            return new JsonResponse(['message' => 'Customer deleted'], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle the exception
            return new JsonResponse(['message' => 'An error occurred while deleting the customer'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
