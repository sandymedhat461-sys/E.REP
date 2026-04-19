<?php

namespace App\OpenApi;

/**
 * Global OpenAPI metadata (concrete class so swagger-php picks it up).
 *
 * @OA\Info(
 *     title="E-Rep API",
 *     version="1.0.0",
 *     description="E-Rep Pharmaceutical Platform API"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
final class OpenApiInfo
{
}
