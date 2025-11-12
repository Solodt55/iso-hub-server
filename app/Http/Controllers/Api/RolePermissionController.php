<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;
use App\Services\ApiResponseService; // Import API response service
use App\Services\RolePermissionService; // Import API response service
use PhpParser\Node\Expr\FuncCall;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    protected $DashboardService;
    protected $RolePermissionService;

    public function __construct(DashboardService $DashboardService, RolePermissionService $RolePermissionService)
    {
        $this->DashboardService = $DashboardService;
        $this->RolePermissionService = $RolePermissionService;
    }

    public function index()
    {
        // $permission = 'role.view';
        // $userPermission = $this->DashboardService->checkPermission($permission);

        // if (isset($userPermission) && !empty($userPermission)) {
        //     return $userPermission;
        // }

        $roles = Role::with('permissions')->get();

        return ApiResponseService::success('Roles retrieved successfully', $roles);
    }

    public function create(Request $request)
    {
        // $permission = 'role.create';
        // $userPermission = $this->DashboardService->checkPermission($permission);

        // if (isset($userPermission) && !empty($userPermission)) {
        //     return $userPermission;
        // }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name|max:50',
            'permissions' => 'array',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = $this->RolePermissionService->createRole($request);

        return ApiResponseService::success('Role created successfully', $role);
    }

    public function update(Request $request)
    {
        // $permission = 'role.edit';
        // $userPermission = $this->DashboardService->checkPermission($permission);

        // if (isset($userPermission) && !empty($userPermission)) {
        //     return $userPermission;
        // }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50|unique:roles,name,' . $request->role_id,
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = $this->RolePermissionService->updateRole($request);
        return ApiResponseService::success('Role updated successfully', $role);
    }

    public function destroy($role_id)
    {
        // $permission = 'role.delete';
        // $userPermission = $this->DashboardService->checkPermission($permission);

        // if (!empty($userPermission)) {
        //     return $userPermission;
        // }
        // Check if the role exists
        $role = Role::find($role_id);
        if (!$role) {
            return ApiResponseService::error('Role not found', 404);
        }
        $role = $this->RolePermissionService->deleteRole($role_id);
        return ApiResponseService::success('Role and its permissions deleted successfully');
    }
}
