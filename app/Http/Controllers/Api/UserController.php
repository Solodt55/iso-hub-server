<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\UserService;
use App\Services\RepService;
use App\Services\DashboardService;
use App\Services\ApiResponseService; // Import API response service
use App\Models\User;
use App\Models\UploadFiles;
use Illuminate\Support\Facades\Mail;
use App\Models\Vendor;
use App\Mail\ProspectMail;
use App\Mail\ClearSignatureMail;
use App\Models\JotForm;
use App\Models\Rep;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $UserService;
    protected $DashboardService;
    protected $RepService;

    public function __construct(UserService $UserService, DashboardService $DashboardService, RepService $RepService)
    {
        $this->UserService = $UserService;
        $this->DashboardService = $DashboardService;
        $this->RepService = $RepService;
    }

    public function createTeamMember(Request $request)
    {
        $validationResponse = $this->userValidation($request);

        if ($validationResponse) {
            return $validationResponse; // return validation error response
        }

        $permission = 'team_member.add';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }
        // $user = $this->UserService->addTeamMember($request);
        $user = $this->UserService->addUser($request);

        return ApiResponseService::success('Team member added successfully', $user);
    }

    public function createUser(Request $request)
    {
        $validationResponse = $this->userValidation($request);

        if ($validationResponse) {
            return $validationResponse; // return validation error response
        }

        $permission = 'user.add';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->UserService->addUser($request);

        return ApiResponseService::success('User added successfully', $user);
    }

    private function userValidation($request)
    {
        // Use Validator for detailed error handling

        if ($request->type === 'tracer') {
            $request->merge([
                'first_name' => $request->fName,
                'last_name' => $request->lName,
            ]);
        }

        // $validator = Validator::make($request->all(), [
        //     'first_name' => 'required|string|max:255',
        //     'last_name' => 'required|string|max:255',
        //     'email' => 'required|string|email|unique:users,email',
        //     'phone' => 'required',
        //     'role_id' => 'required|integer|exists:roles,id', // Check if role_id exists
        //     // 'password' => 'required',
        //     'password' => [
        //         'required',
        //         'string',
        //         'min:8',              // Minimum 8 characters
        //         'regex:/[a-z]/',      // At least one lowercase letter
        //         'regex:/[A-Z]/',      // At least one uppercase letter
        //         'regex:/[0-9]/',      // At least one digit
        //         'regex:/[@$!%*#?&]/', // At least one special character
        //     ],
        // ]);


        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'required',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => [
                'required',
                'string',
                'min:8',
                'has_lowercase',
                'has_uppercase',
                'has_digit',
                'has_special',
            ],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.has_lowercase' => 'Password must contain at least one lowercase letter.',
            'password.has_uppercase' => 'Password must contain at least one uppercase letter.',
            'password.has_digit' => 'Password must contain at least one number.',
            'password.has_special' => 'Password must contain at least one special character (@$!%*#?&).',
        ]);



       // Return validation errors if any
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 200);
        }
    }

    public function createVendor(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:vendors,email',
            'phone' => 'required',
            'address' => 'required',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = 'vendor.add';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->UserService->addVendor($request);
        return ApiResponseService::success('Vendor added successfully', $user);
    }

    public function createRep(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:reps,email',
            'phone' => 'required',
            'address' => 'required',
            'user_id' => 'required',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = 'reps';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->RepService->addRep($request);
        return ApiResponseService::success('Rep added successfully', $user);
    }

    public function updateRep(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:reps,email,' . $request->id,
            'phone' => 'required',
            'id' => 'required|exists:reps,id',
            'address' => 'required',
            'user_id' => 'required'
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = 'reps';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->RepService->updateRep($request);
        return ApiResponseService::success('Rep updated successfully', $user);
    }

    public function updateTeamMember(Request $request)
    {
        $validationResponse = $this->updateUserValidation($request);

        if ($validationResponse) {
            return $validationResponse; // return validation error response
        }

        $permission = 'team_member.edit';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }
        // $user = $this->UserService->updateTeamMember($request);
        $user = $this->UserService->updateUser($request);
        return ApiResponseService::success('Team member updated successfully', $user);
    }

    public function updateUser(Request $request)
    {
        $validationResponse = $this->updateUserValidation($request);

        if ($validationResponse) {
            return $validationResponse; // return validation error response
        }

        $permission = 'user.edit';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->UserService->updateUser($request);
        return ApiResponseService::success('User updated successfully', $user);
    }

    private function updateUserValidation($request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email,' . $request->id,
            'phone' => 'required',
            'role_id' => 'required|integer|exists:roles,id', // Check if role_id exists
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    }

    public function updateVendor(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:vendors,email,' . $request->id,
            'phone' => 'required',
            'id' => 'required|exists:vendors,id',
            'address' => 'required'
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = 'vendor.edit';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->UserService->updateVendor($request);
        return ApiResponseService::success('Vendor updated successfully', $user);
    }

    public function destroyRep(Request $request)
    {
        $permission = 'reps';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = Rep::find($request->id);
        if (!$user) {
            return ApiResponseService::error('Reps not found', 404);
        }
        $user = $this->RepService->destroyRep($request->id);
        return ApiResponseService::success('Rep deleted successfully');
    }

    public function destroyTeamMember($id)
    {

        $permission = 'team_member.delete';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = User::find($id);
        if (!$user) {
            return ApiResponseService::error('Team member not found', 404);
        }
        $user = $this->UserService->destroyTeamMember($id);
        return ApiResponseService::success('Team member deleted successfully');
    }

    public function destroyUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponseService::error('User not found', 404);
        }

        $permission = 'user.delete';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = $this->UserService->destroyUser($id);
        return ApiResponseService::success('User deleted successfully');
    }

    public function destroyVendor($id)
    {
        $permission = 'vendor.delete';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $user = Vendor::find($id);
        if (!$user) {
            return ApiResponseService::error('Vendor not found', 404);
        }
        $user = $this->UserService->destroyVendor($id);
        return ApiResponseService::success('Vendor deleted successfully');
    }

    public function getUserPermission($user_id)
    {
        // Find user by email
        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return ApiResponseService::error('User not found', 404);
        }
        $roles = $user->getRoleNames(); // Returns a collection of role names
        $permissions = $user->getAllPermissions()->pluck('name'); // Get all permissions assigned
        $data = [
            'user' => $user,
            'roles' => $roles, // List of roles
            'permissions' => $permissions, // List of permissions
        ];
        return ApiResponseService::success('User permissions fetched successfully', $data);
    }

    public function getUsers(Request $request)
    {
        // $permission = 'user.view';
        // $userPermission = $this->DashboardService->checkPermission($permission);

        // if (!empty($userPermission)) {
        //     return $userPermission;
        // }

        $query = User::query();

        if ($request->user_id) {
            $query->where('id', $request->user_id);
        }

        if ($request->role_id) {
            $query->where('role_id', $request->role_id);
        }
        $users = $query->get();
        return ApiResponseService::success('User lists fetched successfully', $users);
    }

    public function getTeamMembersList(Request $request)
    {
        $permission = 'team_member.view';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $query = User::query();

        if ($request->id) {
            $query->where('id', $request->id);
        }
        $team_members = $query->where('role_id', 6)->get();
        return ApiResponseService::success('Team member lists fetched successfully', $team_members);
    }

    public function getVendorsList(Request $request)
    {
        $permission = 'vendor.view';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $query = Vendor::query();

        if ($request->id) {
            $query->where('id', $request->id);
        }
        $vendors = $query->get();
        return ApiResponseService::success('Vendor lists fetched successfully', $vendors);
    }

    public function getRepsList(Request $request)
    {
        $permission = 'reps.view';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $query = Rep::query();

        if ($request->id) {
            $query->where('id', $request->id);
        }

        $reps = $query->get();

        return ApiResponseService::success('Reps lists fetched successfully', $reps);
    }

    public function getRepsListUsingUserId(Request $request)
    {
        $permission = 'reps.view';
        $userPermission = $this->DashboardService->checkPermission($permission);

        if (!empty($userPermission)) {
            return $userPermission;
        }

        $query = Rep::query();

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $reps = $query->get();

        return ApiResponseService::success('Reps lists fetched successfully', $reps);
    }

    public function getUserForRep()
    {
        // Fetch users with the role "rep"
        $user_role = User::where('role_id', 5)->get();
        return ApiResponseService::success('User fetched successfully', $user_role);
    }

    public function sendEmailToProspect(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'email' => 'required|email',
            'name' => 'required',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        if ($request->user_id != $userId) {
            return ApiResponseService::error('Unauthorized user.', 401);
        }
        $emailId = $request->email;
        $name = $request->name;

        $data = [
            'user_id' => $userId,
            'email' => $emailId,
            'name' => $name
        ];

        Mail::to($emailId)->send(new ProspectMail($userId, $emailId, $name));

        return ApiResponseService::success('Email sent successfully', $data);
    }

    public function clearSignatureSendMail(Request $request)
    {
        // Use Validator for detailed error handling
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'form_id' => 'required|integer||exists:jot_forms,id',
            'personal_guarantee_required' => 'required',
            'clear_signature' => 'required',
            'email' => 'required|email',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        if ($request->user_id != $userId) {
            return ApiResponseService::error('Unauthorized user.', 401);
        }

        $emailId = $request->email;

        $data = [
            'user_id' => $userId,
            'form_id' => $request->form_id,
            'personal_guarantee_required' => $request->personal_guarantee_required,
            'clear_signature' => $request->clear_signature,
            'email' => $emailId,
        ];

        Mail::to($emailId)->send(new ClearSignatureMail($data));

        $jotform = JotForm::find($request->form_id);
        $jotform->update(['mail_status' => 1]);

        return ApiResponseService::success('Email sent successfully');
    }

    public function updateUserInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email,' . $request->user_id,
            'phone' => 'required',
            'user_id' => 'required'
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();

        if ($request->user_id != $userId) {
            return ApiResponseService::error('Unauthorized user.', 401);
        }

        $user = $this->UserService->updateUserInfoWithPass($request);
        return ApiResponseService::success('User updated successfully', $user);
    }

    public function getUserDetails(Request $request)
    {
        $userId = Auth::id();

        if ($request->user_id != $userId) {
            return ApiResponseService::error('Unauthorized user.', 401);
        }

        $query = User::query();

        if ($request->user_id) {
            $query->where('id', $request->user_id);
        }
        $users = $query->first();
        return ApiResponseService::success('User details fetched successfully', $users);
    }

    public function verifySession(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['valid' => false]);
        }

        // Verify that the user's role_id matches what's stored
        if ($user->role_id != $request->role_id) {
            return response()->json(['valid' => false]);
        }

        if ($user->id != $request->user_id) {
            return response()->json(['valid' => false]);
        }

        return response()->json([
            'valid' => true,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->permissions->pluck('name')
        ]);
    }
}
