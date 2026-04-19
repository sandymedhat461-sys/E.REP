<?php

namespace App\Http\Controllers\Company;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/posts",
     *     tags={"Company - Posts"},
     *     summary="List posts (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $posts = Post::with('author')->orderByDesc('created_at')->paginate(15);
        return $this->success(['posts' => $posts]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/posts",
     *     tags={"Company - Posts"},
     *     summary="Create post",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'in:published,draft'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $post = Post::create([
            'author_type' => 'company',
            'author_id' => $company->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'] ?? 'published',
        ]);

        return $this->success(['post' => $post], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/company/posts/{id}",
     *     tags={"Company - Posts"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $post = Post::with(['author', 'comments'])->withCount('postLikes as likes_count')->find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        return $this->success(['post' => $post]);
    }

    /**
     * @OA\Put(
     *     path="/api/company/posts/{id}",
     *     tags={"Company - Posts"},
     *     summary="Update post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $post = Post::find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        if ($post->author_type !== 'company' || (int) $post->author_id !== (int) $company->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'in:published,draft'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $post->update($validated);
        return $this->success(['post' => $post->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/posts/{id}",
     *     tags={"Company - Posts"},
     *     summary="Delete post",
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $post = Post::find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }
        if ($post->author_type !== 'company' || (int) $post->author_id !== (int) $company->id) {
            return $this->error('Forbidden', 403);
        }

        $post->delete();
        return $this->success([], 'Post deleted');
    }

    /**
     * @OA\Post(
     *     path="/api/company/posts/{postId}/comments",
     *     tags={"Company - Posts"},
     *     summary="Add comment on post",
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
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
            'user_type' => 'company',
            'user_id' => $company->id,
            'comment_text' => $validated['comment_text'],
        ]);

        Post::whereKey($postId)->increment('comments_count');
        return $this->success(['comment' => $comment], null, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/comments/{id}",
     *     tags={"Company - Posts"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $comment = Comment::find($id);
        if (!$comment) {
            return $this->error('Comment not found', 404);
        }
        if ($comment->user_type !== 'company' || (int) $comment->user_id !== (int) $company->id) {
            return $this->error('Forbidden', 403);
        }

        $postId = $comment->post_id;
        $comment->delete();
        Post::whereKey($postId)->decrement('comments_count');
        return $this->success([], 'Comment deleted');
    }

    /**
     * @OA\Post(
     *     path="/api/company/posts/{postId}/like",
     *     tags={"Company - Posts"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        if (!Post::whereKey($postId)->exists()) {
            return $this->error('Post not found', 404);
        }

        $exists = PostLike::where('post_id', $postId)->where('user_type', 'company')->where('user_id', $company->id)->exists();
        if ($exists) {
            return $this->error('Already liked', 422);
        }

        PostLike::create([
            'post_id' => $postId,
            'user_type' => 'company',
            'user_id' => $company->id,
        ]);
        Post::whereKey($postId)->increment('likes_count');

        return $this->success([], 'Post liked', 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/posts/{postId}/unlike",
     *     tags={"Company - Posts"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $like = PostLike::where('post_id', $postId)->where('user_type', 'company')->where('user_id', $company->id)->first();
        if (!$like) {
            return $this->error('Like not found', 404);
        }

        $like->delete();
        Post::whereKey($postId)->decrement('likes_count');
        return $this->success([], 'Post unliked');
    }
}
