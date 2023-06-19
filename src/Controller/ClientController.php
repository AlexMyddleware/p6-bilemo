<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Psr\Log\LoggerInterface;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

use App\Entity\User;


class ClientController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/clients/test', name: 'app_clients_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new client controller, this is a test!',
            'path' => 'src/Controller/ClientController.php',
        ]);
    }

    /**
    * Function to get all the clients
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getClients"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number of the list of clients",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The limit of clients per page",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Clients")
     *
     * @param User $clientRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/clients', name: 'app_clients', methods: ['GET'])]
    public function getClients(
        Request $request,
        UserRepository $clientRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Check if the current user has admin privileges
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['message' => 'Unable to access this page, you are not an admin!'], Response::HTTP_FORBIDDEN);
        }

        $context = SerializationContext::create()->setGroups(['getClients']);

        // extract the page number and limit from url parameters
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        if ($page <= 0 || $limit <= 0) {
            return new JsonResponse(['message' => 'Page number and limit should be greater than 0'], Response::HTTP_BAD_REQUEST);
        }

        $idCache = "getAllClients_{$page}_{$limit}";

        try {
            $clientList = $cache->get($idCache, function (ItemInterface $item) use ($clientRepository, $page, $limit) {
                $this->logger->info("Cache miss for the client list with page {$page} and limit {$limit}");
                $item->tag('clients');
                return $clientRepository->findAllWithPagination($page, $limit);
            });

            // Check if the client list is empty
            if (empty($clientList)) {
                return new JsonResponse(['message' => 'No clients found.'], Response::HTTP_NO_CONTENT);
            }

            $jsonClientList = $serializer->serialize($clientList, 'json', $context);

            return new JsonResponse($jsonClientList, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            $this->logger->error("Error occurred while getting clients: {$e->getMessage()}");
            return new JsonResponse(['message' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/clients/{id}",
     *     summary="Retrieve a specific client",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The id of the client to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns the client data",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized, not a client or client does not belong to the client"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found, either it is not yours or it was deleted"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while retrieving the client"
     *     ),
     *     security={{"bearerAuth":{}}},
     *   tags={"Clients"}
     * )
     */
    #[Route('/api/clients/{id}', name: 'app_client', methods: ['GET'])]
    public function getClient(
        int $id,
        UserRepository $clientRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Check if the current user has admin privileges
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['message' => 'Unable to access this page, you are not an admin!'], Response::HTTP_FORBIDDEN);
        }

        // Create the serialization context
        $context = SerializationContext::create()->setGroups(['getClient']);

        $idCache = "getClient_{$id}";

        try {
            $client = $cache->get($idCache, function (ItemInterface $item) use ($clientRepository, $id) {
                $this->logger->info("Cache miss for the client with id {$id}");
                $item->tag('client');
                return $clientRepository->find($id);
            });

            if (!$client) {
                return new JsonResponse(['message' => 'Client not found'], Response::HTTP_NOT_FOUND);
            }

            $jsonClient = $serializer->serialize($client, 'json', $context);

            return new JsonResponse($jsonClient, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            $this->logger->error("Error occurred while getting client: {$e->getMessage()}");
            return new JsonResponse(['message' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
