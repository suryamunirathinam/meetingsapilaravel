<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
class AuthController extends Controller
{
    public function store(Request $request){

        $validatedData = $request->validate([
            'name' =>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:5'
        ]);
        


        $validatedData['password']   = bcrypt($request->password); 
        // $name =$request->input('name');
        // $email =$request->input('email');
        // $password =$request->input('password');


        $user = User::create($validatedData);
        
        $accessToken =$user->createToken('authToken')->accessToken;
        
        return response(['user'=>$user,'access_token'=>$accessToken]);

        // $user = new User([
        //     'name' =>$name,
        //     'email'=> $email,
        //     'password'=>bcrypt($password)
        // ]);

        // if($user->save()){
        //     $user->signin =[
        //         'href'=>'v1/user/signin',
        //         'method' => 'POST',
        //         'params' => 'email,password'
        //     ];
        //     $response =[
        //         'msg' => 'User Created',
        //         'user' => $user
        //     ];
        //     return response()->json($response,201);    
        // }
       
        // $response = [
        //     'msg' => 'An error occured',
        //     'user' =>$user
        // ];

        // return response()->json($response,404);

    }
    public function signin(Request $request){
        $loginData = $request->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);

        if(!auth()->attempt($loginData)){
            return response(['message'=>'Invalid Credentials'],401);
        }
        $accessToken =auth()->user()->createToken('authToken')->accessToken;
        return response(['user'=>auth()->user(),'access_token'=>$accessToken]);
    }
}
