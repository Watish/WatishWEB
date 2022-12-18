<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Struct\DatabaseExtend;

class UserService
{
    private BaseService $baseService;

    public function __construct()
    {
        $this->baseService = new BaseService();
    }

    public function get_user_info_by_email(string $email, DatabaseExtend $database): array|null
    {
        $resObj = $database->from("user")->where("user_email", $email)->first();
        if (!$resObj) {
            return null;
        }
        return $this->baseService->toArray($resObj);
    }
}
