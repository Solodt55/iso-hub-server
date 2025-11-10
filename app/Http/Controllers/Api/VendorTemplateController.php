<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rep;
use App\Models\VendorCategory;
use Illuminate\Http\Request;
use App\Models\VendorTemplates;
use Illuminate\Support\Facades\Storage;
use App\Services\ApiResponseService; // Import API response service
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorTemplateController extends Controller
{
    // public function storeVendorTemplates(Request $request)
    // {
    //     $vendors = json_decode($request->input('vendors'), true);

    //     if (!is_array($vendors)) {
    //         return response()->json(['error' => 'Invalid vendors data'], 400);
    //     }

    //     $errors = [];

    //     foreach ($vendors as $index => $vendor) {
    //         $vendorName = $vendor['vendor_name'] ?? null;

    //         if (!$vendorName) {
    //             $errors[] = "Vendor name is required at index $index.";
    //             continue;
    //         }

    //         // Check for uniqueness
    //         $existingVendor = VendorTemplates::where('vendor_name', $vendorName)->where('deleted_from_home', 0)->where('is_dropdown_show', 0)->first();

    //         if ($existingVendor) {
    //             $errors[] = "Vendor name '{$vendorName}' already exists at index $index.";
    //             continue;
    //         }

    //         $is_dropdown = VendorTemplates::where('vendor_name', $vendorName)->where('is_dropdown_show',1)->first();

    //         $is_dropdown_show = 0;

    //         if(isset($is_dropdown) && !empty($is_dropdown)){
    //             $is_dropdown_show = 0;
    //         }else{
    //             $is_dropdown_show = 1;
    //         }

    //         $logoPath = null;

    //         if ($request->hasFile("vendors.$index.logo_url")) {
    //             $file = $request->file("vendors.$index.logo_url");
    //             $logoPath = $file->store('vendor_logos', 'public');
    //         }

    //         $data = [
    //             'user_id' => $vendor['user_id'] ?? null,
    //             'vendor_type' => $vendor['vendor_type'] ?? null,
    //             'vendor_name' => $vendorName,
    //             'vendor_email' => $vendor['vendor_email'] ?? null,
    //             'vendor_phone' => $vendor['vendor_phone'] ?? null,
    //             'logo_url' => $logoPath ? asset('storage/' . $logoPath) : parse_url($vendor['logo_url'], PHP_URL_PATH),
    //             'login_url' => $vendor['login_url'] ?? null,
    //             'support_info' => $vendor['support_info'] ?? null,
    //             'notes' => $vendor['notes'] ?? null,
    //             'rep_name' => $vendor['rep_name'] ?? null,
    //             'rep_email' => $vendor['rep_email'] ?? null,
    //             'rep_phone' => $vendor['rep_phone'] ?? null,
    //             'description' => $vendor['description'] ?? null,
    //             'is_dropdown_show' => $is_dropdown_show
    //         ];

    //         VendorTemplates::create($data);
    //     }

    //     if (!empty($errors)) {
    //         return response()->json(['message' => 'Some vendors were not saved.', 'errors' => $errors], 422);
    //     }

    //     return ApiResponseService::success('Vendors saved successfully', []);
    // }

    public function storeVendorTemplates(Request $request)
    {
        // Build vendors array from FormData fields
        $vendors = [];
        $index = 0;
        while (true) {
            $prefix = "vendors.$index.";
            $vendor_name = $request->input($prefix . 'vendor_name');
            // If no vendor_name, break (end of list)
            if ($vendor_name === null) {
                break;
            }
            $vendor = [
                'user_id' => $request->input($prefix . 'user_id'),
                'vendor_type' => $request->input($prefix . 'vendor_type'),
                'vendor_name' => $vendor_name,
                'vendor_email' => $request->input($prefix . 'vendor_email'),
                'vendor_phone' => $request->input($prefix . 'vendor_phone'),
                'login_url' => $request->input($prefix . 'login_url'),
                'support_info' => $request->input($prefix . 'support_info'),
                'notes' => $request->input($prefix . 'notes'),
                'rep_name' => $request->input($prefix . 'rep_name'),
                'rep_email' => $request->input($prefix . 'rep_email'),
                'rep_phone' => $request->input($prefix . 'rep_phone'),
                'description' => $request->input($prefix . 'description'),
                'logo_file' => $request->file($prefix . 'logo_url'),
                'logo_url' => $request->input($prefix . 'logo_url'),
            ];
            $vendors[] = $vendor;
            $index++;
        }

        if (empty($vendors)) {
            return response()->json(['error' => 'No vendors data found'], 400);
        }

        $errors = [];

        foreach ($vendors as $index => $vendor) {
            $vendorName = $vendor['vendor_name'] ?? null;
            if (!$vendorName) {
                $errors[] = "Vendor name is required at index $index.";
                continue;
            }

            // Check if name already exists for is_dropdown_show = 0
            $existsNonDropdown = VendorTemplates::where('vendor_name', $vendorName)
                ->where('deleted_from_home', 0)
                ->where('is_dropdown_show', 0)
                ->exists();
            if ($existsNonDropdown) {
                $errors[] = "Vendor name '{$vendorName}' already exists at index $index.";
                continue;
            }

            // Check if already exists for is_dropdown_show = 1
            $existsDropdown = VendorTemplates::where('vendor_name', $vendorName)
                ->where('deleted_from_home', 0)
                ->where('is_dropdown_show', 1)
                ->exists();

            // Handle logo
            $logoPath = null;
            if ($vendor['logo_file']) {
                $logoPath = $vendor['logo_file']->store('vendor_logos', 'public');
            }
            $logoUrl = $logoPath
                ? asset("storage/{$logoPath}")
                : (isset($vendor['logo_url']) ? parse_url($vendor['logo_url'], PHP_URL_PATH) : null);

            // Shared data
            $baseData = [
                'user_id'        => $vendor['user_id'] ?? null,
                'vendor_type'    => $vendor['vendor_type'] ?? null,
                'vendor_name'    => $vendorName,
                'vendor_email'   => $vendor['vendor_email'] ?? null,
                'vendor_phone'   => $vendor['vendor_phone'] ?? null,
                'logo_url'       => $logoUrl,
                'login_url'      => $vendor['login_url'] ?? null,
                'support_info'   => $vendor['support_info'] ?? null,
                'notes'          => $vendor['notes'] ?? null,
                'rep_name'       => $vendor['rep_name'] ?? null,
                'rep_email'      => $vendor['rep_email'] ?? null,
                'rep_phone'      => $vendor['rep_phone'] ?? null,
                'description'    => $vendor['description'] ?? null,
            ];

            // Insert non-dropdown record
            VendorTemplates::create(array_merge($baseData, ['is_dropdown_show' => 0]));

            // Insert dropdown record only if not already present
            if (!$existsDropdown) {
                VendorTemplates::create(array_merge($baseData, ['is_dropdown_show' => 1]));
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some vendors were not saved.',
                'errors'  => $errors,
            ], 422);
        }

        return ApiResponseService::success('Vendors saved successfully', []);
    }

    public function showVendorTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_name' => 'required',
            'vendor_type' => 'required',
            'user_id' => 'required|exists:users,id',
            'vendor_id' => 'required'
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vendor_template = VendorTemplates::with('vendor_user')->where('vendor_name', $request->vendor_name)->where('vendor_type', $request->vendor_type)->where('user_id', $request->user_id)->where('id', $request->vendor_id)->first();

        if (!$vendor_template) {
            return ApiResponseService::error('Template not found', 404);
        }
        return ApiResponseService::success('Template fetched successfully', $vendor_template);
    }

    public function getAdminVendorDropdownData(Request $request)
    {
        $validTypes = ['processors', 'gateways', 'hardware','internal'];

        if (!in_array($request->vendor_type, $validTypes)) {
            return ApiResponseService::error('Invalid vendor type', 404);
        }

        $vendors = VendorTemplates::where('vendor_type', $request->vendor_type)
            ->where('user_id', 2)
            ->where('is_dropdown_show', 1)
            ->select('vendor_name','id')
            ->distinct()
            ->get();

        return ApiResponseService::success('Template fetched successfully', $vendors);
    }

    public function getAllVendorsList(Request $request)
    {
        $query = VendorTemplates::query();
        // Include records where 'deleted_from_home' is NULL or 0
        $query->where(function ($q) {
            $q->where('deleted_from_home', 0);
        });

        $query->where(function ($q) {
            $q->where('is_dropdown_show', 0);
        });

        // if ($request->has('user_id')) {
        //     $query->where('user_id', $request->user_id);
        // }

        if ($request->has('user_id')) {
            $userIds = [$request->user_id, 2]; // Include requested user and user ID 2
            $query->whereIn('user_id', $userIds);
        }

        // $vendors = $query->orderBy('card_order','asc')->get();
        // Get vendors grouped and sorted per user to preserve correct card_order
        $vendors = $query->get()->groupBy('user_id')->flatMap(function ($userGroup) {
            return $userGroup->sortBy('card_order');
        });

        // Group vendors by type    
        $categorizedVendors = $vendors->groupBy('vendor_type')->map(function ($group) {
            return $group->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'vendor_name' => $vendor->vendor_name,
                    'vendor_email' => $vendor->vendor_email,
                    'vendor_phone' => $vendor->vendor_phone,
                    'logo_url' => $vendor->logo_url,
                    'login_url' => $vendor->login_url,
                    'rep_name' => $vendor->rep_name,
                    'rep_email' => $vendor->rep_email,
                    'rep_phone' => $vendor->rep_phone,
                    'notes' => $vendor->notes,
                    'support_info' => $vendor->support_info,
                    'description' => $vendor->description,
                    'vendor_type' => $vendor->vendor_type,
                    'created_at' => $vendor->created_at,
                    'updated_at' => $vendor->updated_at
                ];
            });
        });

        return ApiResponseService::success('Vendors fetched successfully', $categorizedVendors);
    }

    public function updateVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:vendor_templates,id',
            'vendor_name' => [
                'required',
                Rule::unique('vendor_templates', 'vendor_name')
                    ->ignore($request->id)
                    ->where(function ($query) {
                    $query->where('deleted_from_home', 0)
                            ->where('is_dropdown_show', 0);
                    }),
            ],
            // 'vendor_email' => 'required|email',
            // 'vendor_phone' => 'required',
            // 'logo_url' => 'nullable|file|image',
            // 'login_url' => 'required',
            // 'rep_name' => 'required',
            // 'rep_email' => 'required|email',
            // 'rep_phone' => 'required',
            // 'notes' => 'required',
            // 'support_info' => 'required',
            // 'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422); // <-- Pass a proper status code
        }

        $vendor = VendorTemplates::find($request->id);

        $data = [
            'vendor_name' => $request->vendor_name,
            'vendor_email' => $request->vendor_email,
            'vendor_phone' => $request->vendor_phone,
            'login_url' => $request->login_url,
            'rep_name' => $request->rep_name,
            'rep_email' => $request->rep_email,
            'rep_phone' => $request->rep_phone,
            'notes' => $request->notes,
            'support_info' => $request->support_info,
            'description' => $request->description,
        ];

        if ($request->hasFile("logo_url")) {
            $file = $request->file("logo_url");
            $logoPath = $file->store('vendor_logos', 'public');
            $data['logo_url'] = asset('storage/' . $logoPath);
        }

        $vendor->update($data);

        return ApiResponseService::success('Vendor updated successfully', $vendor);
    }


    public function editVendorDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:vendor_templates,id',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::error('Validation error', $validator->errors(), 422);
        }

        $vendor = VendorTemplates::find($request->id);

        if (!$vendor) {
            return ApiResponseService::error('Vendor not found', 404);
        }

        return ApiResponseService::success('Vendor details fetched successfully', $vendor);
    }

    public function deleteVendor(Request $request)
    {
        $vendor_template = VendorTemplates::find($request->id);

        if (!$vendor_template) {
            return ApiResponseService::error('Vendor not found', 404);
        }

        $vendor_template->deleted_from_home = 1;
        $vendor_template->save();

        return ApiResponseService::success('Vendor deleted successfully', $vendor_template);
    }

    public function updateCardOrder(Request $request)
    {
        try {
            $request->validate([
                'vendor_ids' => 'required|array',
                'vendor_ids.*' => 'required|integer|exists:vendor_templates,id'
            ]);

            \Log::info($request->all());

            foreach ($request->vendor_ids as $index => $vendorId) {
                VendorTemplates::where('id', $vendorId)
                    ->update(['card_order' => $index]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Card order updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
