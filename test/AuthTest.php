<?php namespace Dtkahl\AuthTest;

use Dtkahl\Auth\Auth;
use Dtkahl\Auth\Driver\CustomAuthDriver;

class AuthTest extends \PHPUnit_Framework_TestCase
{

  private $session_token;

  /**
   * @var TestUser[]
   */
  private $users = [];

  public function test()
  {
    $app_salt = Auth::random_salt();

    $auth = new Auth(CustomAuthDriver::class, [
        "handleLogin" => function (Auth $auth, $email, $hash) {
          if ($email == "test@test.com" && $hash == Auth::hash("test1234", $auth->getAppSalt())) {
            return $this->users[] = new TestUser();
          }
          return null;
        },
        "storeSession" => function (Auth $auth, $session_token, $remember_token, $remember) {
          $this->session_token = $session_token;
          $auth->getUser()->setRememberToken($remember_token);
          return true;
        },
        "retrieveSessionToken" => function (Auth $auth) {
          return $this->session_token;
        },
        "retrieveUser" => function (Auth $auth, $remember_token) {
          foreach ($this->users as $user) {
            if ($user->getRememberToken() == $remember_token) {
              return $user;
            }
          }
          return null;
        },
        "destroySession" => function (Auth $auth) {
          $auth->getUser()->setRememberToken(null);
          return true;
        }
    ], $app_salt);

    $this->assertFalse($auth->login("wrong@mail.com", "wrongpw"));
    $this->assertTrue($auth->login("test@test.com", "test1234"));

    $this->assertInstanceOf(TestUser::class, $auth->getUser());
    $this->assertTrue($auth->isAuthenticated());
    $this->assertTrue($auth->validateSession());

    $this->assertTrue($auth->logout());
    $this->assertFalse($auth->logout());
    $this->assertNull($auth->getUser());
    $this->assertFalse($auth->isAuthenticated());
  }

}