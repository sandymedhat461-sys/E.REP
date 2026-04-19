<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/posts",
     *     tags={"Rep - Posts"},
     *     summary="List posts",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $posts = Post::with('author')
            ->withCount(['comments', 'postLikes as likes_count'])
            ->orderByDesc('created_at')
            ->paginate(15);
        return $this->success(['posts' => $posts]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/posts",
     *     tags={"Rep - Posts"},
     *     summary="Create post",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="status", type="string", enum={"published","draft"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'required_without:body'],
            'body' => ['nullable', 'string', 'required_without:content'],
            'status' => ['nullable', 'in:published,draft'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $text = $validated['body'] ?? $validated['content'];

        $post = Post::create([
            'author_type' => 'medical_rep',
            'author_id' => $rep->id,
            'title' => $validated['title'],
            'content' => $text,
            'status' => $validated['status'] ?? 'published',
        ]);

        return $this->success(['post' => $post], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/rep/posts/{id}",
     *     tags={"Rep - Posts"},
     *     summary="Get post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $post = Post::with(['author', 'comments'])
            ->withCount(['postLikes as likes_count'])
            ->find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        return $this->success(['post' => $post]);
    }

    /**
     * @OA\Put(
     *     path="/api/rep/posts/{id}",
     *     tags={"Rep - Posts"},
     *     summary="Update own post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="status", type="string", enum={"published","draft"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $post = Post::find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        if ($post->author_type !== 'medical_rep' || (int) $post->author_id !== (int) $rep->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'in:published,draft'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $text = $validated['body'] ?? $validated['content'] ?? $post->content;

        $post->update([
            'title' => $validated['title'],
            'content' => $text,
            'status' => $validated['status'] ?? $post->status,
        ]);
        return $this->success(['post' => $post->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/api/rep/posts/{id}",
     *     tags={"Rep - Posts"},
     *     summary="Delete own post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $post = Post::find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        if ($post->author_type !== 'medical_rep' || (int) $post->author_id !== (int) $rep->id) {
            return $this->error('Forbidden', 403);
        }

        $post->delete();
        return $this->success([], 'Post deleted');
    }

    /**
     * @OA\Post(
     *     path="/api/rep/posts/{postId}/comments",
     *     tags={"Rep - Posts"},
     *     summary="Add comment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="comment_text", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function storeComment(Request $request, int $postId): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        if (!Post::whereKey($postId)->exists()) {
            return $this->error('Post not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'comment_text' => ['required', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $comment = Comment::create([
            'post_id' => $postId,
            'user_type' => 'medical_rep',
            'user_id' => $rep->id,
            'comment_text' => $validated['comment_text'],
        ]);

        Post::whereKey($postId)->increment('comments_count');
        return $this->success(['comment' => $comment], null, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/rep/comments/{id}",
     *     tags={"Rep - Posts"},
     *     summary="Delete own comment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroyComment(int $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $comment = Comment::find($id);
        if (!$comment) {
            return $this->error('Comment not found', 404);
        }
        if ($comment->user_type !== 'medical_rep' || (int) $comment->user_id !== (int) $rep->id) {
            return $this->error('Forbidden', 403);
        }

        $postId = $comment->post_id;
        $comment->delete();
        Post::whereKey($postId)->decrement('comments_count');
        return $this->success([], 'Comment deleted');
    }

    /**
     * @OA\Post(
     *     path="/api/rep/posts/{postId}/like",
     *     tags={"Rep - Posts"},
     *     summary="Like post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Already liked"),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function like(int $postId): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        if (!Post::whereKey($postId)->exists()) {
            return $this->error('Post not found', 404);
        }

        $exists = PostLike::where('post_id', $postId)->where('user_type', 'medical_rep')->where('user_id', $rep->id)->exists();
        if ($exists) {
            return $this->error('Already liked', 422);
        }

        PostLike::create([
            'post_id' => $postId,
            'user_type' => 'medical_rep',
            'user_id' => $rep->id,
        ]);
        Post::whereKey($postId)->increment('likes_count');

        return $this->success([], 'Post liked', 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/rep/posts/{postId}/unlike",
     *     tags={"Rep - Posts"},
     *     summary="Unlike post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Like not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function unlike(int $postId): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $like = PostLike::where('post_id', $postId)->where('user_type', 'medical_rep')->where('user_id', $rep->id)->first();
        if (!$like) {
            return $this->error('Like not found', 404);
        }

        $like->delete();
        Post::whereKey($postId)->decrement('likes_count');
        return $this->success([], 'Post unliked');
    }
}
