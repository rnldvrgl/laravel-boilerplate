<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->list();

        return ApiResponse::success([
            'users' => UserResource::collection($users),
            'total' => $users->total(),
        ]);
    }
}
