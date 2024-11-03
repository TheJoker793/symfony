<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskListRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/tasks", name="task_api")
 */
class TaskController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TaskRepository $taskRepository;
    private TaskListRepository $taskListRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        TaskListRepository $taskListRepository
    ) {
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
        $this->taskListRepository = $taskListRepository;
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        $tasks = $this->taskRepository->findAll();
        $data = array_map([$this, 'transformTask'], $tasks);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->transformTask($task), JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/", name="create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['description']) || empty($data['deadline']) || !isset($data['completed'])) {
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $task = new Task();
        $task->setTitle($data['title'])
             ->setDescription($data['description'])
             ->setDeadline(new \DateTime($data['deadline']))
             ->setCompleted($data['completed']);

        if (!empty($data['taskListId'])) {
            $taskList = $this->taskListRepository->find($data['taskListId']);
            if ($taskList) {
                $task->setTaskList($taskList);
            }
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();
        $response = [
            'success' => 'Task ajouté avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'deadline' => $task->getDeadline()->format('Y-m-d'),
                'completed' => $task->isCompleted(),
                'taskListTopic' => $task->getTaskList() ? $task->getTaskList()->getTopic() : null,
            ]
        ];
    
        return new JsonResponse($response, JsonResponse::HTTP_CREATED);
    

    }

    /**
     * @Route("/{id}", name="update", methods={"PUT"})
     */
    public function update(int $id, Request $request): JsonResponse
{
    $task = $this->taskRepository->find($id);

    if (!$task) {
        return new JsonResponse(['error' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);

    // Mise à jour des attributs de la tâche
    if (!empty($data['title'])) {
        $task->setTitle($data['title']);
    }
    if (!empty($data['description'])) {
        $task->setDescription($data['description']);
    }
    if (!empty($data['deadline'])) {
        $task->setDeadline(new \DateTime($data['deadline']));
    }
    if (isset($data['completed'])) {
        $task->setCompleted($data['completed']);
    }

    // Assigner le TaskList en fonction de taskListId
    if (isset($data['taskListId'])) {
        $taskList = $this->taskListRepository->find($data['taskListId']);
        if ($taskList) {
            $task->setTaskList($taskList);
        } else {
            return new JsonResponse(['error' => 'Task list not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    $this->entityManager->persist($task);
    $this->entityManager->flush();

    // Préparation des données de réponse
    $response = [
        'success' => 'Task mise à jour avec succès',
        'task' => [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'deadline' => $task->getDeadline()->format('Y-m-d'),
            'completed' => $task->isCompleted(),
            'taskListTopic' => $task->getTaskList() ? $task->getTaskList()->getTopic() : null,
        ]
    ];

    return new JsonResponse($response, JsonResponse::HTTP_OK);
}


    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     */
    public function delete(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return new JsonResponse(['success'=> 'cette task est supprmée avec succes'], JsonResponse::HTTP_OK);
    }

    private function transformTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'deadline' => $task->getDeadline()->format('Y-m-d'),
            'completed' => $task->isCompleted(),
            'taskListId' => $task->getTaskList() ? $task->getTaskList()->getId() : null,
            'taskListTopic' => $task->getTaskList() ? $task->getTaskList()->getTopic() : null,
        ];
    }
}
