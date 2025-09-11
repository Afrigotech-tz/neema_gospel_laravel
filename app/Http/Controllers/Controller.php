<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="APIs For Neema Gospel Choir",
 *     version="1.0.0"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     in="header",
 *     name="bearerAuth"
 * )
 */
abstract class Controller
{
    //
}


