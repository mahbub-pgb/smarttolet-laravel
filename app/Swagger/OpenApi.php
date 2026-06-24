<?php

declare(strict_types=1);

namespace App\Swagger;

/**
 * Root OpenAPI document. Endpoint-level annotations live alongside their
 * controllers; this class carries the global metadata, server and the JWT
 * bearer security scheme. Regenerate docs with `php artisan l5-swagger:generate`.
 *
 * @OA\Info(
 *     title="Smart To-Let API",
 *     version="1.0.0",
 *     description="REST API for the Smart To-Let rental marketplace (Bangladesh).",
 *     @OA\Contact(name="Smart To-Let", email="support@smarttolet.test")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API base URL (versioned at /api/v1)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT access token issued by the auth endpoints."
 * )
 *
 * @OA\Schema(
 *     schema="ApiSuccess",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="OK"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="meta", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ApiError",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="code", type="string", example="validation_failed"),
 *     @OA\Property(property="details", type="object")
 * )
 *
 * @OA\Tag(name="Auth", description="Registration, OTP, login, profile")
 * @OA\Tag(name="Listings", description="Browse, search, create and manage listings")
 * @OA\Tag(name="Me", description="Favorites, saved searches, notifications")
 * @OA\Tag(name="Chat", description="Conversations and messages")
 * @OA\Tag(name="Payments", description="Subscriptions and payments")
 * @OA\Tag(name="Blog", description="Blog content")
 * @OA\Tag(name="Admin", description="Moderation, users, reports, analytics")
 * @OA\Tag(name="Public", description="Public settings, places, advertisements")
 */
final class OpenApi
{
}
