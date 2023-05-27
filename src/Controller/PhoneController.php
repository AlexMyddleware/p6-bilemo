<?php

namespace App\Controller;

use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\PhoneRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PhoneController extends AbstractController
{
    #[Route('/phones/test', name: 'app_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new phone controller, this is a test!',
            'path' => 'src/Controller/PhoneController.php',
        ]);
    }

    // Function to return all phones in the database with a JSON response
    #[Route('/api/phones', name: 'app_phones', methods: ['GET'])]
    public function getPhones(Request $request, PhoneRepository $phoneRepository, SerializerInterface $serializer): JsonResponse
    {
        // extract the page number and limit from the request body
        $content = json_decode($request->getContent(), true);
        $page = $content['page'] ?? 1;
        $limit = $content['limit'] ?? 10;

        $phones = $phoneRepository->findAllWithPagination($page, $limit);

        $jsonPhones = $serializer->serialize($phones, 'json');

        return new JsonResponse($jsonPhones, Response::HTTP_OK, [], true);
    }

    // Function to return a specific phone in the database with a JSON response
    #[Route('/api/phones/{id}', name: 'app_phone', methods: ['GET'])]
    public function getPhone(int $id, PhoneRepository $phoneRepository, SerializerInterface $serializer): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if (!$phone) {
            return new JsonResponse(['message' => 'Phone not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonPhone = $serializer->serialize($phone, 'json');

        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
}
