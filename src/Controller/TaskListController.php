<?php

namespace App\Controller;

use App\Entity\TaskList;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TaskListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/taskList", name="taskList_api")
 */
class TaskListController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TaskListRepository $taskListRepository;

    public function __construct(EntityManagerInterface $entityManager, TaskListRepository $taskListRepository)
    {
        $this->entityManager = $entityManager;
        $this->taskListRepository = $taskListRepository;
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): JsonResponse
{
    $taskLists = $this->taskListRepository->findAll();
    $data = [];

    foreach ($taskLists as $taskList) {
        $data[] = $this->transformTaskList($taskList);
    }

    return new JsonResponse($data, JsonResponse::HTTP_OK);
}


    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(int $id): JsonResponse
    {
        $taskList = $this->taskListRepository->find($id);

        return $taskList ? 
            new JsonResponse($this->transformTaskList($taskList), JsonResponse::HTTP_OK) : 
            new JsonResponse(['error' => 'Task list not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * @Route("/", name="create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['topic'])) {
            return new JsonResponse(['error' => 'Topic is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $taskList = new TaskList();
        $taskList->setTopic($data['topic']);

        $this->entityManager->persist($taskList);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $taskList->getId()], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="update", methods={"PUT"})
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $taskList = $this->taskListRepository->find($id);

        if (!$taskList) {
            return new JsonResponse(['error' => 'Task list not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['topic'])) {
            return new JsonResponse(['error' => 'Topic is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $taskList->setTopic($data['topic']);
        $this->entityManager->flush();

        return new JsonResponse($this->transformTaskList($taskList), JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     */
    public function delete(TaskList $taskList): Response
    {
        $this->entityManager->remove($taskList);
        $this->entityManager->flush();

        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Helper function to transform a TaskList entity into an array.
     */
    private function transformTaskList(TaskList $taskList): array
    {
        return [
            'id' => $taskList->getId(),
            'topic' => $taskList->getTopic(),
        ];
    }
}
