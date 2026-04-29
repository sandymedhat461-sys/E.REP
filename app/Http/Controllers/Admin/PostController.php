<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function reportedPosts(): JsonResponse
    {
        $posts = Post::has('reports')
            ->with(['reports', 'author'])
            ->get();

        return $this->success([
            'posts' => $posts,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->error('Post not found', 404);
        }

        $post->delete();

        return $this->success([], 'Post deleted');
    }
}
