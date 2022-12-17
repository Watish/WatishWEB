<?php

namespace Watish\WatishWEB\Controller\Http;

use Cake\Validation\Validator;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\WatishWEB\Service\AuthService;
use Watish\WatishWEB\Service\UserService;

#[Prefix("/auth")]
class AuthController
{
    #[Inject(AuthService::class)]
    private AuthService $authService;

    #[Inject(UserService::class)]
    private UserService $userService;

    #[Path("/login")]
    public function login(Context $context): void
    {
        $request = $context->getRequest();
        $database = $context->Database();

        $validator = new Validator();
        $validator->requirePresence(["user","pwd"], true)
            ->notEmptyString("user")
            ->notEmptyString("pwd");
        $errors = $validator->validate($request->GetAll());
        if (count($errors) > 0) {
            $errors = array_keys($errors);

            $context->json([
                "Ok" => false,
                "Msg" => $errors[0] . " illegal"
            ]);
            $context->abort();
            return;
        }

        $user = $request->get["user"];
        $pwd = $request->get["pwd"];

        if (!$this->authService->check_user_exists_by_email($user, $context->Database())) {
            $context->json([
                "Ok" => false,
                "Msg" => "user not exists"
            ]);
            $context->abort();
            return;
        }
        $user_info = $this->userService->get_user_info_by_email($user, $database);
        $user_pwd = $user_info["user_password"];
        $user_id = $user_info["user_id"];
        if (md5($pwd) !== $user_pwd) {
            $context->json([
                "Ok" => false,
                "Msg" => "password incorrect"
            ]);
            $context->abort();
            return;
        }

        $token = $this->authService->generate_token($user_id, $database);

        $context->json([
            "Ok" => true,
            "Token" => $token,
        ]);
    }

    #[Path("/register")]
    public function register(Context $context): void
    {
        $database = $context->Database();
        $request = $context->getRequest();
        $response = $context->getResponse();

        $validator = new Validator();
        $validator->requirePresence(["email","username","password"], true)
            ->email("email")
            ->notEmptyString("username")
            ->lengthBetween("username", [6,16])
            ->lengthBetween("password", [6,32]);
        $errors = $validator->validate($request->GetAll());
        $errors = array_keys($errors);
        //Validate Fields
        if (!empty($errors)) {
            $context->json([
                "Ok" => false,
                "Msg" => $errors[0] . " illegal"
            ]);
            $context->abort();
            return;
        }

        //Check User Exists
        if ($this->authService->check_user_exists_by_email($request->get["email"], $database)) {
            $context->json([
                "Ok" => false,
                "Msg" => "email already exists"
            ]);
            $context->abort();
            return;
        }
        if ($this->authService->check_user_exists_by_name($request->get["username"], $database)) {
            $context->json([
                "Ok" => false,
                "Msg" => "username already exists"
            ]);
            $context->abort();
            return;
        }

        $user_id = $database->insert("user", [
            "user_name" => $request->get["username"],
            "user_email" => $request->get["email"],
            "user_password" => md5($request->get["password"])
        ])->lastInsertId();

        if (!$user_id) {
            $context->json([
                "Ok" => false,
                "Msg" => "register failed"
            ]);
            $context->abort();
            return;
        }

        $token = $this->authService->generate_token((int)$user_id, $database);
        $context->json([
            "Ok" => true,
            "Token" => $token
        ]);
    }

    #[Path("/check_token")]
    public function check_token(Context $context): void
    {
        $request = $context->getRequest();
        $params = $request->GetAll();
        $validator = new Validator();
        $validator->requirePresence("token", true)
            ->notEmptyString("token")
            ->lengthBetween("token", [32,32]);
        $errors = $validator->validate($params);
        if ($errors) {
            $context->json([
                "Ok" => false,
                "Msg" => "Token Invalid"
            ]);
            $context->abort();
            return;
        }
        $token = $params["token"];
        $status = $this->authService->check_token_valid($token, $context->Database());
        $context->json([
            "Ok" => $status,
        ]);
    }
}
