<?php 

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthenticationHelper
{
    private $securityTokenStorage;
    private $eventDispatcher;
    private $securityAuthorizationChecker;

    public function __construct($securityTokenStorage, $eventDispatcher, $securityAuthorizationChecker)
    {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
    }

    /**
     *
     * This method is responsible for authenticating a user if it isn't authenticated yet
     *
     * @param  int  $userId  The user Id of the user to authenticate
     * @param  Request $request The request
     * @param  string  $level   The level can be either ROLE_USER or ROLE_ADMIN
     */
    public function authenticateInSymfony($userId = null, Request $request, $level = 'ROLE_USER')
    {
        if (!$this->securityAuthorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            if ($userId == null) {
                throw new UsernameNotFoundException('User not found');
            } else {
                $token = new UsernamePasswordToken($userId, null, 'main', [$level]);
                $this->securityTokenStorage->setToken($token);

                //now dispatch the login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->eventDispatcher->dispatch('security.interactive_login', $event);
            }
        }
    }

    /**
     * This method is responsible for logging out the user
     */
    public function logout()
    {
        if ($this->securityAuthorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = new AnonymousToken(null, 'anon.', ['IS_AUTHENTICATED_ANONYMOUSLY']);
            $this->securityTokenStorage->setToken($token);
        }
    }
}
