<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/doctor/posts/{postId}/comments",
     *     tags={"Doctor - Posts"},
     *     summary="Add comment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="comment_text", type="string"),
     *             @OA\Property(property="parent_comment_id", type="integer", description="Optional reply-to comment id")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
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

        $validated = $this->validateRequest($request, [
            'comment_text' => ['required', 'string'],
            'parent_comment_id' => ['nullable', 'exists:comments,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_type' => 'doctor',
            'user_id' => $request->user()->id,
            'comment_text' => $validated['comment_text'],
            'parent_comment_id' => $validated['parent_comment_id'] ?? null,
        ]);

        $post->increment('comments_count');

        return $this->success(['comment' => $comment], null, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/doctor/comments/{id}",
     *     tags={"Doctor - Posts"},
     *     summary="Delete own comment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return $this->error('Comment not found', 404);
        }
        if ($comment->user_type !== 'doctor' || (int) $comment->user_id !== (int) $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $post = $comment->post;
        $comment->delete();
        if ($post) {
            $post->decrement('comments_count');
        }

        return $this->success([], 'Comment deleted');
    }
}
