<?php

namespace App\Http\Controllers;

use App\Models\MUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class CUsers extends Controller
{
    public function index()
    {
        // Return all users as JSON
        $users = MUsers::all();
        return response()->json($users, 200);
    }

    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:user|max:255',
            'password' => 'required|min:6',
            'level' => 'required|in:owner,sales',
            'nama' => 'required',
        ]);

        // If validation fails, return error messages
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Create new user with mass assignment (password will be automatically hashed by the model)
        $user = MUsers::create($request->all());

        // Return success response
        return response()->json(['success' => 'User created successfully', 'user' => $user], 201);
    }

    public function show($id_user)
    {
        // Find user by ID
        $user = MUsers::find($id_user);

        // If user not found, return error
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Return user data
        return response()->json($user, 200);
    }

    public function update(Request $request, $id_user)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:255',
            'level' => 'required|in:owner,sales',
            'nama' => 'required',
        ]);

        // If validation fails, return error messages
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Find user by ID
        $user = MUsers::find($id_user);

        // If user not found, return error
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update user with mass assignment (password will be automatically hashed if updated)
        $user->update($request->all());

        // Return success response
        return response()->json(['success' => 'User updated successfully', 'user' => $user], 200);
    }

    public function destroy($id_user)
    {
        // Find user by ID
        $user = MUsers::find($id_user);

        // If user not found, return error
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Delete user
        $user->delete();

        // Return success response
        return response()->json(['success' => 'User deleted successfully'], 200);
    }
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = MUsers::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Return id_user as the token
        return response()->json([
            'token' => $user->id_user,
            'message' => 'Login successful'
        ], 200);
    }
}
