<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Neema Gospel Choir APIs",
 *     description="This is the API documentation for the Neema Gospel Choir system. 
 *      ",
 *     version="1.0.0",
 *     @OA\Contact(
 *         email="bennycive@gmail.com",
 *         name="API Support"
 *     ),
 *     
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Use the JWT token obtained from the login endpoint. 
 *         Example: 'Bearer {token}'"
 * )
 */

abstract class Controller
{
    //
}


