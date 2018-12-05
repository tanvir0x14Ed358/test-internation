<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\UserGroups;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/api")
 */
class UserGroupsController extends AbstractController {

    /**
     * @Route("/groups", methods={"POST"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function createGroup() {
        $request = Request::createFromGlobals();

        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $response = new Response('Page not found.', Response::HTTP_NOT_FOUND);
            return $response;
        }

        $data = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();

        $group = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->findOneBy(["name" => $data->name]);

        if (!$group) {
            $group = new UserGroups();
        }

        $group = new UserGroups();

        $group->setName($data->name);
        $entityManager->persist($group);
        $entityManager->flush();

        $serialized = $this->serializeObject($group);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/groups/{id}", methods={"GET"})
     */
    public function fetchGroup($id) {

        $group = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->find($id);

        $serialized = $this->serializeObject($group);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/groups", methods={"GET"})
     */
    public function fetchAllGroups() {
        $groups = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->findAll();

        $groupList = array('groups' => $groups);
        $serialized = $this->serializeObject($groupList);

        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/groups/{id}", methods={"PUT"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function updateGroup($id) {
        $request = Request::createFromGlobals();

        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $response = new Response('Page not found.', Response::HTTP_NOT_FOUND);
            return $response;
        }

        $data = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();

        $group = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->find($id);

        $group->setName($data->name);
        $entityManager->persist($group);
        $entityManager->flush();

        $serialized = $this->serializeObject($group);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/groups/{id}", methods={"DELETE"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteGroup($id) {
        $group = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        if ($group) {
            $entityManager->remove($group);
            $entityManager->flush();
        }
        $groups = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->findAll();

        $serialized = $this->serializeObject($groups);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * 
     * @param type $object
     * @return type string
     * 
     * To assist json serialization from object
     */
    private function serializeObject($object) {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer(array($normalizer), array($encoder));
        $normalized = $serializer->normalize($object, null, array('groups' => 'public'));
        $serialized = $serializer->serialize($normalized, 'json');

        return $serialized;
    }

}
