<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $auth): JsonResponse
    {
        $user = $auth->register(RegisterData::fromRequest($request));
        $token = $user->createToken('api')->plainTextToken;

        return (new UserResource($user->load('role')))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function login(LoginRequest $request, AuthService $auth): UserResource
    {
        $user = $auth->login(LoginData::fromRequest($request));
        $token = $user->createToken('api')->plainTextToken;

        return (new UserResource($user->load('role')))
            ->additional(['token' => $token]);
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $auth->logout($user);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user->load('role'));
    }
}
