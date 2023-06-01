<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClientController extends AbstractController
{
    #[Route('/clients/test', name: 'app_clients_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new client controller, this is a test!',
            'path' => 'src/Controller/ClientController.php',
        ]);
    }

    #[Route('/api/clients', name: 'app_clients', methods: ['GET'])]
    public function getClients(Request $request, UserRepository $clientRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        // Check if the current user has admin privileges
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['message' => 'Unable to access this page, you are not an admin!'], Response::HTTP_FORBIDDEN);
        }

        // extract the page number and limit from the request body
        $content = json_decode($request->getContent(), true);
        $page = $content['page'] ?? 1;
        $limit = $content['limit'] ?? 10;

        $cacheKey = "getClients_{$page}_{$limit}";

        $clients = $cache->get($cacheKey, function (ItemInterface $item) use ($clientRepository, $page, $limit) {
            echo ("Cache miss for clients with page {$page} and limit {$limit} \n");
            $item->tag('clients');
            return $clientRepository->findAllWithPagination($page, $limit);
        });

        $jsonClients = $serializer->serialize($clients, 'json', ['groups' => 'getClients', 'json_encode_options' => JSON_PRETTY_PRINT]);

        return new JsonResponse($jsonClients, Response::HTTP_OK, [], true);
    }
}
