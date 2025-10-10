<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;


class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     *
     * @OA\Get(
     *     path="/api/departments",
     *     tags={"Departments"},
     *     summary="Get list of departments",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Finance"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Handles financial matters"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="users",
     *                         type="array",
     *                         @OA\Items(type="object")
     *                     ),
     *                     @OA\Property(
     *                         property="permissions",
     *                         type="array",
     *                         @OA\Items(type="object")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 5);
        $withRelations = $request->get('with_relations', false);

        $query = Department::query();

        if ($withRelations) {
            $query->with(['users:id,first_name,surname', 'permissions:id,name']);
        }

        $departments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Store a newly created department.
     *
     * @OA\Post(
     *     path="/api/departments",
     *     tags={"Departments"},
     *     summary="Create a new department",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Finance"),
     *             @OA\Property(property="description", type="string", example="Handles financial matters"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="permission_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Department created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Department created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Finance"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Handles financial matters"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create($request->only(['name', 'description', 'is_active']));

        if ($request->has('permission_ids')) {
            $department->permissions()->attach($request->permission_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully',
            'data' => $department->load('permissions')
        ], 201);
    }

    /**
     * Display the specified department.
     *
     * @OA\Get(
     *     path="/api/departments/{department}",
     *     tags={"Departments"},
     *     summary="Get a specific department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Finance"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Handles financial matters"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="users",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $department->load(['users', 'permissions'])
        ]);

    }


    /**
     * Update the specified department.
     *
     * @OA\Put(
     *     path="/api/departments/{department}",
     *     tags={"Departments"},
     *     summary="Update a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Finance"),
     *             @OA\Property(property="description", type="string", example="Handles financial matters"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="permission_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Department updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Finance"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Handles financial matters"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update($request->only(['name', 'description', 'is_active']));

        if ($request->has('permission_ids')) {
            $department->permissions()->sync($request->permission_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
            'data' => $department->load('permissions')
        ]);
    }

    /**
     * Remove the specified department.
     *
     * @OA\Delete(
     *     path="/api/departments/{department}",
     *     tags={"Departments"},
     *     summary="Delete a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Department deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete department with assigned users"
     *     )
     * )
     */
    public function destroy(Department $department): JsonResponse
    {
        // Check if department has users
        if ($department->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department with assigned users'
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Assign user(s) to department.
     *
     * @OA\Post(
     *     path="/api/departments/{department}/assign-user",
     *     tags={"Departments"},
     *     summary="Assign user(s) to a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="array", @OA\Items(type="integer"), example={1,2}),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User(s) assigned to department successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User(s) assigned to department successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function assignUser(Request $request, Department $department): JsonResponse
    {
        // Allow single user_id as integer or array
        $userIds = is_array($request->user_id) ? $request->user_id : [$request->user_id];

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['user_id' => ['The user_id field is required.']]
            ], 422);
        }

        // Filter valid user_ids (skip invalid ones)
        $validUserIds = \App\Models\User::whereIn('id', $userIds)->pluck('id')->toArray();

        if (empty($validUserIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['user_id' => ['All provided user_ids are invalid.']]
            ], 422);
        }

        // Check which users are already assigned
        $alreadyAssigned = [];
        $newAssignments = [];
        foreach ($validUserIds as $userId) {
            if ($department->users()->where('user_id', $userId)->exists()) {
                $alreadyAssigned[] = $userId;
            } else {
                $newAssignments[] = $userId;
            }
        }

        if (empty($newAssignments)) {
            return response()->json([
                'success' => true,
                'message' => 'User(s) already assigned to department'
            ]);
        }

        // Attach new users to the department (many-to-many)
        $department->users()->syncWithoutDetaching($newAssignments);

        return response()->json([
            'success' => true,
            'message' => 'User(s) assigned to department successfully'
        ]);
    }
    


    /**
     * Remove user from department.
     *
     * @OA\Post(
     *     path="/api/departments/{department}/remove-user",
     *     tags={"Departments"},
     *     summary="Remove a user from a department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User removed from department successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User removed from department successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */

    public function removeUser(Request $request, Department $department): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Detach user from the department (many-to-many)
        $department->users()->detach($request->user_id);

        return response()->json([
            'success' => true,
            'message' => 'User removed from department successfully'
        ]);
        
        
    }
    
    
}


