<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Struct\DatabaseExtend;

class AuthService
{
    public function check_user_exists_by_email(string $email, DatabaseExtend $database): bool
    {
        return $database->from("user")->where("user_email", $email)->exists();
    }

    public function check_user_exists_by_name(string $username, DatabaseExtend $database): bool
    {
        return $database->from("user")->where("user_name", $username)->exists();
    }

    public function generate_token(int $user_id, DatabaseExtend $database): string
    {
        $database = $database->clone();
        $key = uniqid().rand(1000, 9999).time().$user_id;
        $token = md5($key);
        $database->table("user")->where("user_id", $user_id)->lockForUpdate()->toSql();
        $database->table("user")->where("user_id", $user_id)->lockForUpdate()->update(["token"=>$token]);
        return $token;
    }

    public function check_token_valid(string $token, DatabaseExtend $database): bool
    {
        return $database->table("user")->where("token", $token)->exists();
    }
}
