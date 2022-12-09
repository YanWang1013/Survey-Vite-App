<?php

namespace App\Http\Controllers;


use App\Http\Constants;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\Rules\Password;


class ManagerController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== Constants::$USER_ROLE_ADMIN) {
            return abort(403, 'Unauthorized action.');
        }
        return UserResource::collection(User::where('role', Constants::$USER_ROLE_MANAGER)->orderBy('created_at', 'DESC')->paginate(10));
    }

    public function showGuests(Request $request)
    {
        $user = $request->user();
        if ($user->role < Constants::$USER_ROLE_MANAGER) {
            return abort(403, 'Unauthorized action.');
        }
        return UserResource::collection(User::where('role', Constants::$USER_ROLE_GUEST)->orderBy('created_at', 'DESC')->paginate(10));
    }

    public function store(StoreManagerRequest $request)
    {
        $user = $request->user();
        if ($user->role !== Constants::$USER_ROLE_ADMIN) {
            return abort(403, 'Unauthorized action.');
        }
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        $data['role'] = Constants::$USER_ROLE_MANAGER;
        $manager = User::create($data);
        $token = $manager->createToken('main')->plainTextToken;
        return new UserResource($manager);
    }

    public function show(User $manager, Request $request)
    {
        $user = $request->user();
        if ($user->role !== Constants::$USER_ROLE_ADMIN) {
            return abort(403, 'Unauthorized action.');
        }
        return new UserResource($manager);
    }

    public function update(UpdateManagerRequest $request, User $manager)
    {
        $user = $request->user();
        if ($user->role !== Constants::$USER_ROLE_ADMIN) {
            return abort(403, 'Unauthorized action.');
        }
        $data = $request->validated();
        $data['role'] = Constants::$USER_ROLE_MANAGER;
        $manager->update($data);
        return new UserResource($manager);
    }

    public function destroy(User $manager, Request $request)
    {
        $user = $request->user();
        if ($user->role !== Constants::$USER_ROLE_ADMIN) {
            return abort(403, 'Unauthorized action.');
        }
        $manager->delete();
        return response('', 204);
    }
}
