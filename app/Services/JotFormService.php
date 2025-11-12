<?php

namespace App\Services;

use App\Models\JotForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\JotFormBankDocs;
use App\Models\JotFormOwnerDocs;
use App\Models\JotFormDetails;
use App\Models\TeamMember;
use App\Models\Vendor;
use App\Models\UploadFiles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class JotFormService
{
    public function create($request, $user_id)
    {
        // \Log::info($request->all());
        $jotFormData = [
            'user_id' => $user_id ?? '',
            'first_name' => $request->first_name ?? '',
            'last_name' => $request->last_name ?? '',
            // 'email' => $request->email ?? '',
            // 'phone' => $request->phone ?? '',
            'description' => $request->description ?? '',
            'signature_date' => $request->signature_date ?? '',
            'signature' => $request->signature ?? '',
            'dba' => $request->dba ?? '',
            'address2' => $request->address2 ?? '',
            'city' => $request->city ?? '',
            'state' => $request->state ?? '',
            'pincode' => $request->pincode ?? '',
            'is_same_shipping_address' => $request->is_same_shipping_address ?? '0',
            'shipping_street_address' => $request->shipping_street_address ?? '',
            'shipping_street_address2' => $request->shipping_street_address2 ?? '',
            'shipping_city' => $request->shipping_city ?? '',
            'shipping_state' => $request->shipping_state ?? '',
            'shipping_zip' => $request->shipping_zip ?? '',
            'is_duplicate' => $request->is_duplicate ?? '0',
            'business_dba' => $request->business_dba ?? '',
            'business_corporate_legal_name' => $request->business_corporate_legal_name ?? '',
            'business_location_address' => $request->business_location_address ?? '',
            'business_corporate_address' => $request->business_corporate_address ?? '',
            'business_city' => $request->business_city ?? '',
            'business_state' => $request->business_state ?? '',
            'business_zip' => $request->business_zip ?? '',
            'business_phone_number' => $request->business_phone_number ?? '',
            'business_contact_name' => $request->business_contact_name ?? '',
            'business_contact_number' => $request->business_contact_number ?? '',
            'business_start_date' => !empty($request->business_start_date) ? $request->business_start_date : null,
            'business_tax_id' => $request->business_tax_id ?? '',
            'business_profile_business_type' => $request->business_profile_business_type ?? '',

            'bank_name' => $request->bank_name ?? '',
            'aba_routing' => $request->aba_routing ?? '',
            'doa' => $request->doa ?? '',
            'business_type' => $request->business_type ?? '',
            'processing_services' => $request->processing_services ?? '',
            'terminal' => $request->terminal ?? '',
            'terminal_special_features' => $request->terminal_special_features ?? '',
            'terminal_type_or_model' => $request->terminal_type_or_model ?? '',
            'mobile_app' => $request->mobile_app ?? '',
            'mobile_app_special_features' => $request->mobile_app_special_features ?? '',
            'mobile_app_cardreader_type_model' => $request->mobile_app_cardreader_type_model ?? '',
            'pos_point_of_sale' => $request->pos_point_of_sale ?? '',
            'pos_special_features' => $request->pos_special_features ?? '',
            'system_type_model' => $request->system_type_model ?? '',
            'number_of_stations' => $request->number_of_stations ?? '',
            'pos_other_items' => $request->pos_other_items ?? '',
            'virtual_terminal' => $request->virtual_terminal ?? '',
            'business_type_other' => $request->business_type_other ?? '',
            'personal_guarantee_required' => $request->personal_guarantee_required ?? '',
            'clear_signature' => $request->clear_signature ?? '',
            'mail_status' => $request->mail_status ?? 0,
        ];

        if (!empty($request->form_id)) {
            $form = JotForm::find($request->form_id);
            if ($form) {
                $form->update($jotFormData);
            } else {
                return response()->json(['error' => true, 'message' => 'Form not found'], 404);
            }
        } else {
            $form = JotForm::create($jotFormData);
        }

        $jotFormDetailsArr = [
            'jot_form_id' => $form->id,
            'dba_street_address' => $request->dba_street_address ?? '',
            'dba_street_address2' => $request->dba_street_address2 ?? '',
            'business_profile_business_type_other' => $request->business_profile_business_type_other ?? '',
            'corporate_street_address1' => $request->corporate_street_address1 ?? '',
            'corporate_street_address2' => $request->corporate_street_address2 ?? '',
            'corporate_city' => $request->corporate_city ?? '',
            'corporate_state' => $request->corporate_state ?? '',
            'corporate_zip' => $request->corporate_zip ?? '',
            'business_contact_mail' => $request->business_contact_mail ?? '',
            'business_location_phone_number' => $request->business_location_phone_number ?? '',
            'business_date_started' => $request->business_date_started ?? '',
            'business_website' => $request->business_website ?? '',
            'business_legal_name' => $request->business_legal_name ?? '',
            'terminal_other' => $request->terminal_other ?? '',
            'estimation_early_master_card' => $request->estimation_early_master_card ?? '',
            'estimated_average_ticket' => $request->estimated_average_ticket ?? '',
            'estimated_highest_ticket' => $request->estimated_highest_ticket ?? '',
            'transaction_card_present' => $request->transaction_card_present ?? '',
            'transaction_keyed_in' => $request->transaction_keyed_in ?? '',
            'transaction_all_online' => $request->transaction_all_online ?? '',
            'auto_settle_time' => $request->auto_settle_time ?? '',
            'auto_settle_type' => $request->auto_settle_type ?? '',
            'add_tips_to_account' => $request->add_tips_to_account ?? '',
            'tip_amounts' => $request->tip_amounts ?? '[]',
            'business_products_sold' => $request->business_products_sold ?? '',
            'business_return_policy' => $request->business_return_policy ?? '',
            'location_description' => $request->location_description ?? '',
        ];

        if (!empty($request->form_id)) {
            $JotFormDetailsExist = JotFormDetails::where('jot_form_id',$request->form_id)->first();
            if ($JotFormDetailsExist) {
                $detailsFind = JotFormDetails::find($JotFormDetailsExist->id);
                $detailsFind->update($jotFormDetailsArr);
            } else {
                JotFormDetails::create($jotFormDetailsArr);
            }
        } else {
            JotFormDetails::create($jotFormDetailsArr);
        }

        // $jotFormDetails = JotFormDetails::create();

        if ($request->hasFile('bankingDocs')) {
            foreach ($request->file('bankingDocs') as $file) {
               
                $storedPath = $file->store('uploads', 'public');
                $original_name = $file->getClientOriginalName();
                $images = [
                    'user_id' => $user_id,
                    'form_id' => $form->id,
                    'file_path' => asset('storage/' . $storedPath), // Correct path
                    'prospect_name' => $request->business_contact_name ?? '', // Correct path
                    'file_original_name' => $original_name,
                    'email' =>  ''
                ];
                UploadFiles::create($images);
            }
        }

        if (isset($request->owners) && !empty($request->owners)) {
            foreach ($request->owners as $ownerData) {
                $allImagePaths = [];

                if (isset($ownerData['driver_license_image']) && is_array($ownerData['driver_license_image'])) {
                    foreach ($ownerData['driver_license_image'] as $image) {
                        // $imageName = time() . '_' . $image->getClientOriginalName();
                        // $imagePath = $image->storeAs('uploads/owner_docs', $imageName, 'public');
                        // $allImagePaths[] = 'storage/' . $imagePath;

                        $storedPath = $image->store('uploads', 'public');
                        $original_name = $image->getClientOriginalName();
                        $images = [
                            'user_id' => $user_id,
                            'form_id' => $form->id,
                            'file_path' => asset('storage/' . $storedPath), // Correct path
                            'prospect_name' => $request->business_contact_name ?? '', // Correct path
                            'file_original_name' => $original_name,
                            'email' => $ownerData['ownership_email'] ?? ''
                        ];
                        UploadFiles::create($images);
                    }
                }

                JotFormOwnerDocs::create([
                    'jot_form_id' => $form->id, // Use the form ID if applicable
                    'name' => '',
                    'path' => json_encode($allImagePaths),
                    'jot_form_id' => $form->id,
                    'ownership_first_name' => $ownerData['ownership_first_name'] ?? '',
                    'ownership_last_name' => $ownerData['ownership_last_name'] ?? '',
                    'ownership_percent' => $ownerData['ownership_percent'] ?? '',
                    'ownership_phone_number' => $ownerData['ownership_phone_number'] ?? '',
                    'ownership_city' => $ownerData['ownership_city'] ?? '',
                    'ownership_state' => $ownerData['ownership_state'] ?? '',
                    'ownership_zip' => $ownerData['ownership_zip'] ?? '',
                    'ownership_email' => $ownerData['ownership_email'] ?? '',
                    'ownership_dob' => $ownerData['ownership_dob'] ?? '',
                    'ownership_driver_licence_number' => $ownerData['ownership_driver_licence_number'] ?? '',
                    'ownership_title' => $ownerData['ownership_title'] ?? '',
                    'owner_street_address' => $ownerData['owner_street_address'] ?? '',
                    'owner_street_address2' => $ownerData['owner_street_address2'] ?? '',
                    'ownership_social_security_number' => $ownerData['ownership_social_security_number'] ?? '',
                    'ownership_address' => $ownerData['ownership_address'] ?? '',
                    'ownership_residential_street_address' => $ownerData['ownership_residential_street_address'] ?? '',
                ]);
            }
        }

        if($form){
            $form = JotForm::find($form->id);
            if ($form) {
                $form->update(['iso_form_status' => 5]);
            } else {
                return response()->json(['error' => true, 'message' => 'Form not found'], 404);
            }
        }

        return $form;
    }
}
