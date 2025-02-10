<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Form\TodoType;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/todo')]
final class TodoController extends AbstractController
{
    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        description: "Retrieves specified Todo using an id",
        responses: [
            new OA\Response(response: 200, description: "Todo found."),
            new OA\Response(response: 404, description: "Not Found.")
        ]
    )]
    public function get(SerializerInterface $serializer, ?Todo $todo): JsonResponse
    {
        if (!$todo) return $this->json(null, 404);

        $jsonContent = $serializer->serialize($todo, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    #[Route('s', methods: ['GET'])]
    #[OA\Get(description: "Retrieve a collection of Todos")]
    #[OA\Response(response: 200, description: "Todos retrieved")]
    public function list(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $todos = $entityManager->getRepository(Todo::class)
            ->findAll();

        $jsonContent = $serializer->serialize($todos, 'json');

        return JsonResponse::fromJsonString($jsonContent);
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        description: "Creates a new Todo",
        responses: [
            new OA\Response(response: 200, description: "Todo created."),
            new OA\Response(response: 400, description: "Bad Request.")
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            example: [
                "title" => "Title",
                "description" => "Description",
                "finished" => false
            ]
        )
    )]
    public function store(EntityManagerInterface $entityManager, ValidatorInterface $validator, Request $request): JsonResponse
    {
        $input = json_decode($request->getContent(), true);

        $form = $this->createForm(TodoType::class);
        $form->submit($input);

        if (!$form->isValid() && count($form->getErrors()) > 0) {
            return $this->json([
                'error' => $form->getErrors()
                    ->current()
                    ->getMessage()
            ], 400);
        }

        $todo = $form->getData();
        $validationErrors = $validator->validate($todo);
        if (count($validationErrors) > 0) {
            $errors = [];
            foreach ($validationErrors as $error)
                $errors[$error->getPropertyPath()] = $error->getMessage();

            return $this->json(compact('errors'), 400);
        }

        $entityManager->persist($todo);
        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully created.',
            'todo' => $todo
        ]);
    }

    #[Route('/update/{id}', methods: ['PUT', 'PATCH'])]
    #[OA\Put(description: "Updates specified Todo")]
    #[OA\Patch(description: "Updates specified Todo")]
    #[OA\Response(response: 200, description: "Todo updated.")]
    #[OA\Response(response: 400, description: "Bad Request.")]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            example: [
                "title" => "Title",
                "description" => "Updated Description",
                "finished" => true
            ]
        )
    )]
    public function update(
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,
        Request                $request,
        ?Todo                  $todo
    ): JsonResponse
    {
        if (!$todo) return $this->json(['error' => "Todo not found"], 404);

        $input = json_decode($request->getContent());
        $todo->setTitle($input->title ?? $todo->getTitle());
        $todo->setDescription($input->description ?? null);
        $todo->setFinished($input->finished ?? $todo->isFinished());

        $validationErrors = $validator->validate($todo);
        if (count($validationErrors) > 0) {
            $errors = [];
            foreach ($validationErrors as $error)
                $errors[$error->getPropertyPath()] = $error->getMessage();

            return $this->json(compact('errors'), 400);
        }

        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully updated.',
            'todo' => $todo
        ]);
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        description: "Deletes specified Todo",
        responses: [
            new OA\Response(response: 200, description: "Todo deleted."),
            new OA\Response(response: 404, description: "Not Found.")
        ]
    )]
    public function destroy(EntityManagerInterface $entityManager, ?Todo $todo): JsonResponse
    {
        if (!$todo) return $this->json(['error' => "Todo not found"], 404);

        $entityManager->remove($todo);
        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully deleted.'
        ]);
    }
}
