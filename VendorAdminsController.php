<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use App\Models\Vendor;

use App\Models\Permission;
use App\Models\Role;

class VendorAdminsController extends Controller
{
    public $vendor_data = [];

    public function __construct() {
        $this->middleware('vendor');
        $this->middleware('role:vendorAdmins');

    }

    public function vendor_add_data(){

        $this->vendor_data['id'] = Auth::guard('vendor')->id();
        $this->vendor_data['admin_id'] = Auth::guard('vendor')->user()->id;
        $this->vendor_data['admin_name'] = Auth::guard('vendor')->user()->vendor_name;
        $this->vendor_data['admin_email'] = Auth::guard('vendor')->user()->email;
        $this->vendor_data['admin_phone'] = Auth::guard('vendor')->user()->phone;
        $this->vendor_data['admin_avatar'] = Auth::guard('vendor')->user()->vendor_image;

        return $this->vendor_data;
    }

    public function hasRole($user_id, $role_slug) {

        $admin = Vendor::find($user_id);

        if ($admin->roles->contains('slug', $role_slug)) {
            return true;
        }
        return false;
        
    }

    public function hasPermission($user_id, $permission_slug) {

        $admin = Vendor::find($user_id);

        if ($admin->can($permission_slug)) {
            return true;
        }
        return false;
        
    }

    public function getRolePermission($role_id) {

        $permission = Permission::where('role_id', $role_id)->get();
        return $permission;
        
    }
  
    public function showProfile(){
        $vendor = Vendor::find(Auth::guard('vendor')->user()->id);
        return view('dashboardVendor.admins.profile', compact('vendor'));
    }

    public function show(){
        $admins = Vendor::where('main_vendor_id', Auth::guard('vendor')->id())->orderBy('id', 'DESC')->get();
        //$admins = Vendor::orderBy('id','DESC')->paginate(5);
        //return view('dashboardVendor.admins.admins',compact('data'))->with('i', ($request->input('page', 1) - 1) * 5);
        return view('dashboardVendor.admins.admins', compact('admins'))->with($this->vendor_add_data());
    }

