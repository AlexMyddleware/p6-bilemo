<?php

namespace App\Controller;

use App\Repository\PhoneRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Psr\Log\LoggerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Entity\Phone;


class PhoneController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    #[Route('/phones/test', name: 'app_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new phone controller, this is a test!',
            'path' => 'src/Controller/PhoneController.php',
        ]);
    }

    /**
    * Function to get all the phones
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns the list of phones",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhones"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page number of the list of phones",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The limit of phones per page",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Phones")
     *
     * @param PhoneRepository $phoneRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/phones', name: 'app_phones', methods: ['GET'])]
    public function getPhones(Request $request, PhoneRepository $phoneRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $context = SerializationContext::create()->setGroups(['getPhones']);

            $page = $request->query->get('page', 1);
            $limit = $request->query->get('limit', 10);

            $idCache = "getAllPhones_{$page}_{$limit}";

            $phoneList = $cache->get($idCache, function (ItemInterface $item) use ($phoneRepository, $page, $limit) {
                $this->logger->info("Cache miss for the phone list with page {$page} and limit {$limit}");
                $item->tag('phones');
                return $phoneRepository->findAllWithPagination($page, $limit);
            });

            if (empty($phoneList)) {
                return new JsonResponse(['error' => 'No phones found'], Response::HTTP_NOT_FOUND);
            }

            $jsonPhoneList = $serializer->serialize($phoneList, 'json', $context);

            return new JsonResponse($jsonPhoneList, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            $this->logger->error("Error in getPhones: {$e->getMessage()}");
            return new JsonResponse(['error' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/phones/{id}",
     *     summary="Retrieve a specific phone",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The id of the phone to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns the phone data",
     *         @OA\JsonContent(ref="#/components/schemas/Phone")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized, not a client or phone does not belong to the client"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Phone not found, either it is not yours or it was deleted"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while retrieving the phone"
     *     ),
     *     security={{"bearerAuth":{}}},
     *   tags={"Phones"}
     * )
     */
    #[Route('/api/phones/{id}', name: 'app_phone', methods: ['GET'])]
    public function getPhone(
        int $id,
        PhoneRepository $phoneRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Create the serialization context
        $context = SerializationContext::create()->setGroups(['getPhone']);

        // Create the cache id
        $idCache = "getPhone_{$id}";

        try {
            $phone = $cache->get($idCache, function (ItemInterface $item) use ($phoneRepository, $id) {
                $this->logger->info("Cache miss for the phone with id {$id}");
                $item->tag('phone');
                return $phoneRepository->find($id);
            });

            if (!$phone) {
                return new JsonResponse(['message' => 'Phone not found'], Response::HTTP_NOT_FOUND);
            }

            $jsonPhone = $serializer->serialize($phone, 'json', $context);

            return new JsonResponse($jsonPhone, Response::HTTP_OK, ['json_encode_options' => JSON_PRETTY_PRINT], true);
        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while retrieving the phone: ' . $e->getMessage();
            return new JsonResponse(['error' => $errorMessage], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
