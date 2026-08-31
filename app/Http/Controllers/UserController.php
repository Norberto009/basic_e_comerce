<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function register(Request $request){
        try {
            $user = $request->validate([
                'name'=>'required|string|max:100|min:5',
                'email'=>'required|string|email|max:200|unique:users',
                'password'=> [
                    'required',
                    'string',
                    Password::min(8)
                    ->mixedCase() // mayus AND minus
                    ->numbers() // one number
                    ->symbols() // al least one symbol

                ]
            ]);

            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password'])
            ]);

            return response() -> json([
                'message' => 'User Created'

            ], 201);
            
        } catch (\Exception $error) {
            return response()->json([
                'message'=>$error->getMessage(),
                'code'=>$error->getCode()
            ]);
            
        }
    }

    public function login(Request $request){
        try {
           $request -> validate([
                'email' => 'required|string|email',
                'password' => 'required|string|min:8'

            ]);
            //extraemos solo los datos que nos interesan de la peticion
            $credenciales = $request -> only('email', 'password');

            //validar credenciales y confirma si la clave es igual en la BD
            if (Auth::attempt($credenciales)) {
                //si las credenciales funcionaron

                $user = $request -> user();

                //crea el token unico para las peticiones
                $token = $user -> createToken('auth_token')->plainTextToken;
                 
            }

            return response()->json([
                'message' => 'User loged successfully',
                'user' => $user,
                'token' => $token,
                'type:token' => 'Bearer'

            ]); 
        } catch (\Exception $error) {
            return response()->json([
                'message'=>$error->getCode()

            ]);
        }
    }
}