    public function edit($id){
        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'edit-vendor-admin')) {
            $matchThese = ['id' => $id, 'main_vendor_id' => Auth::guard('vendor')->id()];
            $admin = Vendor::where($matchThese)->get()->first();
            $roles = Role::where('type', 'vendor')->get();

            if($admin){
                return view('dashboardVendor.admins.edit', compact(['admin', 'roles']))->with($this->vendor_add_data());
            }else{
                return redirect('vendor/adminsList')->with('error', trans('all.no_data'));
            }
        }else{
            return back()->with('error', trans('all.not_have_permission'));
        }
    }

    public function delete($id){
        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'delete-vendor-admin')) {
            $matchThese = ['id' => $id, 'main_vendor_id' => Auth::guard('vendor')->id()];
            $admin = Vendor::where($matchThese)->get()->first();
            $admin->delete();
            return back();
        }else{
            return back()->with('error', trans('all.not_have_permission'));
        }
    }
  
    public function update(Request $request){

        $id = Auth::guard('vendor')->user()->id;

        $vendor = Vendor::find($id);
        
        $logoutStataus = 0;

            $passwordNew = $vendor->password;
            $avatarName = $vendor->vendor_image;
            $logoName = $vendor->vendor_logo;
            $commercialRegistrationNumberImage = $vendor->commercialRegistrationNumberImage;
            $bankImages = $vendor->bankImages;
            $commercialLicenseImages = $vendor->commercialLicenseImages;
            $zakatTaxCertificate = $vendor->zakatTaxCertificate;

            $request->validate([
                'name' => 'required',
                'email' => 'required|email|' . Rule::unique('vendors')->ignore($id, 'id'),
                'phone' => 'required|numeric|min:10',
                'country' => 'required',
                'address' => 'nullable',
                'password' => 'nullable|min:8',
                'avatar' => 'nullable|image',
                'logo' => 'nullable|image',
                'commercialRegistrationNumber' => 'required',
                'commercialRegistrationNumberImage' => ($commercialRegistrationNumberImage) ? 'nullable' : 'required' . '|image',
                'bankName' => 'required',
                'bankIBAN' => 'required',
                'bankImages' => ($bankImages) ? 'nullable' : 'required' . '|image',
                'commercialLicenseImages' => ($commercialLicenseImages) ? 'nullable' : 'required' . '|image',
                'zakatTaxCertificate' => ($zakatTaxCertificate) ? 'nullable' : 'required' . '|image',
            ]);
    
            if($request->avatar){
                $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
                $request->avatar->move(public_path('avatars/vendors/images'), $avatarName);
            }

            if(!$vendor->main_vendor_id){
                if($request->logo){
                    $logoName = time().'.'.$request->logo->getClientOriginalExtension();
                    $request->logo->move(public_path('avatars/vendors/logos'), $logoName);
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }

                if($request->commercialRegistrationNumberImage){
                    $commercialRegistrationNumberImage = time().'.'.$request->commercialRegistrationNumberImage->getClientOriginalExtension();
                    $request->commercialRegistrationNumberImage->move(public_path('avatars/vendors/commercialRegistrationNumberImage'), $commercialRegistrationNumberImage);
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }

                if($request->bankImages){
                    $bankImages = time().'.'.$request->bankImages->getClientOriginalExtension();
                    $request->bankImages->move(public_path('avatars/vendors/bankImages'), $bankImages);
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }

                if($request->commercialLicenseImages){
                    $commercialLicenseImages = time().'.'.$request->commercialLicenseImages->getClientOriginalExtension();
                    $request->commercialLicenseImages->move(public_path('avatars/vendors/commercialLicenseImages'), $commercialLicenseImages);
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }

                if($request->zakatTaxCertificate){
                    $zakatTaxCertificate = time().'.'.$request->zakatTaxCertificate->getClientOriginalExtension();
                    $request->zakatTaxCertificate->move(public_path('avatars/vendors/zakatTaxCertificate'), $zakatTaxCertificate);
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }

                if($request->commercialRegistrationNumber != $vendor->commercialRegistrationNumber){
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }
                if($request->bankName != $vendor->bankName){
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }
                if($request->bankIBAN != $vendor->bankIBAN){
                    $vendor->status = 0;
                    $logoutStataus = 1;
                }
            }else{
                $logoName = '';
                $commercialRegistrationNumberImage = '';
                $bankImages = '';
                $commercialLicenseImages = '';
                $zakatTaxCertificate = '';
            }

            if($request->password){
                $passwordNew = Hash::make($request->password);
                $logoutStataus = 1;
            }

            $vendor->vendor_name = $request->name;
            $vendor->email = $request->email;
            $vendor->phone = $request->phone;
            $vendor->country = $request->country;
            $vendor->address = $request->address;
            $vendor->password = $passwordNew;
            $vendor->vendor_image = $avatarName;
            $vendor->vendor_logo = $logoName;

            $vendor->commercialRegistrationNumber = $request->commercialRegistrationNumber;
            $vendor->commercialRegistrationNumberImage = $commercialRegistrationNumberImage;
            $vendor->bankName = $request->bankName;
            $vendor->bankIBAN = $request->bankIBAN;
            $vendor->bankImages = $bankImages;
            $vendor->commercialLicenseImages = $commercialLicenseImages;
            $vendor->zakatTaxCertificate = $zakatTaxCertificate;

            $vendor->update();

        if($logoutStataus){
            return Redirect::to('vendorLogout');
        }else{
            return back();
        }
  
    }

    // Update by control
    public function updateAdmin(Request $request, $id){

        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'edit-vendor-admin')) {

            $matchThese = ['id' => $id, 'main_vendor_id' => Auth::guard('vendor')->id()];
            $admin = Vendor::where($matchThese)->get()->first();

            $passwordNew = $admin->password;
            $avatarName = $admin->vendor_image;

            $request->validate([
                'name' => 'required',
                'position' => 'required',
                'email' => 'required|email|' . Rule::unique('vendors')->ignore($id, 'id'),
                'phone' => 'nullable|numeric|min:10',
                'password' => 'nullable|min:8',
                'avatar' => 'nullable|image',
            ]);
    
            if($request->avatar){
                $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
                $request->avatar->move(public_path('avatars/vendors/images'), $avatarName);
            }

            if($request->password){
                $passwordNew = Hash::make($request->password);
            }

            $admin->vendor_name = $request->name;
            $admin->position = $request->position;
            $admin->email = $request->email;
            $admin->phone = $request->phone;
            $admin->password = $passwordNew;
            $admin->vendor_image = $avatarName;
            $admin->update();

            return back();

        }
  
    }

    public function updatePermission(Request $request, $id){

        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'edit-vendor-admin')) {

            $matchThese = ['id' => $id, 'main_vendor_id' => Auth::guard('vendor')->id()];
            $admin = Vendor::where($matchThese)->get()->first();

            // Add and update roles
            $admin->roles()->detach();
            if($request->roles){
                foreach($request->roles as $role){
                    $admin->roles()->attach(Role::where('slug', $role)->first());
                }
            }

            // Add and update permissions
            $admin->permissions()->detach();
            if($request->permissions){
                foreach($request->permissions as $permission){
                    $admin->givePermissionsTo($permission);
                    //$admin->permissions()->attach(Permission::where('slug', $permission)->first());
                }
            }

            return back();

        }
  
    }

    public function showCreate(){
        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'create-vendor-admin')) {
            return view('dashboardVendor.admins.create', $this->vendor_add_data());
        }else{
            return back()->with('error', trans('all.not_have_permission'));
        }
    }

    public function create(Request $request){

        if (Vendor::vendorHasPermission(Auth::guard('vendor')->user()->id,'create-vendor-admin')) {
            $admin = new Vendor;

            $validated = $request->validate([
                'name' => 'required',
                'position' => 'required',
                'email' => 'required|email:rfc,dns|unique:admins,email',
                'password' => 'required|min:8',
                'passwordConfirmation' => 'required|same:password'
            ]);

            $admin->vendor_name = $request->input('name');
            $admin->position = $request->input('position');
            $admin->main_vendor_id = Auth::guard('vendor')->id();
            $admin->email = $request->input('email');
            $admin->phone = $request->input('phone');
            $admin->password = Hash::make($request->input('password'));
            $admin->status = 1;
            $admin->save();

            return Redirect('/vendor/adminsEdit/' . $admin->id);
        }

    }
}
