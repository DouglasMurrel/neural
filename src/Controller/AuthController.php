<?php


namespace App\Controller;


use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends ApiController
{

    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $password = $request->get('password');
        $email = $request->get('email');

        if (empty($password) || empty($email)){
            return $this->respondValidationError("Invalid Email or Password");
        }


        $user = new User();
        $user->setPassword($encoder->encodePassword($user, $password));
        $user->setEmail($email);
        $em->persist($user);
        $em->flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getUsername()));
    }

    /**
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     * @Route("/api/login_check", name="api_login_check", methods={"POST"})
     */
    public function getTokenUser(UserInterface $user,JWTTokenManagerInterface $JWTManager)
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }

}