<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/doctor/posts/{postId}/like",
     *     tags={"Doctor - Posts"},
     *     summary="Like post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Already liked"),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $post = Post::find($postId);
        if (!$post) {
            return $this->error('Post not found', 404);
        }

        $exists = PostLike::where('post_id', $postId)
            ->where('user_type', 'doctor')
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return $this->error('Already liked', 422);
        }

        PostLike::create([
            'post_id' => $postId,
            'user_type' => 'doctor',
            'user_id' => $request->user()->id,
        ]);

        $post->increment('likes_count');

        return $this->success([], 'Post liked', 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/doctor/posts/{postId}/unlike",
     *     tags={"Doctor - Posts"},
     *     summary="Unlike post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Like not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $postId): JsonResponse
    {
        $like = PostLike::where('post_id', $postId)
            ->where('user_type', 'doctor')
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$like) {
            return $this->error('Like not found', 404);
        }

        $post = Post::find($postId);
        $like->delete();
        if ($post) {
            $post->decrement('likes_count');
        }

        return $this->success([], 'Post unliked');
    }
}
