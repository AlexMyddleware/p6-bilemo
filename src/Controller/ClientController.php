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


class ClientController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger) {
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

    // Function to get all the users, which are the real users of the api, only accessible by the admin
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

    // Function to return a specific client in the database with a JSON response
    #[Route('/api/clients/{id}', name: 'app_client', methods: ['GET'])]
    public function getClient(int $id, UserRepository $clientRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {

         // Check if the current user has admin privileges
         if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['message' => 'Unable to access this page, you are not an admin!'], Response::HTTP_FORBIDDEN);
        }

        // Create the serialization context
        $context = SerializationContext::create()->setGroups(['getClient']);

        $idCache = "getClient_{$id}";

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

    }
}
