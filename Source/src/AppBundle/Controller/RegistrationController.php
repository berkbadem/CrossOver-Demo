<?php

/*
 * This file is part of the Crossover Demo package.
 *
 * (c) Berk BADEM <berkbadem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Form\RegisterType;
use AppBundle\Entity\User;
use AppBundle\Form\ValidationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller used to manage registration user in the public part of the site.
 *
 * @author Berk BADEM <berkbadem@gmail.com>
 */
class RegistrationController extends Controller
{
    /**
     * Register route function, it generates registration form and displays at registration/register.html.twig then saves user when submit
     *
     * @param Request $request
     * @return mixed
     *
     * @Route("/register", name="user_registration")
     */
    public function registerAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('news_list'));
        }

        $user = new User();
        $user->generateValidationKey();

        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $mail = $this->container->get("app.mailer");
            $mail->setAddress($user->getEmail());
            $mail->setSubject("Crossover Validation Email");
            $mail->setContent(
                $this->renderView(
                    "emails/validation.html.twig",
                    array(
                        "username" => $user->getUsername(),
                        "validationUrl" => $this->generateUrl(
                            "user_validation",
                            array("id" => $user->getValidationKey()),
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    )
                )
            );

            if ($mail->send()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute("email_sent");
            } else {
                return $this->redirectToRoute("email_error");
            }

        }

        return $this->render(
            "registration/register.html.twig",
            array("form" => $form->createView())
        );
    }


    /**
     * Validate route function, it generates validation form and displays atregistration/validation.html.twig then activates and saves user when submit
     *
     * @param User $id
     * @param Request $request
     * @return mixed
     *
     * @Route("/validate/{id}", name="user_validation")
     */
    public function validateAction($id, Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('news_list'));
        }

        if (!preg_match("/^[a-f0-9]{32}$/", $id)) {
            return $this->redirectToRoute("validation_md5_error");
        }

        $user = $this->getDoctrine()
            ->getRepository("AppBundle:User")
            ->findOneBy(array("validationKey" => $id));

        if (!$user) {
            return $this->redirectToRoute("validation_user_error");
        }

        $form = $this->createForm(ValidationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordEncoder = $this->get("security.password_encoder");
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setIsActive(true);

            $user->deleteValidationKey();
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute("user_complete");
        }

        return $this->render(
            "registration/validation.html.twig",
            array("form" => $form->createView())
        );
    }

    /**
     * Validation complete route function, it generates registration complete message on registration/success.html.twig
     *
     * @return Response
     *
     * @Route("/complete", name="user_complete")
     */
    public function validationCompleteAction()
    {
        return $this->render(
            "registration/success.html.twig",
            array(
                "header" => "Registration Complete",
                "content" => "Your registration process has been completed.",
            )
        );
    }


    /**
     * Validation MD5 error route function, it generates validation code wrong message on errors/error.html.twig
     *
     * @return Response
     *
     * @Route("/error/validation/code", name="validation_md5_error")
     */
    public function validationMD5ErrorAction()
    {
        return $this->render(
            "errors/error.html.twig",
            array(
                "error_header" => "Validation Code Wrong",
                "error_content" => "There is a problem in validation code, registration failed.",
            )
        );

    }

    /**
     * Validation user error route function, it generates user not found message on errors/error.html.twig
     *
     * @return Response
     *
     * @Route("/error/validation/user", name="validation_user_error")
     */
    public function validationUserErrorAction()
    {
        return $this->render(
            "errors/error.html.twig",
            array(
                "error_header" => "User Not Found",
                "error_content" => "There is a problem in validation code, user not found.",
            )
        );

    }

    /**
     * Validation email sent route function, it generates validation email sent message on registration/success.html.twig
     *
     * @return Response
     *
     * @Route("/sent", name="email_sent")
     */
    public function sentAction()
    {
        return $this->render(
            "registration/success.html.twig",
            array(
                "header" => "Validation Email Sent",
                "content" => "You need to check your email to next step of registration process.",
            )
        );
    }

    /**
     * Validation email sent error route function, it generates validation email cant send message on errors/error.html.twig
     *
     * @return Response
     *
     * @Route("/error/sent", name="email_error")
     */
    public function sentErrorAction()
    {
        return $this->render(
            "errors/error.html.twig",
            array(
                "error_header" => "Validation Email Can Not Send",
                "error_content" => "There is a problem in our services, registration failed.",
            )
        );
    }

}