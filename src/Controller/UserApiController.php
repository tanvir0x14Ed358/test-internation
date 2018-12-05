<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
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
class UserApiController extends AbstractController {

    /**
     * @Route("/users", methods={"POST"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function createUser() {
        $request = Request::createFromGlobals();

        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $response = new Response('Page not found.', Response::HTTP_NOT_FOUND);
            return $response;
        }

        $data = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();

        $user = new User();

        $user->setName($data->name);
        $user->setEmail($data->email);        
        $user->setPassword($data->password);
        $user->setApiToken(uniqid());
        $user->setRoles($data->roles);

        foreach ($data->userGroups as $group) {
            $userGroup = $this->getDoctrine()
                ->getRepository(UserGroups::class)
                ->findOneBy(["name"=>$group->name]);

            if(!$userGroup){
                $userGroup = new UserGroups();
            }
            
            $userGroup->setName($group->name);
            if ($userGroup) {
                $user->addUserGroup($userGroup);
            }
            $entityManager->persist($userGroup);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $serialized = $this->serializeObject($user);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/users/{id}", methods={"GET"})
     */
    public function fetchUser($id) {

        $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($id);

        $userDetail = array('user' => $user);
        $serialized = $this->serializeObject($userDetail);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/users", methods={"GET"})
     * 
     */
    public function fetchAllUsers() {
        $users = $this->getDoctrine()
                ->getRepository(User::class)
                ->findAll();

        $usersList = array('users' => $users);
        $serialized = $this->serializeObject($usersList);


        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/users/{id}", methods={"PUT"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function updateUser($id) {
        $request = Request::createFromGlobals();

        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $response = new Response('Page not found.', Response::HTTP_NOT_FOUND);
            return $response;
        }

        $data = json_decode($request->getContent());
        $entityManager = $this->getDoctrine()->getManager();

        $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($id);

        if (count($data->userGroups)) {
            $existingUserGroups = $user->getUserGroups();
            $existingUserGroups->clear();
            $user = $entityManager->merge($user); // this is crucial
            $entityManager->flush();
        }

        $user->setName($data->name);
        $user->setEmail($data->email);
        $user->setPassword($data->password);
        $user->setRoles($data->roles);

        foreach ($data->userGroups as $group) {
            $userGroup = $this->getDoctrine()
                    ->getRepository(UserGroups::class)
                    ->findOneBy(["name" => $group->name]);
            if ($userGroup) {
                $user->addUserGroup($userGroup);
            }
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $usersDetail = array('users' => $user);
        $serialized = $this->serializeObject($usersDetail);
        $response = new Response($serialized);
        $response->headers->set("Content-Type ", "application/json");

        return $response;
    }

    /**
     * @Route("/users/{id}", methods={"DELETE"})
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteUser($id) {
        $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        if($user){
            $entityManager->remove($user);
            $entityManager->flush();
        }
        $users = $this->getDoctrine()
                ->getRepository(User::class)
                ->findAll();

        $usersList = array('users' => $users);
        $serialized = $this->serializeObject($usersList);
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
