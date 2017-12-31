<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class LegacyController extends Controller
{

    /**
     * This method is responsible for returning the legacy content
     *
     * @param  Request $request Symfony's request
     * @param  string  $path    The Path where the fetch the file
     *
     * @return Response           Symfony's response
     */
    public function fallbackAction(Request $request, $path)
    {
        $response = new Response();
        $legacyContent = __DIR__ . '/../legacy_app' . $request->getRequestUri();
        if (substr($legacyContent, -1) == '/') {
            $legacyContent .= 'index.php';
        }

        $arguments = strpos($legacyContent, '?');
        if ($arguments != 0) {
            $legacyContent = substr($legacyContent, 0, $arguments);
            if (strpos($legacyContent, 'index.php') == 0) {
                $legacyContent .= 'index.php';
            }
        }

        try {
            return $this->returnResponse($legacyContent, $response, $request);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException("Page not found $e");
        }
    }

    private function returnResponse($legacyContent, $response, $request)
    {
        $file = new File($legacyContent);
        if ($file->getExtension() == 'php') {
            ob_start();

            include $legacyContent;

            $content = ob_get_clean();

            $authenticationHelper = $this->container->get('authentication.helper');

            if (isset($_SESSION['logged'])) {
                $authenticationHelper->authenticateInSymfony($_SESSION['id'], $request);
            } else {
                $authenticationHelper->logout();
            }
        } else {
            $content = file_get_contents($legacyContent);
            if (strpos($legacyContent, '.css')) {
                $response->headers->set('Content-Type', 'text/css');
            } else {
                $response->headers->set('Content-Type', $file->getMimeType());
            }

            $response->setPublic();
            $response->setMaxAge(3600);
            $date = new \DateTime();
            $date->modify('+3600 seconds');
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($content);

        return $response;
    }
}
