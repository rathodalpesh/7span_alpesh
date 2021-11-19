<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Validator;
use App\Http\Resources\User as UserResource;
use Illuminate\Support\Facades\Auth;
use Storage;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $Users = User::where('status', 1)->where('role', 0);

        if($request->hobbies != ""){
            $Users->where('hobbies', 'like', '%'. $request->hobbies.'%');
        }
        
        return $this->sendResponse(UserResource::collection($Users->get()), 'Users retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input,$this->rules($request));

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        //image upload
        if( $request->file('user_photo') ){
            $image = $request->file('user_photo');
            $image_uploaded_path = $image->store('users', 'public');
            $input["user_photo"] = Storage::disk('public')->url($image_uploaded_path);
        }

        $User = User::create($input);

        return $this->sendResponse(new UserResource($User), 'User created successfully.');
    }

    /**
     * @param Request $request
     * 
     * @return array
     */
    private function rules(Request $request , $id = "" ): array
    {
        $rules = [
            'first_name' => 'sometimes|required',
            'last_name' => 'sometimes|required',
            'user_photo' => 'image:jpeg,png,jpg,gif,svg|max:2048',
            'mobile_number' => 'sometimes|required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'status' => 'in:Active,Inactive',
        ];

        if ($id != "" ){
            $rules['email'] = 'sometimes|required|email:rfc,dns|unique:users,email,' . $id;
        }else{
            $rules['email'] = 'required|email:rfc,dns|unique:users';
        }

        if ($id == ""){
            $rules['password'] = 'required|min:6';
            $rules['c_password'] = 'required|same:password|min:6';

        }else{
            $rules['password'] = 'sometimes|required|min:6';
            $rules['c_password'] = 'sometimes|required|required_if:password|same:password|min:6';
        }

        return  $rules;
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $User = User::find($id);

        if (is_null($User)) {
            return $this->sendError('User not found.');
        }

        return $this->sendResponse(new UserResource($User), 'User retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $User)
    {
        $input = $request->all();
        $User = $this->userDataUpdate($request, $input, $User);
        return $this->sendResponse(new UserResource($User), 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $User)
    {
        $User->delete();
        return $this->sendResponse([], 'User deleted successfully.');
    }

    public function ProfileUpdate(Request $request)
    {
        $User = Auth::user();
        $input = $request->all();
        $User = $this->userDataUpdate($request, $input, $User);
        return $this->sendResponse(new UserResource($User), 'Profile updated successfully.');
    }

    public function userDataUpdate( $request, $input , $User ){

        $validator = Validator::make($input, $this->rules($request, $User->id));

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if (isset($request->first_name)) {
            $User->first_name = $input['first_name'];
        }

        if (isset($request->last_name)) {
            $User->last_name = $input['last_name'];
        }

        if (isset($request->email)) {
            $User->email = $input['email'];
        }

        if (isset($request->mobile_number)) {
            $User->mobile_number = $input['mobile_number '];
        }

        if (isset($request->hobbies) &&  count($request->hobbies) > 0) {
            $User->hobbies = $input['hobbies'];
        }

        if (isset($request->password)) {
            $User->password = $input['password'];
        }

        if (isset($request->status)) {
            $User->status = 0;
            if( $input['status'] == 'active' ){
                $User->status = 1;
            }
        }

        //image upload
        if ($request->file('user_photo')) {
            $image = $request->file('user_photo');
            $image_uploaded_path = $image->store('users', 'public');
            $User->user_photo = Storage::disk('public')->url($image_uploaded_path);
        }

        $User->save();

        return $User;
    }

}
